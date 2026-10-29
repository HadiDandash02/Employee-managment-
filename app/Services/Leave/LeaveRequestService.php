<?php

namespace App\Services\Leave;

use App\Enums\LeaveAction;
use App\Enums\LeavePeriod;
use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestDecided;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

/**
 * Business logic for leave requests: submit, approve, reject.
 *
 *   Controller → Form Request (is the input well-formed?) → THIS service (is it allowed now?) → Models → Database
 *
 * Every operation writes to two tables (leave_requests + leave_request_actions), so it runs
 * in DB::transaction(): everything succeeds → COMMIT; anything fails → ROLLBACK, nothing is saved.
 * Notifications are sent only AFTER the commit: nobody is told about a change that was rolled back.
 */
class LeaveRequestService
{
    public function __construct(
        private LeaveDayCounter $dayCounter,
        private LeaveBalanceService $balances,
    ) {
    }

    /**
     * An employee asks for leave: create the request (pending) and its first history row,
     * then notify who can approve it.
     *
     * @param  array{start_date: string, start_period?: string, end_date: string, end_period?: string, reason: string}  $data
     */
    public function submit(Employee $employee, array $data, User $by): LeaveRequest
    {
        $startPeriod = LeavePeriod::from($data['start_period'] ?? LeavePeriod::Morning->value);
        $endPeriod = LeavePeriod::from($data['end_period'] ?? LeavePeriod::Afternoon->value);
        $startSlot = LeaveRequest::halfDaySlot($data['start_date'], $startPeriod);
        $endSlot = LeaveRequest::halfDaySlot($data['end_date'], $endPeriod);

        // Also checked by the Form Request; repeated here because the service may be called from elsewhere.
        if ($data['start_date'] > $data['end_date']) {
            throw new BusinessRuleException('The start date cannot be after the end date.');
        }

        if ($startSlot > $endSlot) {
            throw new BusinessRuleException('A one-day leave cannot start in the afternoon and end in the morning.');
        }

        // Each year has its own leave (base + monthly days), so one request stays inside one year.
        $year = (int) substr($data['start_date'], 0, 4);
        if ((int) substr($data['end_date'], 0, 4) !== $year) {
            throw new BusinessRuleException("A leave cannot run into the next year: send one request until 31 Dec {$year} and another from 1 Jan.");
        }

        $days = $this->dayCounter->count($data['start_date'], $startPeriod, $data['end_date'], $endPeriod);

        if ($days <= 0) {
            throw new BusinessRuleException('These dates fall on a weekend: there are no working days to take.');
        }

        $leaveRequest = DB::transaction(function () use ($employee, $data, $by, $startPeriod, $endPeriod, $startSlot, $endSlot, $days, $year) {
            // Lock the employee row: two requests sent at the same moment are checked one after
            // the other, so together they cannot go over what was earned.
            $locked = Employee::whereKey($employee->getKey())->lockForUpdate()->firstOrFail();
            $balance = $this->balances->for($locked, $year);

            if ($days > $balance->remaining()) {
                throw new BusinessRuleException(sprintf(
                    'Not enough leave left: this request is %s %s, but you have %s days left in %d (%s earned so far, %s taken, %s waiting for approval).',
                    LeaveBalance::format($days), $days == 1 ? 'day' : 'days',
                    LeaveBalance::format($balance->remaining()), $year, LeaveBalance::format($balance->earned()),
                    LeaveBalance::format($balance->taken), LeaveBalance::format($balance->pending),
                ));
            }

            // Same dates as a pending/approved request? Half days count: Monday morning and
            // Monday afternoon are two different leaves and do not overlap.
            $overlap = LeaveRequest::overlapping($employee->id, $data['start_date'], $data['end_date'])
                ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
                ->lockForUpdate()
                ->get()
                ->contains(fn (LeaveRequest $other) => $other->startSlot() <= $endSlot && $other->endSlot() >= $startSlot);

            if ($overlap) {
                throw new BusinessRuleException('These dates overlap another pending or approved leave request.');
            }

            $leaveRequest = $employee->leaveRequests()->create([
                'start_date' => $data['start_date'],
                'start_period' => $startPeriod,
                'end_date' => $data['end_date'],
                'end_period' => $endPeriod,
                'days' => $days,
                'reason' => $data['reason'],
            ]);

            $this->record($leaveRequest, LeaveAction::Submitted, null, $by);

            return $leaveRequest;
        });

        Notification::send($this->approversFor($leaveRequest), new LeaveRequestSubmitted($leaveRequest));

        return $leaveRequest;
    }

    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $comment = null): LeaveRequest
    {
        return $this->decide($leaveRequest, $approver, LeaveStatus::Approved, LeaveAction::Approved, $comment);
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, string $comment): LeaveRequest
    {
        return $this->decide($leaveRequest, $approver, LeaveStatus::Rejected, LeaveAction::Rejected, $comment);
    }

    /**
     * The employee withdraws their own request. Only while it is pending: an approved leave
     * must be handled by HR, a rejected or cancelled one is already closed.
     * The days it reserved become available again (LeaveBalanceService counts pending + approved only).
     */
    public function cancel(LeaveRequest $leaveRequest, User $by): LeaveRequest
    {
        // Only the employee who asked (LeaveRequestPolicy::cancel); the route checks it too.
        Gate::forUser($by)->authorize('cancel', $leaveRequest);

        return DB::transaction(function () use ($leaveRequest, $by) {
            $locked = LeaveRequest::whereKey($leaveRequest->getKey())->lockForUpdate()->firstOrFail();

            $this->ensureTransition($locked, LeaveStatus::Cancelled);

            $from = $locked->status;
            $locked->status = LeaveStatus::Cancelled;
            $locked->save();

            $this->record($locked, LeaveAction::Cancelled, $from, $by);

            return $locked;
        });
    }

    /**
     * Approve or reject. The rules:
     *   1. only an authorized approver (LeaveRequestPolicy::approve: Manager of that department, or HR),
     *   2. never your own request,
     *   3. only while it is still pending (not already approved, not rejected),
     *   4. approving cannot go over the leave the employee has earned that year.
     * Afterwards the employee is notified.
     */
    private function decide(LeaveRequest $leaveRequest, User $approver, LeaveStatus $to, LeaveAction $action, ?string $comment): LeaveRequest
    {
        // 1. Authorization (403). The route checks it too; here it protects every other caller.
        Gate::forUser($approver)->authorize('approve', $leaveRequest);

        // 2. Not your own request.
        if ($leaveRequest->isOwnedBy($approver)) {
            throw new BusinessRuleException('You cannot approve or reject your own leave request.');
        }

        $decided = DB::transaction(function () use ($leaveRequest, $approver, $to, $action, $comment) {
            // Read the row again and LOCK it until COMMIT: if two managers click at the same moment,
            // the second one waits, then sees the new status and is refused by rule 3.
            $locked = LeaveRequest::whereKey($leaveRequest->getKey())->lockForUpdate()->firstOrFail();

            // 3. Only pending requests can be decided (APPROVED → REJECTED, CANCELLED → APPROVED, … are blocked).
            $this->ensureTransition($locked, $to);

            // 4. Approving cannot go over what was earned (HR may have lowered the base since the request was sent).
            if ($to === LeaveStatus::Approved) {
                $balance = $this->balances->for($locked->employee, $locked->start_date->year);

                if ($balance->taken + $locked->days > $balance->earned()) {
                    throw new BusinessRuleException(sprintf(
                        'Approving would go over the leave earned: %s already has %s of %s earned days approved in %d.',
                        $locked->employee->full_name, LeaveBalance::format($balance->taken),
                        LeaveBalance::format($balance->earned()), $balance->year,
                    ));
                }
            }

            $from = $locked->status;
            $locked->status = $to;
            $locked->approved_by = $approver->id;
            $locked->approved_at = now();
            $locked->save();

            $this->record($locked, $action, $from, $approver, $comment);

            return $locked;
        });

        // The employee's login (users.employee_id), if they have one.
        $decided->load(['approver', 'employee.user']);
        $decided->employee?->user?->notify(new LeaveRequestDecided($decided, $comment));

        return $decided;
    }

    /**
     * Active users who may decide this request (HR, Admin, the department's Manager), except the requester.
     *
     * @return Collection<int, User>
     */
    private function approversFor(LeaveRequest $leaveRequest): Collection
    {
        $roles = collect(config('permissions.roles'))
            ->filter(fn (array $role) => array_intersect(['*', 'leave.approve'], $role['permissions']) !== [])
            ->keys();

        return User::whereIn('role', $roles)
            ->where('is_active', true)
            ->with('employee')
            ->get()
            ->filter(fn (User $user) => ! $leaveRequest->isOwnedBy($user) && $user->can('approve', $leaveRequest))
            ->values();
    }

    /**
     * Strict status transitions (LeaveStatus::allowedTransitions()): only PENDING may become
     * APPROVED, REJECTED or CANCELLED. Any other change is refused, e.g. "already Approved: it cannot be rejected".
     */
    private function ensureTransition(LeaveRequest $leaveRequest, LeaveStatus $to): void
    {
        if (! $leaveRequest->status->canTransitionTo($to)) {
            throw new BusinessRuleException(sprintf(
                'This leave request is already %s: it cannot be %s.',
                $leaveRequest->status->label(), strtolower($to->label()),
            ));
        }
    }

    /**
     * Add one history row (leave_request_actions).
     */
    private function record(LeaveRequest $leaveRequest, LeaveAction $action, ?LeaveStatus $from, User $by, ?string $comment = null): void
    {
        $leaveRequest->actions()->create([
            'user_id' => $by->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $leaveRequest->status,
            'comment' => $comment,
        ]);
    }
}
