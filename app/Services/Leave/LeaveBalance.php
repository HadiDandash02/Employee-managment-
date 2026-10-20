<?php

namespace App\Services\Leave;

/**
 * An employee's leave for one year, in working days:
 *   earned = base 3 + 10 months × 1.25 = 15.5
 *   remaining = earned 15.5 − taken 5 (approved) − pending 2 (waiting for an answer) = 8.5
 * Pending days are reserved, so two requests cannot together go over what was earned.
 */
final class LeaveBalance
{
    public function __construct(
        public readonly int $year,
        public readonly float $base,
        public readonly int $months,
        public readonly float $monthlyAccrual,
        public readonly float $taken,
        public readonly float $pending,
    ) {
    }

    /**
     * Days earned in this year so far: the base + every month started.
     */
    public function earned(): float
    {
        return $this->base + $this->months * $this->monthlyAccrual;
    }

    public function remaining(): float
    {
        return max(0, $this->earned() - $this->taken - $this->pending);
    }

    /**
     * "2.5" or "3" (no ".0").
     */
    public static function format(float $days): string
    {
        return rtrim(rtrim(number_format($days, 2), '0'), '.');
    }
}
