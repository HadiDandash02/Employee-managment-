<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pages and URLs, typed by hand as each role.
 */
class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private Employee $sam;      // Sales employee

    private Employee $tom;      // Support employee

    private User $samLogin;

    private User $salesManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sam = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam']);
        $this->tom = Employee::factory()->inDepartment('Support')->create(['first_name' => 'Tom']);
        $this->samLogin = User::factory()->create(['role' => 'employee', 'employee_id' => $this->sam->id]);
        $boss = Employee::factory()->create(['first_name' => 'Mona', 'department_id' => $this->sam->department_id]);
        $this->salesManager = User::factory()->manager()->create(['employee_id' => $boss->id]);
    }

    public function test_employee_requests_leave_and_sees_only_their_own(): void
    {
        LeaveRequest::factory()->create(['employee_id' => $this->tom->id, 'reason' => 'Tom holiday']);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.create'))->assertOk();
        $this->post(route('leave-requests.store'), [
            'start_date' => '2026-12-01', 'end_date' => '2026-12-03', 'reason' => 'Sam holiday',
            'status' => 'approved', 'approved_by' => $this->samLogin->id, // ignored: not in validated()
        ])->assertRedirect(route('leave-requests.index'));

        $leave = LeaveRequest::where('employee_id', $this->sam->id)->sole();
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertNull($leave->approved_by);

        $this->get(route('leave-requests.index'))->assertOk()->assertSee('Sam holiday')->assertDontSee('Tom holiday')->assertDontSee('Review');
        $this->get(route('leave-requests.show', $leave))->assertOk()->assertSee('You cannot decide your own request')->assertDontSee('formaction', false);
    }

    public function test_form_validation(): void
    {
        $this->actingAs($this->samLogin);

        $this->from(route('leave-requests.create'))
            ->post(route('leave-requests.store'), ['start_date' => '2026-12-05', 'end_date' => '2026-12-01', 'reason' => ''])
            ->assertRedirect(route('leave-requests.create'))
            ->assertSessionHasErrors(['end_date', 'reason']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_an_overlapping_request_shows_the_business_rule_message(): void
    {
        LeaveRequest::factory()->create(['employee_id' => $this->sam->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-05']);
        $this->actingAs($this->samLogin);

        $this->from(route('leave-requests.create'))
            ->post(route('leave-requests.store'), ['start_date' => '2026-12-04', 'end_date' => '2026-12-06', 'reason' => 'Again'])
            ->assertRedirect(route('leave-requests.create'))
            ->assertSessionHasErrors(['business' => 'These dates overlap another pending or approved leave request.']);

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_manager_approves_a_request_of_their_department(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id]);
        $this->actingAs($this->salesManager);

        $this->get(route('leave-requests.index'))->assertOk()->assertSee('Sam')->assertSee('Review');
        $this->get(route('leave-requests.show', $leave))->assertOk()->assertSee('Approve');

        $this->post(route('leave-requests.approve', $leave), ['comment' => 'OK'])
            ->assertRedirect(route('leave-requests.show', $leave));

        $leave->refresh();
        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertSame($this->salesManager->id, $leave->approved_by);
        $this->assertNotNull($leave->approved_at);

        // Clicking again (or a second tab): refused with a message, nothing changes.
        $this->from(route('leave-requests.show', $leave))
            ->post(route('leave-requests.reject', $leave), ['comment' => 'Changed my mind'])
            ->assertSessionHasErrors(['business' => 'This leave request is already Approved: it cannot be rejected.']);
        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    public function test_reject_needs_a_note(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id]);
        $this->actingAs($this->salesManager);

        $this->post(route('leave-requests.reject', $leave), ['comment' => ''])->assertSessionHasErrors('comment');
        $this->post(route('leave-requests.reject', $leave), ['comment' => 'Busy week'])->assertRedirect();

        $this->assertSame(LeaveStatus::Rejected, $leave->fresh()->status);
    }

    public function test_manager_cannot_see_or_decide_another_department(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->tom->id, 'reason' => 'Tom holiday']);
        $this->actingAs($this->salesManager);

        $this->get(route('leave-requests.index'))->assertDontSee('Tom holiday');
        $this->get(route('leave-requests.show', $leave))->assertForbidden();
        $this->post(route('leave-requests.approve', $leave))->assertForbidden();
        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
    }

    public function test_employee_cannot_approve_by_typing_the_url(): void
    {
        $colleague = Employee::factory()->create(['department_id' => $this->sam->department_id]);
        $leave = LeaveRequest::factory()->create(['employee_id' => $colleague->id]);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.show', $leave))->assertForbidden();
        $this->post(route('leave-requests.approve', $leave))->assertForbidden();
        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
    }

    public function test_hr_can_decide_every_department_but_not_their_own_request(): void
    {
        $tomLeave = LeaveRequest::factory()->create(['employee_id' => $this->tom->id]);
        $hr = User::factory()->hr()->create(['employee_id' => $this->tom->id]);
        $samLeave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id]);
        $this->actingAs($hr);

        $this->post(route('leave-requests.approve', $samLeave))->assertRedirect();
        $this->assertSame(LeaveStatus::Approved, $samLeave->fresh()->status);

        $this->from(route('leave-requests.show', $tomLeave))
            ->post(route('leave-requests.approve', $tomLeave))
            ->assertSessionHasErrors(['business' => 'You cannot approve or reject your own leave request.']);
        $this->assertSame(LeaveStatus::Pending, $tomLeave->fresh()->status);
    }

    public function test_status_tabs_and_sidebar_badge(): void
    {
        LeaveRequest::factory()->count(2)->create(['employee_id' => $this->sam->id, 'reason' => 'Pending one']);
        LeaveRequest::factory()->approved()->create(['employee_id' => $this->sam->id, 'reason' => 'Approved one']);
        $this->actingAs($this->salesManager);

        $this->get(route('leave-requests.index', ['status' => 'approved']))
            ->assertOk()->assertSee('Approved one')->assertDontSee('Pending one')
            ->assertViewHas('counts', fn ($counts) => $counts['pending'] === 2 && $counts['approved'] === 1);

        // Sidebar: "Leave Requests 2"
        $this->get(route('leave-requests.index'))->assertSeeInOrder(['Leave Requests', 'text-bg-warning', '2'], false);
    }

    public function test_a_login_without_an_employee_cannot_request_leave(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employee']));

        $this->get(route('leave-requests.index'))->assertOk()->assertDontSee('Request leave</a>', false);
        $this->get(route('leave-requests.create'))->assertForbidden();
        $this->post(route('leave-requests.store'), ['start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'reason' => 'x'])->assertForbidden();
    }

    public function test_employee_cancels_their_own_pending_request(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id]);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.show', $leave))->assertSee('Cancel request');
        $this->post(route('leave-requests.cancel', $leave))
            ->assertRedirect(route('leave-requests.index'))
            ->assertSessionHas('success', 'Your leave request was cancelled. The days are available again.');

        $this->assertSame(LeaveStatus::Cancelled, $leave->fresh()->status);
        $this->assertDatabaseHas('leave_request_actions', [
            'leave_request_id' => $leave->id, 'user_id' => $this->samLogin->id, 'action' => 'cancelled', 'from_status' => 'pending', 'to_status' => 'cancelled',
        ]);

        // A cancelled request no longer reserves days, and the "Cancelled" tab lists it.
        $this->assertSame(0.0, app(\App\Services\Leave\LeaveBalanceService::class)->for($this->sam, $leave->start_date->year)->pending);
        $this->get(route('leave-requests.index', ['status' => 'cancelled']))->assertSee($leave->reason);
    }

    public function test_only_pending_requests_can_be_cancelled(): void
    {
        $leave = LeaveRequest::factory()->approved()->create(['employee_id' => $this->sam->id]);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.show', $leave))->assertDontSee('Cancel request');
        $this->from(route('leave-requests.show', $leave))
            ->post(route('leave-requests.cancel', $leave))
            ->assertSessionHasErrors(['business' => 'This leave request is already Approved: it cannot be cancelled.']);

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    public function test_nobody_else_can_cancel_a_request_not_even_hr(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id]);
        $colleague = User::factory()->create(['employee_id' => Employee::factory()->create(['department_id' => $this->sam->department_id])->id]);

        // HR sees everything, but withdrawing someone's leave is not part of HR's rights.
        foreach ([$colleague, $this->salesManager, User::factory()->hr()->create()] as $user) {
            $this->actingAs($user)->post(route('leave-requests.cancel', $leave))->assertForbidden();
        }

        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
    }

    public function test_a_cancelled_request_cannot_be_approved(): void
    {
        $leave = LeaveRequest::factory()->create(['employee_id' => $this->sam->id, 'status' => LeaveStatus::Cancelled]);

        $this->actingAs($this->salesManager)
            ->from(route('leave-requests.show', $leave))
            ->post(route('leave-requests.approve', $leave))
            ->assertSessionHasErrors(['business' => 'This leave request is already Cancelled: it cannot be approved.']);
    }

    public function test_search_by_employee_name_or_reason_inside_the_visible_rows(): void
    {
        $sara = Employee::factory()->create(['first_name' => 'Sara', 'last_name' => 'Khoury', 'department_id' => $this->sam->department_id]);
        LeaveRequest::factory()->create(['employee_id' => $this->sam->id, 'reason' => 'Wedding in Tripoli']);
        LeaveRequest::factory()->create(['employee_id' => $sara->id, 'reason' => 'Dentist']);
        LeaveRequest::factory()->create(['employee_id' => $this->tom->id, 'reason' => 'Wedding abroad']); // other department
        $this->actingAs($this->salesManager);

        $this->get(route('leave-requests.index', ['search' => 'khoury']))
            ->assertOk()->assertSee('Dentist')->assertDontSee('Wedding in Tripoli');

        // "wedding" matches two reasons, but Tom is outside this manager's team.
        $this->get(route('leave-requests.index', ['search' => 'wedding']))
            ->assertSee('Wedding in Tripoli')->assertDontSee('Wedding abroad')
            ->assertViewHas('counts', fn ($counts) => $counts->sum() === 1);

        $this->get(route('leave-requests.index', ['search' => 'nothing-matches']))->assertSee('No leave requests match');
    }

    public function test_search_and_status_filter_work_together_and_paginate(): void
    {
        LeaveRequest::factory()->count(12)->create(['employee_id' => $this->sam->id, 'reason' => 'Trip']);
        LeaveRequest::factory()->approved()->create(['employee_id' => $this->sam->id, 'reason' => 'Trip approved']);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.index', ['status' => 'pending', 'search' => 'trip']))
            ->assertOk()
            ->assertViewHas('leaveRequests', fn ($page) => $page->total() === 12 && $page->count() === 10)
            ->assertSee('status=pending', false);

        $this->get(route('leave-requests.index', ['status' => 'pending', 'search' => 'trip', 'page' => 2]))
            ->assertViewHas('leaveRequests', fn ($page) => $page->count() === 2);
    }

    public function test_suspended_users_are_blocked(): void
    {
        $this->samLogin->update(['is_active' => false]);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.index'))->assertForbidden();
    }
}
