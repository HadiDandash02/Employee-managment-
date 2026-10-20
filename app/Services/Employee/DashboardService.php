<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Carbon\CarbonInterface;

/**
 * Builds the numbers shown on an employee's own dashboard.
 *
 * Kept out of the controller (thin controller) and out of Blade (views only display),
 * and injected by Laravel's service container (see App\Http\Controllers\Employee\DashboardController).
 */
class DashboardService
{
    /**
     * @return array{years_of_service: ?int, days_to_birthday: int, department_size: int, skills: int}
     */
    public function summaryFor(Employee $employee): array
    {
        $employee->loadMissing(['department.branch.company', 'profile', 'skills']);
        $hired = $employee->profile?->hire_date;

        return [
            'years_of_service' => $hired ? (int) $hired->diffInYears(now()) : null,
            'days_to_birthday' => $this->daysToNextBirthday($employee->date_of_birth),
            // employees in the same department, including this one (only a count: no names or salaries)
            'department_size' => Employee::where('department_id', $employee->department_id)->count(),
            'skills' => $employee->skills->count(),
        ];
    }

    private function daysToNextBirthday(CarbonInterface $dateOfBirth): int
    {
        $next = $dateOfBirth->copy()->year(now()->year)->startOfDay();

        if ($next->isPast() && ! $next->isToday()) {
            $next->addYear();
        }

        return (int) now()->startOfDay()->diffInDays($next);
    }
}
