<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeRoleTest extends TestCase
{
    use RefreshDatabase;

    private function userData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Olivia Own',
            'username' => 'olivia',
            'email' => 'olivia@example.com',
            'role' => 'employee',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_employee_logs_in_to_their_own_profile(): void
    {
        $employee = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Olivia', 'last_name' => 'Own', 'salary' => 48000]);
        User::factory()->create(['username' => 'olivia', 'password' => 'secret-pass', 'role' => 'employee', 'employee_id' => $employee->id]);

        $this->post(route('login.store'), ['login' => 'olivia', 'password' => 'secret-pass'])
            ->assertRedirect(route('profile.show'));

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Olivia Own')
            ->assertSee('Sales')
            ->assertSee('48,000.00')
            // privacy: no department average or colleagues on the employee's own page
            ->assertDontSee('department average')
            ->assertDontSee('Colleagues');
    }

    public function test_unlinked_employee_login_sees_a_message(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employee']));

        $this->get(route('profile.show'))->assertOk()->assertSee('not linked to an employee record');
    }

    public function test_employee_cannot_open_other_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employee', 'employee_id' => Employee::factory()->create()->id]));

        $this->get(route('employees.index'))->assertForbidden();
        $this->get(url('/admin/dashboard'))->assertNotFound();
        $this->get(url('/admin/users'))->assertNotFound();
        $this->get(url('/admin/departments'))->assertNotFound();
        $this->get(route('account.edit'))->assertOk(); // can still change their own password
    }

    public function test_admin_creates_an_employee_login_linked_to_an_employee(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $employee = Employee::factory()->create(['first_name' => 'Olivia', 'last_name' => 'Own']);

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Linked employee')
            ->assertSee('<option value="employee" selected>', false); // Employee is the default role

        $this->post(route('admin.users.store'), $this->userData(['employee_id' => $employee->id]))
            ->assertSessionHasNoErrors();

        $user = User::where('username', 'olivia')->sole();
        $this->assertSame('employee', $user->role);
        $this->assertTrue($user->employee->is($employee));
        $this->assertTrue($employee->user->is($user));

        $row = $this->getJson(route('admin.users.data', ['search' => ['value' => 'olivia']]))->json('data.0');
        $this->assertSame('Olivia Own', $row['employee']);
    }

    public function test_employee_role_requires_a_linked_employee(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.users.store'), $this->userData())
            ->assertSessionHasErrors(['employee_id' => 'Please choose the employee this login belongs to.']);

        // Other roles may stay unlinked.
        $this->post(route('admin.users.store'), $this->userData(['role' => 'manager']))->assertSessionHasNoErrors();
    }

    public function test_one_login_per_employee(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $employee = Employee::factory()->create();
        User::factory()->create(['employee_id' => $employee->id]);

        $this->post(route('admin.users.store'), $this->userData(['employee_id' => $employee->id]))
            ->assertSessionHasErrors(['employee_id' => 'This employee already has a login.']);

        // already-linked employees are not offered in the dropdown
        $this->get(route('admin.users.create'))->assertDontSee('<option value="'.$employee->id.'"', false);
    }

    public function test_hr_can_create_employee_logins(): void
    {
        $this->actingAs(User::factory()->hr()->create());
        $employee = Employee::factory()->create();

        $this->post(route('admin.users.store'), $this->userData(['employee_id' => $employee->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame('employee', User::where('username', 'olivia')->value('role'));
    }

    public function test_linked_staff_also_see_my_profile_in_the_menu(): void
    {
        $linked = User::factory()->manager()->create(['employee_id' => Employee::factory()->create()->id]);
        $unlinked = User::factory()->manager()->create();

        $sidebar = fn (User $user) => Str::between($this->actingAs($user)->get(route('employees.index'))->getContent(), '<aside', '</aside>');

        $this->assertStringContainsString('My Profile', $sidebar($linked));
        $this->assertStringNotContainsString('My Profile', $sidebar($unlinked));
    }

    public function test_permanently_deleting_the_employee_unlinks_the_login(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $employee->forceDelete();

        $this->assertModelExists($user);
        $this->assertNull($user->fresh()->employee_id); // nullOnDelete
    }
}
