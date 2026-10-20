<?php

namespace App\Services\Leave;

use App\Enums\LeaveStatus;
use App\Models\Employee;

/**
 * How much leave an employee has earned and has left in a year (rules in config/leave.php):
 *   - the base days (default 3) at the start of the year,
 *   - plus 1.25 days at the start of every month, up to the current month
 *     (a past year counts all 12 months, a future year none yet),
 *   - someone hired during the year earns from their hire month.
 * A leave belongs to the year it starts in (LeaveRequestService refuses leaves that run into the next year).
 */
class LeaveBalanceService
{
    public function for(Employee $employee, ?int $year = null): LeaveBalance
    {
        $year ??= now()->year;

        // One query: [approved => 5.0, pending => 2.0]; rejected requests do not count.
        $totals = $employee->leaveRequests()
            ->whereYear('start_date', $year)
            ->whereIn('status', [LeaveStatus::Approved, LeaveStatus::Pending])
            ->toBase()
            ->selectRaw('status, sum(days) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $hired = $employee->loadMissing('profile')->profile?->hire_date;
        $notYetHired = $hired !== null && $hired->year > $year;

        return new LeaveBalance(
            year: $year,
            base: $notYetHired ? 0.0 : (float) $employee->base_leave_days,
            months: $notYetHired ? 0 : $this->monthsEarned($year, $hired?->year === $year ? $hired->month : 1),
            monthlyAccrual: (float) config('leave.monthly_accrual'),
            taken: (float) ($totals[LeaveStatus::Approved->value] ?? 0),
            pending: (float) ($totals[LeaveStatus::Pending->value] ?? 0),
        );
    }

    /**
     * Months started in $year from $fromMonth until today: October 20 → 10 (from January).
     */
    private function monthsEarned(int $year, int $fromMonth): int
    {
        $lastMonth = match (true) {
            $year < now()->year => 12,
            $year > now()->year => 0,
            default => now()->month,
        };

        return max(0, $lastMonth - $fromMonth + 1);
    }
}
