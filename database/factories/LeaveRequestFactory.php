<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'employee_id' => Employee::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+'.fake()->numberBetween(0, 6).' days')->format('Y-m-d'),
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
