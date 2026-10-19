<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'department_id' => Department::factory(),
            'salary' => fake()->randomFloat(2, 30000, 150000),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
        ];
    }

    /**
     * Put the employee in a department with this name (one shared "Test Company · Head Office" branch),
     * so several employees can share a department: Employee::factory()->inDepartment('Sales').
     */
    public function inDepartment(string $name): static
    {
        return $this->state(function () use ($name) {
            $company = Company::firstOrCreate(['name' => 'Test Company']);
            $branch = Branch::firstOrCreate(['company_id' => $company->id, 'name' => 'Head Office']);

            return ['department_id' => Department::firstOrCreate(['branch_id' => $branch->id, 'name' => $name])->id];
        });
    }

    /**
     * Also create a profile (hasOne) and attach 1–3 existing skills (belongsToMany).
     */
    public function withDetails(): static
    {
        return $this->afterCreating(function (Employee $employee) {
            EmployeeProfile::factory()->for($employee)->create();

            $skills = Skill::inRandomOrder()->limit(fake()->numberBetween(1, 3))->pluck('id');
            $employee->skills()->attach(
                $skills->mapWithKeys(fn ($id) => [$id => ['level' => fake()->randomElement(['beginner', 'intermediate', 'expert'])]])
            );
        });
    }
}
