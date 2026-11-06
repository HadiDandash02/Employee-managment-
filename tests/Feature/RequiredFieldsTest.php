<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every field marked * in a form is also required by the SERVER, not only by the browser.
 * These requests skip the browser completely (like a hacker or a script would), with the fields empty.
 */
class RequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_add_and_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $department = Department::factory()->create(['name' => 'Keep', 'code' => 'KEEP-1']);

        $this->post(route('admin.departments.store'), [])
            ->assertSessionHasErrors(['name', 'code', 'branch_id']);

        $this->put(route('admin.departments.update', $department), ['name' => '', 'code' => '', 'branch_id' => ''])
            ->assertSessionHasErrors(['name', 'code', 'branch_id']);

        $this->assertSame(1, Department::count());
        $this->assertSame('Keep', $department->fresh()->name);
    }

    public function test_employee_add_and_edit(): void
    {
        $this->actingAs(User::factory()->hr()->create());
        $employee = Employee::factory()->create(['first_name' => 'Keep']);

        $this->post(route('employees.store'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'department_id', 'salary', 'date_of_birth']);

        // The edit form may send only some fields, but a sent required field can never be emptied.
        $this->put(route('employees.update', $employee), ['first_name' => '', 'last_name' => '', 'email' => '', 'department_id' => '', 'salary' => '', 'date_of_birth' => ''])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'department_id', 'salary', 'date_of_birth']);

        $this->assertSame('Keep', $employee->fresh()->first_name);
    }

    public function test_user_add_and_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create(['name' => 'Keep']);

        $this->post(route('admin.users.store'), [])
            ->assertSessionHasErrors(['name', 'username', 'email', 'roles', 'password']);

        $this->put(route('admin.users.update', $user), ['name' => '', 'username' => '', 'email' => '', 'roles' => []])
            ->assertSessionHasErrors(['name', 'username', 'email', 'roles']);

        $this->assertSame('Keep', $user->fresh()->name);
    }

    public function test_leave_request_and_rejection_comment(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'employee', 'employee_id' => $employee->id]));

        $this->post(route('leave-requests.store'), [])
            ->assertSessionHasErrors(['start_date', 'end_date', 'reason']);
    }

    public function test_change_password(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('account.password'), [])
            ->assertSessionHasErrorsIn('password', ['current_password', 'password']);
    }

    public function test_passwords_must_be_strong(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $form = fn (string $password) => ['name' => 'New Person', 'username' => 'new.person', 'email' => 'new@example.com', 'role' => 'hr',
            'password' => $password, 'password_confirmation' => $password];

        // Each weak password breaks at least one rule: length, uppercase, lowercase, number, special character.
        foreach (['Ab1!', 'password1!', 'PASSWORD1!', 'Password!!', 'Password12'] as $weak) {
            $this->post(route('admin.users.store'), $form($weak))->assertSessionHasErrors('password');
        }
        $this->assertDatabaseMissing('users', ['username' => 'new.person']);

        $this->post(route('admin.users.store'), $form('Strong#Pass1'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'new.person']);
    }
}
