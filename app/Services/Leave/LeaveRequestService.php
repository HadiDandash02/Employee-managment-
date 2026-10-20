<?php

namespace App\Services\Leave;

use App\Enums\LeaveAction;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Business logic for leave requests: submit, approve, reject.
 *
 *   Controller → Form Request (is the input well-formed?) → THIS service (is it allowed now?) → Models → Database
 *
 * Every operation writes to two tables (leave_requests + leave_request_actions), so it runs
 * in DB::transaction(): everything succeeds → COMMIT; anything fails → ROLLBACK, nothing is saved.
 */
class LeaveRequestService
{
    /**
     * An employee asks for leave: create the request (pending) and its first history row.
     *
     * @param  array{start_date: string, end_date: string, reason: string}  $data
     */
    public function submit(Employee $employee, array $data, User $by): LeaveRequest
    {
        // Also checked by the Form Request; repeated here because the service may be called from elsewhere.
        if ($data['start_date'] > $data['end_date']) {
            throw new BusinessRuleException('The start date cannot be after the end date.');
        }

        return DB::transaction(function () use ($employee, $data, $by) {
            $overlap = LeaveRequest::overlapping($employee->id, $data['start_date'], $data['end_date'])
                ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw new BusinessRuleException('These dates overlap another pending or approved leave request.');
            }

            $leaveRequest = $employee->leaveRequests()->create($data);

            $this->record($leaveRequest, LeaveAction::Submitted, null, $by);

            return $leaveRequest;
        });
    }

    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $note = null): LeaveRequest
    {
        return $this->decide($leaveRequest, $approver, LeaveStatus::Approved, LeaveAction::Approved, $note);
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, string $note): LeaveRequest
    {
        return $this->decide($leaveRequest, $approver, LeaveStatus::Rejected, LeaveAction::Rejected, $note);
    }

    /**
     * Approve or reject. The rules:
     *   1. only an authorized approver (LeaveRequestPolicy::approve: Manager of that department, or HR),
     *   2. never your own request,
     *   3. only while it is still pending (not already approved, not rejected).
     */
    private function decide(LeaveRequest $leaveRequest, User $approver, LeaveStatus $to, LeaveAction $action, ?string $note): LeaveRequest
    {
        // 1. Authorization (403). The route checks it too; here it protects every other caller.
        Gate::forUser($approver)->authorize('approve', $leaveRequest);

        // 2. Not your own request.
        if ($leaveRequest->isOwnedBy($approver)) {
            throw new BusinessRuleException('You cannot approve or reject your own leave request.');
        }

        return DB::transaction(function () use ($leaveRequest, $approver, $to, $action, $note) {
            // Read the row again and LOCK it until COMMIT: if two managers click at the same moment,
            // the second one waits, then sees the new status and is refused by rule 3.
            $locked = LeaveRequest::whereKey($leaveRequest->getKey())->lockForUpdate()->firstOrFail();

            // 3. Only pending requests can be decided.
            if ($locked->status !== LeaveStatus::Pending) {
                throw new BusinessRuleException("This leave request is already {$locked->status->label()}.");
            }

            $from = $locked->status;
            $locked->status = $to;
            $locked->approved_by = $approver->id;
            $locked->approved_at = now();
            $locked->save();

            $this->record($locked, $action, $from, $approver, $note);

            return $locked;
        });
    }

    /**
     * Add one history row (leave_request_actions).
     */
    private function record(LeaveRequest $leaveRequest, LeaveAction $action, ?LeaveStatus $from, User $by, ?string $note = null): void
    {
        $leaveRequest->actions()->create([
            'user_id' => $by->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $leaveRequest->status,
            'note' => $note,
        ]);
    }
}
