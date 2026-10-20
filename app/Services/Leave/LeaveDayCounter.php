<?php

namespace App\Services\Leave;

use App\Enums\LeavePeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * How many working days a leave takes, in half days. Saturdays and Sundays are not counted.
 *
 *   Mon morning   → Mon afternoon  = 1
 *   Mon morning   → Mon morning    = 0.5   (morning only)
 *   Mon afternoon → Mon afternoon  = 0.5   (afternoon only)
 *   Mon afternoon → Wed morning    = 0.5 + 1 + 0.5 = 2
 *   Fri morning   → Mon afternoon  = 1 + 1 = 2   (the weekend is skipped)
 */
class LeaveDayCounter
{
    public function count(CarbonInterface|string $start, LeavePeriod $startPeriod, CarbonInterface|string $end, LeavePeriod $endPeriod): float
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();
        $days = 0.0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $value = 1.0;
            if ($day->equalTo($start) && $startPeriod === LeavePeriod::Afternoon) {
                $value -= 0.5; // the morning of the first day is worked
            }
            if ($day->equalTo($end) && $endPeriod === LeavePeriod::Morning) {
                $value -= 0.5; // the afternoon of the last day is worked
            }

            $days += max(0, $value);
        }

        return $days;
    }
}
