<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A user can have several roles (users.roles). The highest one is the main role (users.role):
 * it decides the URL prefix and the dashboard; the permissions are those of ALL the roles together.
 */
class MultipleRolesTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return [
            'name' => 'Mona Two-Roles',
            'username' => 'mona',
            'email' => 'mona@example.com',
            'roles' => ['employee', 'manager'],
            'employee_id' => Employee::factory()->create()->id,
            'password' => 'Strong#Pass1',
            'password_confirmation' => 'Strong#Pass1',
            ...$overrides,
        ];
    }

    public function test_admin_gives_a_user_two_roles_and_the_highest_is_the_main_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.users.store'), $this->form())->assertSessionHasNoErrors();

        $mona = User::where('username', 'mona')->sole();
        $this->assertSame(['manager', 'employee'], $mona->roles);   // highest first
        $this->assertSame('manager', $mona->role);                  // the main role
        $this->assertSame('manager', $mona->url_prefix);            // /manager/...
        $this->assertSame(['Manager', 'Employee'], $mona->role_labels);
        $this->assertTrue($mona->hasRole('employee'));
        $this->assertTrue($mona->hasPermission('leave.request'));  // from Employee
        $this->assertTrue($mona->hasPermission('leave.approve'));  // from Manager
    }

    public function test_permissions_are_combined_from_every_role(): void
    {
        // An extra permission only the Employee role has: a Manager + Employee user must get it too.
        config(['permissions.roles.employee.permissions' => [...config('permissions.roles.employee.permissions'), 'canteen.order']]);
        $both = User::factory()->create(['roles' => ['manager', 'employee']]);
        $managerOnly = User::factory()->manager()->create();

        $this->assertTrue($both->hasPermission('canteen.order'));
        $this->assertFalse($managerOnly->hasPermission('canteen.order'));
    }

    public function test_the_edit_form_shows_every_role_and_saves_changes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create(['roles' => ['manager', 'employee'], 'employee_id' => Employee::factory()->create()->id]);

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('name="roles[]"', false)
            ->assertSee('<option value="manager" selected>', false)
            ->assertSee('<option value="employee" selected>', false);

        $this->put(route('admin.users.update', $user), [
            'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'roles' => ['hr', 'employee'], 'employee_id' => $user->employee_id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['hr', 'employee'], $user->fresh()->roles);
        $this->assertSame('hr', $user->fresh()->role);
    }

    public function test_the_role_rules_still_apply(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        // HR may not give the Admin role, not even as one of several.
        $this->post(route('admin.users.store'), $this->form(['roles' => ['employee', 'admin']]))
            ->assertSessionHasErrors('roles.1');
        // At least one role.
        $this->post(route('admin.users.store'), $this->form(['roles' => []]))->assertSessionHasErrors('roles');
        // Employee among the roles: the linked employee is required.
        $this->post(route('admin.users.store'), $this->form(['employee_id' => null]))->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('users', ['username' => 'mona']);
    }

    public function test_hr_cannot_see_or_edit_a_user_who_is_also_admin(): void
    {
        $adminAndEmployee = User::factory()->create(['roles' => ['employee', 'admin']]);
        $this->actingAs(User::factory()->hr()->create());

        $this->assertSame('admin', $adminAndEmployee->role);
        $this->get(route('admin.users.edit', $adminAndEmployee))->assertNotFound();
        $ids = collect($this->getJson(route('admin.users.data'))->json('data'))->pluck('id');
        $this->assertNotContains($adminAndEmployee->id, $ids);
    }

    public function test_the_table_shows_every_role_and_the_filter_matches_any_of_them(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $both = User::factory()->create(['name' => 'Both', 'roles' => ['manager', 'employee']]);
        User::factory()->manager()->create(['name' => 'Manager Only']);

        $row = collect($this->getJson(route('admin.users.data'))->json('data'))->firstWhere('id', $both->id);
        $this->assertSame([['key' => 'manager', 'label' => 'Manager'], ['key' => 'employee', 'label' => 'Employee']], $row['roles']);

        $employees = collect($this->getJson(route('admin.users.data', ['filters' => ['role' => 'employee']]))->json('data'))->pluck('name')->all();
        $this->assertSame(['Both'], $employees); // her main role is Manager, but she is also an Employee
    }

    public function test_existing_code_that_only_sets_role_keeps_roles_in_sync(): void
    {
        $user = User::factory()->hr()->create();
        $this->assertSame(['hr'], $user->roles);

        $user->update(['role' => 'manager']);
        $this->assertSame(['manager'], $user->fresh()->roles);
    }
}
