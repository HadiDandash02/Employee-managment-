<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('employees.index'))->assertRedirect(route('login'));
        $this->get(url('/admin'))->assertRedirect(route('login'));
        $this->getJson(route('employees.data'))->assertUnauthorized();
    }

    public function test_login_page_is_shown(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
    }

    public function test_user_can_log_in_with_username_or_email(): void
    {
        $user = User::factory()->manager()->create(['username' => 'jane', 'email' => 'jane@example.com', 'password' => 'secret-pass']);

        $this->post(route('login.store'), ['login' => 'jane', 'password' => 'secret-pass'])
            ->assertRedirect(route('employees.index'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), ['login' => 'jane@example.com', 'password' => 'secret-pass'])
            ->assertRedirect(route('employees.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'jane', 'password' => 'secret-pass']);

        $this->from(route('login'))
            ->post(route('login.store'), ['login' => 'jane', 'password' => 'wrong'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        User::factory()->create(['username' => 'jane', 'password' => 'secret-pass']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['login' => 'jane', 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['login' => 'jane', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('login');
        $this->assertStringStartsWith('Too many login attempts', session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_logged_in_user_visiting_login_goes_to_start_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_admin_seeder_creates_admin_with_all_permissions(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class); // safe to run twice

        $admin = User::where('username', 'admin')->sole();

        $this->assertSame('admin', $admin->role);
        foreach (array_keys(config('permissions.permissions')) as $permission) {
            $this->assertTrue($admin->can($permission), "admin should have {$permission}");
        }

        $this->post(route('login.store'), ['login' => 'admin', 'password' => 'Admin@12345'])
            ->assertRedirect(url('/admin/dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_start_page_is_the_dashboard_for_admin_and_hr(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(url('/admin/dashboard'));
        $this->actingAs(User::factory()->hr()->create())->get('/')->assertRedirect(url('/hr/dashboard'));
        $this->actingAs(User::factory()->manager()->create())->get('/')->assertRedirect(route('employees.index'));
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('profile.show')); // Employee role
    }

    public function test_hr_logs_in_to_the_hr_dashboard(): void
    {
        User::factory()->hr()->create(['username' => 'helen', 'password' => 'secret-pass']);

        $this->post(route('login.store'), ['login' => 'helen', 'password' => 'secret-pass'])
            ->assertRedirect(url('/hr/dashboard'));
        $this->get(url('/hr/dashboard'))->assertOk()->assertSee('HR Dashboard');
    }

    public function test_admin_cannot_open_the_hr_prefix(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(url('/admin'))->assertRedirect(url('/admin/dashboard'));
        $this->get(url('/admin/dashboard'))->assertOk()->assertSee('Admin Dashboard');
        $this->get(url('/hr'))->assertNotFound();
    }

    public function test_employee_role_sees_only_their_own_profile(): void
    {
        $employee = Employee::factory()->create();
        $own = Employee::factory()->create(['first_name' => 'Olivia', 'last_name' => 'Own']);
        $this->actingAs(User::factory()->create(['role' => 'employee', 'employee_id' => $own->id]));

        $this->get(route('profile.show'))->assertOk()->assertSee('Olivia Own')->assertDontSee($employee->email);
        $this->get(route('employees.index'))->assertForbidden();
        $this->getJson(route('employees.data'))->assertForbidden();
        $this->get(route('employees.show', $own))->assertForbidden();
        $this->get(route('employees.show', $employee))->assertForbidden();
        $this->get(route('employees.create'))->assertForbidden();
        $this->get(route('employees.edit', $employee))->assertForbidden();
        $this->put(route('employees.update', $employee), [])->assertForbidden();
        $this->delete(route('employees.destroy', $employee))->assertForbidden();
        $this->deleteJson(route('employees.bulk-destroy'), ['ids' => [$employee->id]])->assertForbidden();
        $this->get(url('/admin'))->assertNotFound();

        $this->assertModelExists($employee);
    }

    public function test_manager_can_add_and_edit_but_not_delete(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs(User::factory()->manager()->create());

        $this->get(route('employees.create'))->assertOk();
        $this->get(route('employees.edit', $employee))->assertOk()->assertDontSee('Danger zone');
        $this->delete(route('employees.destroy', $employee))->assertForbidden();
        $this->get(url('/admin'))->assertNotFound();

        $this->assertModelExists($employee);
    }
}
