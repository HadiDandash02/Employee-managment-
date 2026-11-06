<?php

namespace Database\Factories;

use App\Enums\LeavePeriod;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\User;
use App\Services\Leave\LeaveDayCounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Full days by default; "days" is counted from the dates unless given.
     *
     * The dates stay inside the CURRENT year: a leave in next year only has the 3 base days earned so far,
     * so approving it in a test would fail on the balance rule (late in the year that happened at random).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween(now()->startOfYear(), now()->endOfYear()->subDays(7));

        return [
            'employee_id' => Employee::factory(),
            'start_date' => $start->format('Y-m-d'),
            'start_period' => LeavePeriod::Morning,
            'end_date' => (clone $start)->modify('+'.fake()->numberBetween(0, 6).' days')->format('Y-m-d'),
            'end_period' => LeavePeriod::Afternoon,
            'days' => function (array $attributes) {
                $period = fn ($value) => $value instanceof LeavePeriod ? $value : LeavePeriod::from($value);

                return app(LeaveDayCounter::class)->count(
                    $attributes['start_date'], $period($attributes['start_period']),
                    $attributes['end_date'], $period($attributes['end_period']),
                );
            },
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Approved,
            'approved_by' => User::factory()->hr(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Rejected,
            'approved_by' => User::factory()->hr(),
            'approved_at' => now(),
        ]);
    }
}
