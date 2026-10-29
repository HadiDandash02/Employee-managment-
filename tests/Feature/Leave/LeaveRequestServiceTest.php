<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveAction;
use App\Enums\LeavePeriod;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestAction;
use App\Models\User;
use App\Notifications\LeaveRequestDecided;
use App\Notifications\LeaveRequestSubmitted;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * The service on its own (no HTTP): business rules and the transaction.
 */
class LeaveRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveRequestService $service;

    private Employee $worker;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveRequestService::class);
        $this->worker = Employee::factory()->inDepartment('Sales')->create();
        $boss = Employee::factory()->create(['department_id' => $this->worker->department_id]);
        $this->manager = User::factory()->manager()->create(['employee_id' => $boss->id]);
    }

    private function pending(): LeaveRequest
    {
        return LeaveRequest::factory()->create(['employee_id' => $this->worker->id]);
    }

    public function test_submit_creates_a_pending_request_and_its_history(): void
    {
        $user = User::factory()->create(['employee_id' => $this->worker->id]);

        $leave = $this->service->submit($this->worker, ['start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'reason' => 'Family trip'], $user);

        $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
        $this->assertSame(3.0, $leave->days); // Mon–Wed
        $this->assertDatabaseHas('leave_request_actions', [
            'leave_request_id' => $leave->id, 'user_id' => $user->id, 'action' => 'submitted', 'from_status' => null, 'to_status' => 'pending',
        ]);
    }

    public function test_start_date_cannot_be_after_end_date(): void
    {
        $this->expectException(BusinessRuleException::class);

        $this->service->submit($this->worker, ['start_date' => '2026-11-05', 'end_date' => '2026-11-01', 'reason' => 'x'], $this->manager);
    }

    public function test_dates_cannot_overlap_a_pending_or_approved_request(): void
    {
        LeaveRequest::factory()->create(['employee_id' => $this->worker->id, 'start_date' => '2026-11-10', 'end_date' => '2026-11-12']);
        LeaveRequest::factory()->rejected()->create(['employee_id' => $this->worker->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-22']);

        // Same dates as a REJECTED request: fine.
        $this->service->submit($this->worker, ['start_date' => '2026-11-20', 'end_date' => '2026-11-20', 'reason' => 'Retry'], $this->manager);

        $this->expectException(BusinessRuleException::class);
        $this->service->submit($this->worker, ['start_date' => '2026-11-12', 'end_date' => '2026-11-14', 'reason' => 'Overlaps'], $this->manager);
    }

    public function test_approve_sets_status_approver_time_and_history(): void
    {
        Carbon::setTestNow('2026-10-20 09:30:00');
        $leave = $this->pending();

        $this->service->approve($leave, $this->manager, 'Enjoy');

        $leave->refresh();
        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertSame($this->manager->id, $leave->approved_by);
        $this->assertSame('2026-10-20 09:30:00', $leave->approved_at->format('Y-m-d H:i:s'));

        $action = $leave->actions()->latest('id')->first();
        $this->assertSame(LeaveAction::Approved, $action->action);
        $this->assertSame(LeaveStatus::Pending, $action->from_status);
        $this->assertSame(LeaveStatus::Approved, $action->to_status);
        $this->assertSame('Enjoy', $action->comment);
    }

    public function test_cannot_approve_an_already_approved_request(): void
    {
        $leave = LeaveRequest::factory()->approved()->create(['employee_id' => $this->worker->id]);

        $this->expectExceptionObject(new BusinessRuleException('This leave request is already Approved: it cannot be approved.'));
        $this->service->approve($leave, $this->manager);
    }

    public function test_cannot_approve_a_rejected_request(): void
    {
        $leave = LeaveRequest::factory()->rejected()->create(['employee_id' => $this->worker->id]);

        $this->expectExceptionObject(new BusinessRuleException('This leave request is already Rejected: it cannot be approved.'));
        $this->service->approve($leave, $this->manager);
    }

    public function test_the_second_of_two_approvals_is_refused(): void
    {
        $leave = $this->pending();
        $hr = User::factory()->hr()->create();

        $this->service->approve($leave, $this->manager);

        // $leave in memory still says "pending"; the service reads the row again (locked) and refuses.
        $this->expectException(BusinessRuleException::class);
        $this->service->reject($leave, $hr, 'Too late');
    }

    public function test_employee_cannot_approve_their_own_request(): void
    {
        // Even HR, who may approve everyone, cannot approve their own leave.
        $hr = User::factory()->hr()->create(['employee_id' => $this->worker->id]);

        $this->expectExceptionObject(new BusinessRuleException('You cannot approve or reject your own leave request.'));
        $this->service->approve($this->pending(), $hr);
    }

    public function test_only_an_authorized_manager_can_approve(): void
    {
        $leave = $this->pending();
        $otherManager = User::factory()->manager()->create(['employee_id' => Employee::factory()->inDepartment('Support')->create()->id]);
        $employee = User::factory()->create(['employee_id' => Employee::factory()->create(['department_id' => $this->worker->department_id])->id]);

        foreach ([$otherManager, $employee] as $user) {
            try {
                $this->service->approve($leave, $user);
                $this->fail('Should have been refused.');
            } catch (AuthorizationException) {
                $this->assertSame(LeaveStatus::Pending, $leave->fresh()->status);
            }
        }
    }

    public function test_everything_is_rolled_back_when_the_history_row_fails(): void
    {
        $leave = $this->pending();
        // Make the 2nd write of the transaction fail, as if the database had a problem.
        LeaveRequestAction::creating(fn () => throw new RuntimeException('History table is down'));

        try {
            $this->service->approve($leave, $this->manager);
            $this->fail('The exception should reach us.');
        } catch (RuntimeException) {
        }

        // ROLLBACK: the 1st write (status = approved) was undone too.
        $leave->refresh();
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertNull($leave->approved_by);
        $this->assertNull($leave->approved_at);
    }

    public function test_half_days_are_saved_with_their_day_count(): void
    {
        $leave = $this->service->submit($this->worker, [
            'start_date' => '2026-11-02', 'start_period' => 'afternoon', 'end_date' => '2026-11-04', 'end_period' => 'morning', 'reason' => 'Trip',
        ], $this->manager);

        $leave->refresh();
        $this->assertSame(LeavePeriod::Afternoon, $leave->start_period);
        $this->assertSame(LeavePeriod::Morning, $leave->end_period);
        $this->assertSame(2.0, $leave->days);
        $this->assertSame('02 Nov 2026 (afternoon) → 04 Nov 2026 (morning)', $leave->period_text);
    }

    public function test_one_day_cannot_go_from_afternoon_back_to_morning(): void
    {
        $this->expectExceptionObject(new BusinessRuleException('A one-day leave cannot start in the afternoon and end in the morning.'));

        $this->service->submit($this->worker, [
            'start_date' => '2026-11-02', 'start_period' => 'afternoon', 'end_date' => '2026-11-02', 'end_period' => 'morning', 'reason' => 'x',
        ], $this->manager);
    }

    public function test_a_weekend_only_leave_is_refused(): void
    {
        $this->expectExceptionObject(new BusinessRuleException('These dates fall on a weekend: there are no working days to take.'));

        $this->service->submit($this->worker, ['start_date' => '2026-11-07', 'end_date' => '2026-11-08', 'reason' => 'x'], $this->manager);
    }

    public function test_morning_and_afternoon_of_the_same_day_do_not_overlap(): void
    {
        $data = ['start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'reason' => 'Doctor'];

        $this->service->submit($this->worker, [...$data, 'start_period' => 'morning', 'end_period' => 'morning'], $this->manager);
        $this->service->submit($this->worker, [...$data, 'start_period' => 'afternoon', 'end_period' => 'afternoon'], $this->manager);
        $this->assertSame(2, $this->worker->leaveRequests()->count());

        // But the full day now overlaps both halves.
        $this->expectException(BusinessRuleException::class);
        $this->service->submit($this->worker, $data, $this->manager);
    }

    public function test_submit_notifies_the_department_manager_and_hr_but_not_others(): void
    {
        Notification::fake();
        $hr = User::factory()->hr()->create();
        $admin = User::factory()->admin()->create();
        $suspendedHr = User::factory()->hr()->suspended()->create();
        $otherManager = User::factory()->manager()->create(['employee_id' => Employee::factory()->inDepartment('Support')->create()->id]);
        $requester = User::factory()->create(['employee_id' => $this->worker->id]);

        $this->service->submit($this->worker, ['start_date' => '2026-11-02', 'end_date' => '2026-11-03', 'reason' => 'Trip'], $requester);

        Notification::assertSentTo([$this->manager, $hr, $admin], LeaveRequestSubmitted::class);
        Notification::assertNotSentTo([$otherManager, $requester, $suspendedHr], LeaveRequestSubmitted::class);
    }

    public function test_the_employee_is_notified_of_the_decision(): void
    {
        Notification::fake();
        $requester = User::factory()->create(['employee_id' => $this->worker->id]);

        $this->service->reject($this->pending(), $this->manager, 'Busy week');

        Notification::assertSentTo($requester, LeaveRequestDecided::class, function (LeaveRequestDecided $notification) use ($requester) {
            $data = $notification->toArray($requester);

            return $data['title'] === 'Leave request rejected' && str_contains($data['message'], 'Comment: Busy week');
        });
    }

    public function test_nobody_is_notified_when_the_transaction_rolls_back(): void
    {
        Notification::fake();
        User::factory()->create(['employee_id' => $this->worker->id]);
        LeaveRequestAction::creating(fn () => throw new RuntimeException('History table is down'));

        try {
            $this->service->approve($this->pending(), $this->manager);
        } catch (RuntimeException) {
        }

        Notification::assertNothingSent();
    }

    public function test_reject_records_the_reason(): void
    {
        $leave = $this->pending();

        $this->service->reject($leave, $this->manager, 'Busy season');

        $this->assertSame(LeaveStatus::Rejected, $leave->fresh()->status);
        $this->assertDatabaseHas('leave_request_actions', ['leave_request_id' => $leave->id, 'action' => 'rejected', 'comment' => 'Busy season']);
    }
}
