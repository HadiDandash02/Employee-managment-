<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Month 1 Final Assessment: the Employee Leave Management rules.
 *   1. CANCELLED: an employee withdraws their own PENDING request, with a history row.
 *   2. Strict transitions: only PENDING → APPROVED | REJECTED | CANCELLED.
 *   3. Search (employee name or reason) and pagination (10 per page) for Managers and HR.
 *   4. Policies: no peeking at another employee's request, no approving outside the team.
 */
class LeaveFinalAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private Employee $sam;      // Sales

    private User $samLogin;

    private User $salesManager;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed "today", so the leave dates below are always in the future and inside the earned balance.
        $this->travelTo('2026-10-29 09:00:00');

        $this->sam = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam', 'last_name' => 'Haddad']);
        $this->samLogin = User::factory()->create(['role' => 'employee', 'employee_id' => $this->sam->id]);
        $boss = Employee::factory()->create(['department_id' => $this->sam->department_id]);
        $this->salesManager = User::factory()->manager()->create(['employee_id' => $boss->id]);
        $this->hr = User::factory()->hr()->create();
    }

    /**
     * Mon 2 Nov – Tue 3 Nov 2026: 2 working days.
     */
    private function leave(LeaveStatus $status = LeaveStatus::Pending, ?Employee $employee = null, array $attributes = []): LeaveRequest
    {
        return LeaveRequest::factory()->create([
            'employee_id' => ($employee ?? $this->sam)->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'status' => $status,
            ...$attributes,
        ]);
    }

    // 1. CANCELLED ------------------------------------------------------------------------------

    public function test_employee_cancels_their_own_pending_request_and_it_is_logged(): void
    {
        $leave = $this->leave();

        $this->actingAs($this->samLogin)
            ->post(route('leave-requests.cancel', $leave))
            ->assertRedirect(route('leave-requests.index'))
            ->assertSessionHas('success');

        $this->assertSame(LeaveStatus::Cancelled, $leave->fresh()->status);
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('leave_request_actions', [
            'leave_request_id' => $leave->id,
            'user_id' => $this->samLogin->id,
            'action' => 'cancelled',
            'from_status' => 'pending',
            'to_status' => 'cancelled',
        ]);

        // Cancelling twice is a transition CANCELLED → CANCELLED: refused, and no second history row.
        $this->from(route('leave-requests.show', $leave))
            ->post(route('leave-requests.cancel', $leave))
            ->assertRedirect(route('leave-requests.show', $leave))
            ->assertSessionHasErrors(['business' => 'This leave request is already Cancelled: it cannot be cancelled.']);
        $this->assertSame(1, $leave->actions()->where('action', 'cancelled')->count());
    }

    public function test_only_the_owner_can_cancel(): void
    {
        $leave = $this->leave();

        foreach ([$this->salesManager, $this->hr] as $user) {
            $this->actingAs($user)->post(route('leave-requests.cancel', $leave))->assertForbidden();
        }

        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
    }

    // 2. Strict status transitions ---------------------------------------------------------------

    public function test_the_transition_table(): void
    {
        foreach (LeaveStatus::cases() as $from) {
            foreach (LeaveStatus::cases() as $to) {
                $allowed = $from === LeaveStatus::Pending && $to !== LeaveStatus::Pending;

                $this->assertSame($allowed, $from->canTransitionTo($to), "{$from->name} → {$to->name}");
            }
        }
    }

    public function test_pending_can_become_approved_or_rejected(): void
    {
        $service = app(LeaveRequestService::class);

        $this->assertSame(LeaveStatus::Approved, $service->approve($this->leave(), $this->salesManager)->status);

        $other = $this->leave(attributes: ['start_date' => '2026-11-05', 'end_date' => '2026-11-05']);
        $this->assertSame(LeaveStatus::Rejected, $service->reject($other, $this->salesManager, 'Busy week')->status);
    }

    public function test_invalid_transitions_throw_a_business_rule_exception(): void
    {
        $service = app(LeaveRequestService::class);

        $blocked = [
            'APPROVED → REJECTED' => [LeaveStatus::Approved, fn (LeaveRequest $l) => $service->reject($l, $this->hr, 'Too late')],
            'APPROVED → APPROVED' => [LeaveStatus::Approved, fn (LeaveRequest $l) => $service->approve($l, $this->hr)],
            'APPROVED → CANCELLED' => [LeaveStatus::Approved, fn (LeaveRequest $l) => $service->cancel($l, $this->samLogin)],
            'REJECTED → APPROVED' => [LeaveStatus::Rejected, fn (LeaveRequest $l) => $service->approve($l, $this->hr)],
            'REJECTED → CANCELLED' => [LeaveStatus::Rejected, fn (LeaveRequest $l) => $service->cancel($l, $this->samLogin)],
            'CANCELLED → APPROVED' => [LeaveStatus::Cancelled, fn (LeaveRequest $l) => $service->approve($l, $this->hr)],
            'CANCELLED → REJECTED' => [LeaveStatus::Cancelled, fn (LeaveRequest $l) => $service->reject($l, $this->hr, 'No')],
        ];

        foreach ($blocked as $case => [$from, $change]) {
            $leave = $this->leave($from);

            try {
                $change($leave);
                $this->fail("{$case} should have been blocked.");
            } catch (BusinessRuleException $e) {
                $this->assertStringStartsWith("This leave request is already {$from->label()}", $e->getMessage(), $case);
            }

            // Nothing changed: same status, no new history row.
            $this->assertSame($from, $leave->fresh()->status, $case);
            $this->assertSame(0, $leave->actions()->count(), $case);
        }
    }

    public function test_a_blocked_transition_shows_the_message_on_the_page(): void
    {
        $leave = $this->leave(LeaveStatus::Approved);

        $this->actingAs($this->salesManager)
            ->from(route('leave-requests.show', $leave))
            ->post(route('leave-requests.reject', $leave), ['comment' => 'Changed my mind'])
            ->assertRedirect(route('leave-requests.show', $leave))
            ->assertSessionHasErrors(['business' => 'This leave request is already Approved: it cannot be rejected.']);

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    // 3. Search & pagination ---------------------------------------------------------------------

    public function test_hr_and_manager_search_by_employee_name_or_reason(): void
    {
        $nadia = Employee::factory()->create(['first_name' => 'Nadia', 'last_name' => 'Saab', 'department_id' => $this->sam->department_id]);
        $tom = Employee::factory()->inDepartment('Support')->create(['first_name' => 'Tom']);
        $this->leave(attributes: ['reason' => 'Family wedding']);
        $this->leave(employee: $nadia, attributes: ['reason' => 'Dentist appointment']);
        $this->leave(employee: $tom, attributes: ['reason' => 'Wedding abroad']);

        $reasons = fn (User $user, string $search) => $this->actingAs($user)
            ->get(route('leave-requests.index', ['search' => $search]))
            ->assertOk()
            ->viewData('leaveRequests')->pluck('reason')->sort()->values()->all();

        // By employee name (first or last) and by reason.
        $this->assertSame(['Dentist appointment'], $reasons($this->hr, 'saab'));
        $this->assertSame(['Family wedding'], $reasons($this->hr, 'sam'));
        $this->assertSame(['Family wedding', 'Wedding abroad'], $reasons($this->hr, 'wedding'));

        // The manager searches only inside their own team: Tom (Support) is never found.
        $this->assertSame(['Family wedding'], $reasons($this->salesManager, 'wedding'));
        $this->assertSame([], $reasons($this->salesManager, 'tom'));

        // The search box is on the page with the value kept.
        $this->actingAs($this->hr)->get(route('leave-requests.index', ['search' => 'wedding']))
            ->assertSee('name="search"', false)
            ->assertSee('value="wedding"', false);
    }

    public function test_the_list_shows_10_per_page_and_keeps_the_search_in_the_links(): void
    {
        LeaveRequest::factory()->count(23)->create(['employee_id' => $this->sam->id, 'reason' => 'Trip']);
        LeaveRequest::factory()->create(['employee_id' => $this->sam->id, 'reason' => 'Doctor']);
        $this->actingAs($this->hr);

        $this->get(route('leave-requests.index'))
            ->assertViewHas('leaveRequests', fn ($page) => $page->perPage() === 10 && $page->count() === 10 && $page->total() === 24 && $page->lastPage() === 3);

        $this->get(route('leave-requests.index', ['search' => 'trip', 'page' => 3]))
            ->assertViewHas('leaveRequests', fn ($page) => $page->total() === 23 && $page->count() === 3);

        // The "next page" link keeps the search.
        $this->get(route('leave-requests.index', ['search' => 'trip']))
            ->assertSee('search=trip&amp;page=2', false);
    }

    // 4. Policies --------------------------------------------------------------------------------

    public function test_an_employee_cannot_view_another_employees_request(): void
    {
        $colleague = Employee::factory()->create(['department_id' => $this->sam->department_id]);
        $theirs = $this->leave(employee: $colleague, attributes: ['reason' => 'Private reason']);
        $mine = $this->leave(attributes: ['start_date' => '2026-11-09', 'end_date' => '2026-11-09']);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.show', $mine))->assertOk();
        $this->get(route('leave-requests.show', $theirs))->assertForbidden();
        $this->get(route('leave-requests.index'))->assertDontSee('Private reason');

        $this->assertFalse($this->samLogin->can('view', $theirs));
    }

    public function test_a_manager_cannot_approve_outside_their_team(): void
    {
        $tom = Employee::factory()->inDepartment('Support')->create();
        $leave = $this->leave(employee: $tom);
        $this->actingAs($this->salesManager);

        $this->get(route('leave-requests.show', $leave))->assertForbidden();
        $this->post(route('leave-requests.approve', $leave))->assertForbidden();
        $this->post(route('leave-requests.reject', $leave), ['comment' => 'No'])->assertForbidden();

        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
        $this->assertFalse($this->salesManager->can('approve', $leave));
        $this->assertTrue($this->salesManager->can('approve', $this->leave(attributes: ['start_date' => '2026-11-10', 'end_date' => '2026-11-10'])));
    }
}
