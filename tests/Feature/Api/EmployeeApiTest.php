<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Week 4 Day 2: the REST API at /api/employees (Sanctum token, JSON in and out).
 */
class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The fields of one employee in every API answer (App\Http\Resources\EmployeeResource).
     */
    private const EMPLOYEE_FIELDS = ['id', 'full_name', 'email', 'department', 'salary', 'created_at'];

    private function validData(array $overrides = []): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department_id' => Department::factory()->create(['name' => 'Finance'])->id,
            'salary' => 4200,
            'date_of_birth' => '1995-06-15',
            ...$overrides,
        ];
    }

    public function test_guests_get_401_json_not_a_redirect(): void
    {
        $this->getJson('/api/employees')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        // Even without the "Accept: application/json" header.
        $this->get('/api/employees')->assertUnauthorized();
    }

    public function test_index_returns_a_paginated_json_collection(): void
    {
        Employee::factory()->count(12)->create();
        Sanctum::actingAs(User::factory()->hr()->create());

        $response = $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => ['*' => self::EMPLOYEE_FIELDS],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.per_page', 10);

        // Only the chosen fields: nothing internal leaks out.
        $this->assertSame(self::EMPLOYEE_FIELDS, array_keys($response->json('data.0')));

        $this->getJson('/api/employees?page=2')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_show_returns_one_employee_with_the_department_name(): void
    {
        $employee = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam', 'last_name' => 'Lee', 'salary' => 3100]);
        Sanctum::actingAs(User::factory()->hr()->create());

        $this->getJson("/api/employees/{$employee->id}")
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $employee->id,
                'full_name' => 'Sam Lee',
                'email' => $employee->email,
                'department' => 'Sales',
                'salary' => 3100,
                'created_at' => $employee->created_at->toIso8601String(),
            ]]);

        $this->getJson('/api/employees/999')->assertNotFound();
    }

    public function test_hr_can_create_an_employee(): void
    {
        Sanctum::actingAs(User::factory()->hr()->create());

        $this->postJson('/api/employees', $this->validData())
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Jane Doe')
            ->assertJsonPath('data.department', 'Finance');

        $this->assertDatabaseHas('employees', ['email' => 'jane.doe@example.com', 'first_name' => 'Jane']);
    }

    public function test_invalid_data_returns_422_with_the_errors(): void
    {
        Employee::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs(User::factory()->hr()->create());

        $this->postJson('/api/employees', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'department_id', 'salary', 'date_of_birth']);

        $this->postJson('/api/employees', $this->validData(['email' => 'not-an-email', 'salary' => -5, 'department_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'salary', 'department_id']);

        $this->postJson('/api/employees', $this->validData(['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Another employee already uses this email.']);

        // A plain form post (no JSON header) to the API is still a 422, never a redirect.
        $this->post('/api/employees', [])->assertUnprocessable();

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_hr_can_update_and_delete_an_employee(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Old', 'salary' => 3000]);
        Sanctum::actingAs(User::factory()->hr()->create());

        // Partial update: only the sent fields change.
        $this->putJson("/api/employees/{$employee->id}", ['first_name' => 'New', 'salary' => 3500])
            ->assertOk()
            ->assertJsonPath('data.full_name', "New {$employee->last_name}")
            ->assertJsonPath('data.salary', 3500);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'first_name' => 'New', 'salary' => 3500]);

        $this->putJson("/api/employees/{$employee->id}", ['email' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->deleteJson("/api/employees/{$employee->id}")->assertNoContent();
        $this->assertSoftDeleted($employee);
        $this->getJson("/api/employees/{$employee->id}")->assertNotFound();
    }

    public function test_manager_sees_only_their_department_and_cannot_change_anyone(): void
    {
        $own = Employee::factory()->inDepartment('Sales')->create();
        $teammate = Employee::factory()->create(['department_id' => $own->department_id]);
        $other = Employee::factory()->inDepartment('Support')->create(['salary' => 5000]);
        Sanctum::actingAs(User::factory()->manager()->create(['employee_id' => $own->id]));

        $ids = collect($this->getJson('/api/employees')->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
        $this->assertSame([$own->id, $teammate->id], $ids);

        $this->getJson("/api/employees/{$teammate->id}")->assertOk();
        $this->getJson("/api/employees/{$other->id}")->assertForbidden();

        // 403 comes before validation: an empty body is refused, not answered with a list of errors.
        $this->postJson('/api/employees', [])->assertForbidden();
        $this->putJson("/api/employees/{$other->id}", ['salary' => 1])->assertForbidden();
        $this->deleteJson("/api/employees/{$teammate->id}")->assertForbidden();

        $this->assertDatabaseHas('employees', ['id' => $other->id, 'salary' => 5000]);
        $this->assertNotSoftDeleted($teammate);
    }

    public function test_employee_role_cannot_list_employees(): void
    {
        $own = Employee::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'employee', 'employee_id' => $own->id]));

        $this->getJson('/api/employees')->assertForbidden();
    }

    public function test_login_gives_a_token_that_opens_the_api(): void
    {
        User::factory()->hr()->create(['username' => 'helen', 'password' => 'secret-pass']);
        Employee::factory()->create();

        $this->postJson('/api/login', ['login' => 'helen', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');

        $token = $this->postJson('/api/login', ['login' => 'helen', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'username', 'role']])
            ->json('token');

        $this->withToken($token)->getJson('/api/employees')->assertOk()->assertJsonCount(1, 'data');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_suspended_users_get_no_token_and_no_access(): void
    {
        $user = User::factory()->hr()->suspended()->create(['username' => 'sus', 'password' => 'secret-pass']);

        $this->postJson('/api/login', ['login' => 'sus', 'password' => 'secret-pass'])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // A token made before the suspension stops working too ("active" middleware).
        Sanctum::actingAs($user);
        $this->getJson('/api/employees')->assertForbidden();
    }
}
