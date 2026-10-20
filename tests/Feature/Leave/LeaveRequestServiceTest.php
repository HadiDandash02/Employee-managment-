<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveAction;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestAction;
use App\Models\User;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        $this->assertSame(3, $leave->days);
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
        $this->service->submit($this->worker, ['start_date' => '2026-11-21', 'end_date' => '2026-11-21', 'reason' => 'Retry'], $this->manager);

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
        $this->assertSame('Enjoy', $action->note);
    }

    public function test_cannot_approve_an_already_approved_request(): void
    {
        $leave = LeaveRequest::factory()->approved()->create(['employee_id' => $this->worker->id]);

        $this->expectExceptionObject(new BusinessRuleException('This leave request is already Approved.'));
        $this->service->approve($leave, $this->manager);
    }

    public function test_cannot_approve_a_rejected_request(): void
    {
        $leave = LeaveRequest::factory()->rejected()->create(['employee_id' => $this->worker->id]);

        $this->expectExceptionObject(new BusinessRuleException('This leave request is already Rejected.'));
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

    public function test_reject_records_the_reason(): void
    {
        $leave = $this->pending();

        $this->service->reject($leave, $this->manager, 'Busy season');

        $this->assertSame(LeaveStatus::Rejected, $leave->fresh()->status);
        $this->assertDatabaseHas('leave_request_actions', ['leave_request_id' => $leave->id, 'action' => 'rejected', 'note' => 'Busy season']);
    }
}
