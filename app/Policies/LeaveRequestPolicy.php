<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

/**
 * Who may see and decide leave requests (permissions from config/permissions.php).
 *
 *   Employee → request leave for themselves, see their own requests           (leave.request)
 *   Manager  → also approve/reject requests of their own department           (leave.approve + employees.view-team)
 *   HR/Admin → approve/reject every request                                    (leave.approve + employees.view-all)
 *
 * "Not your own request" and "only while pending" are business rules: LeaveRequestService checks them.
 */
class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.request') || $user->hasPermission('leave.approve');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->isOwnedBy($user) || $this->approve($user, $leaveRequest);
    }

    /**
     * Only a login that is linked to an employee record can ask for leave.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('leave.request') && $user->employee_id !== null;
    }

    /**
     * Cancel (withdraw): ONLY the employee who asked. HR can see every request, but being HR
     * does not give the right to withdraw someone else's leave; they approve or reject instead.
     * "Only while pending" is a business rule: LeaveRequestService::cancel().
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.request') && $leaveRequest->isOwnedBy($user);
    }

    /**
     * Approve or reject: HR for everyone, a Manager only for their own department.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $user->hasPermission('leave.approve')) {
            return false;
        }

        if ($user->hasPermission('employees.view-all')) {
            return true;
        }

        $department = $user->employee?->department_id;

        return $user->hasPermission('employees.view-team')
            && $department !== null
            && $department === $leaveRequest->employee?->department_id;
    }
}
