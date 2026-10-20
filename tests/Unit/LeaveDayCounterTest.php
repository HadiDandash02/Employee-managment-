<?php

namespace Tests\Unit;

use App\Enums\LeavePeriod;
use App\Services\Leave\LeaveDayCounter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pure calculation, no database: a plain unit test. 2026-11-02 is a Monday.
 */
class LeaveDayCounterTest extends TestCase
{
    public static function leaves(): array
    {
        $m = LeavePeriod::Morning;
        $a = LeavePeriod::Afternoon;

        return [
            'one full day' => ['2026-11-02', $m, '2026-11-02', $a, 1.0],
            'morning only' => ['2026-11-02', $m, '2026-11-02', $m, 0.5],
            'afternoon only' => ['2026-11-02', $a, '2026-11-02', $a, 0.5],
            'afternoon to morning, same day' => ['2026-11-02', $a, '2026-11-02', $m, 0.0],
            'Mon to Wed' => ['2026-11-02', $m, '2026-11-04', $a, 3.0],
            'Mon afternoon to Wed morning' => ['2026-11-02', $a, '2026-11-04', $m, 2.0],
            'Mon afternoon to Wed' => ['2026-11-02', $a, '2026-11-04', $a, 2.5],
            'Fri to Mon skips the weekend' => ['2026-11-06', $m, '2026-11-09', $a, 2.0],
            'a whole week' => ['2026-11-02', $m, '2026-11-08', $a, 5.0],
            'weekend only' => ['2026-11-07', $m, '2026-11-08', $a, 0.0],
        ];
    }

    #[DataProvider('leaves')]
    public function test_it_counts_working_days_in_half_days(string $start, LeavePeriod $startPeriod, string $end, LeavePeriod $endPeriod, float $expected): void
    {
        $this->assertSame($expected, (new LeaveDayCounter)->count($start, $startPeriod, $end, $endPeriod));
    }
}
