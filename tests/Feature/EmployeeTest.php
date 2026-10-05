<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'department' => 'Engineering',
            'salary' => '55000.50',
            'date_of_birth' => '1990-05-15',
        ], $overrides);
    }

    public function test_index_is_paginated(): void
    {
        Employee::factory(15)->create();

        $response = $this->get(route('employees.index'));

        $response->assertOk();
        $this->assertCount(10, $response->viewData('employees'));
        $this->assertSame(15, $response->viewData('employees')->total());
    }

    public function test_index_can_be_searched(): void
    {
        Employee::factory()->create(['first_name' => 'Zelda', 'department' => 'Sales']);
        Employee::factory()->create(['first_name' => 'Bob', 'department' => 'Finance']);

        $this->get(route('employees.index', ['search' => 'zeld']))
            ->assertOk()
            ->assertSee('Zelda')
            ->assertDontSee('Bob');

        $this->get(route('employees.index', ['search' => 'Finance']))
            ->assertSee('Bob')
            ->assertDontSee('Zelda');
    }

    public function test_employee_can_be_created(): void
    {
        $this->post(route('employees.store'), $this->validData())
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['email' => 'jane@example.com', 'department' => 'Engineering']);
    }

    public function test_store_validates_input(): void
    {
        Employee::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('employees.store'), [
            'email' => 'taken@example.com',
            'salary' => -5,
            'date_of_birth' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'department', 'salary', 'date_of_birth']);

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_employee_can_be_viewed_and_edited(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('employees.show', $employee))->assertOk()->assertSee($employee->email);
        $this->get(route('employees.edit', $employee))->assertOk()->assertSee($employee->email);
    }

    public function test_employee_can_be_updated_keeping_same_email(): void
    {
        $employee = Employee::factory()->create(['email' => 'jane@example.com']);

        $this->put(route('employees.update', $employee), $this->validData(['last_name' => 'Smith']))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Smith', $employee->fresh()->last_name);
    }

    public function test_employee_can_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $this->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertModelMissing($employee);
    }
}
