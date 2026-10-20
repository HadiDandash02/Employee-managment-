<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Authorization for Employee records (created with: php artisan make:policy EmployeePolicy --model=Employee).
 *
 * A Gate answers "may this user do X at all?"; a Policy answers "may this user do X to THIS employee?".
 * Laravel finds this class automatically for App\Models\Employee. It is used by:
 *   - routes:      ->can('view', 'employee')            (routes/web.php)
 *   - controllers: Gate::authorize('updateContact', $employee)
 *   - Blade:       @can('update', $employee)
 *
 * Rules (permissions from config/permissions.php):
 *   HR / Admin  → see all employees, create, edit, delete           (employees.view-all, .create, .update, .delete)
 *   Manager     → see employees of their own department only        (employees.view-team)
 *   Employee    → see their own record, edit their own phone/address (profile.view-own, profile.update-own)
 */
class EmployeePolicy
{
    /**
     * Open the employee list (which rows they get is decided by Employee::scopeVisibleTo()).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employees.view');
    }

    /**
     * See one employee: all of them, their own department, or only themselves.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasPermission('employees.view-all')) {
            return true;
        }

        if ($user->hasPermission('employees.view-team') && $this->isInTeamOf($user, $employee)) {
            return true;
        }

        return $this->isOwnRecord($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('employees.create');
    }

    /**
     * Full edit (salary, department, …): HR / Admin only.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.update') && $this->view($user, $employee);
    }

    /**
     * Limited self-service edit: an employee may change their OWN phone and address only.
     */
    public function updateContact(User $user, Employee $employee): bool
    {
        return $user->hasPermission('profile.update-own') && $this->isOwnRecord($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.delete');
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.delete');
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.delete');
    }

    private function isOwnRecord(User $user, Employee $employee): bool
    {
        return $user->employee_id !== null && $user->employee_id === $employee->id;
    }

    /**
     * Same department as the employee record the user's login is linked to.
     */
    private function isInTeamOf(User $user, Employee $employee): bool
    {
        $department = $user->employee?->department_id;

        return $department !== null && $department === $employee->department_id;
    }
}
