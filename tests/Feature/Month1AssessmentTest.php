<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Week 4 Day 1: the Month 1 assessment, five feature tests for the employee module.
 */
class Month1AssessmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid "Add employee" form, with the given fields changed.
     */
    private function validData(array $overrides = []): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department_id' => Department::factory()->create()->id,
            'salary' => 4200,
            'date_of_birth' => '1995-06-15',
            ...$overrides,
        ];
    }

    public function test_employee_list_is_accessible_to_admin_hr_and_manager(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->hr()->create(), User::factory()->manager()->create()] as $user) {
            $this->actingAs($user);

            $this->get(route('employees.index'))->assertOk()->assertSee('Employees');
            // The rows are loaded by DataTables from this JSON endpoint.
            $this->getJson(route('employees.data'))->assertOk()->assertJsonStructure(['data', 'recordsTotal']);
        }
    }

    public function test_employee_can_be_created(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        $this->post(route('employees.store'), $this->validData())
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', ['email' => 'jane.doe@example.com', 'first_name' => 'Jane', 'last_name' => 'Doe']);
    }

    public function test_validation_rejects_invalid_email_data(): void
    {
        $existing = Employee::factory()->create(['email' => 'taken@example.com']);
        $this->actingAs(User::factory()->hr()->create());

        $cases = [
            'missing email' => ['email' => ''],
            'invalid email' => ['email' => 'not-an-email'],
            'duplicate email' => ['email' => $existing->email],
        ];

        foreach ($cases as $case => $overrides) {
            $this->from(route('employees.create'))
                ->post(route('employees.store'), $this->validData($overrides))
                ->assertRedirect(route('employees.create'))
                ->assertSessionHasErrors('email', "No email error for: {$case}");
        }

        // Nothing new was saved: only the existing employee is there.
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_employee_can_be_updated(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Old', 'salary' => 3000]);
        $this->actingAs(User::factory()->hr()->create());

        $this->put(route('employees.update', $employee), ['first_name' => 'New', 'salary' => 3500])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'first_name' => 'New', 'salary' => 3500]);
    }

    public function test_manager_cannot_update_employee_of_another_department(): void
    {
        $own = Employee::factory()->inDepartment('Sales')->create();
        $target = Employee::factory()->inDepartment('Support')->create(['salary' => 5000]);
        $this->actingAs(User::factory()->manager()->create(['employee_id' => $own->id]));

        $this->get(route('employees.edit', $target))->assertForbidden();
        $this->put(route('employees.update', $target), ['salary' => 1])->assertForbidden();

        $this->assertDatabaseHas('employees', ['id' => $target->id, 'salary' => 5000]);
    }
}
