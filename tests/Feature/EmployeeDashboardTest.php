<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckEmployeeStatus;
use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Models\Skill;
use App\Models\User;
use App\Services\EmployeeDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(array $attributes = []): User
    {
        $employee = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Olivia']);

        return User::factory()->create(['role' => 'employee', 'employee_id' => $employee->id, ...$attributes]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('employee.dashboard'))->assertRedirect(route('login'));
    }

    public function test_active_employee_sees_the_dashboard(): void
    {
        $this->actingAs($this->employeeUser());

        $this->assertSame(url('/employee/dashboard'), route('employee.dashboard'));
        $this->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Welcome, Olivia')
            ->assertSee('Sales')
            ->assertSee('My Profile');
    }

    public function test_suspended_employee_gets_403(): void
    {
        $this->actingAs($this->employeeUser(['is_active' => false]));

        $this->get(route('employee.dashboard'))->assertForbidden()->assertSee('Your account is suspended');
        $this->get(route('profile.show'))->assertForbidden();
        $this->get(route('account.edit'))->assertOk(); // can still change their password
    }

    public function test_employee_logs_in_to_the_dashboard(): void
    {
        $this->employeeUser(['username' => 'olivia', 'password' => 'secret-pass']);

        $this->post(route('login.store'), ['login' => 'olivia', 'password' => 'secret-pass'])
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_users_without_the_permission_cannot_open_it(): void
    {
        // Admin has every permission; this just checks the route is protected by "can:" too.
        $this->actingAs(User::factory()->create(['role' => 'unknown-role']));

        $this->get(route('employee.dashboard'))->assertForbidden();
    }

    public function test_middleware_blocks_inactive_and_lets_active_users_through(): void
    {
        $middleware = new CheckEmployeeStatus;
        $next = fn () => response('passed');

        $active = Request::create('/employee/dashboard');
        $active->setUserResolver(fn () => User::factory()->make(['is_active' => true]));
        $this->assertSame('passed', $middleware->handle($active, $next)->getContent());

        $inactive = Request::create('/employee/dashboard');
        $inactive->setUserResolver(fn () => User::factory()->make(['is_active' => false]));
        try {
            $middleware->handle($inactive, $next);
            $this->fail('Inactive user was not blocked.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_service_is_injected_by_the_container_and_builds_the_summary(): void
    {
        $employee = Employee::factory()->inDepartment('Sales')->create(['date_of_birth' => now()->subYears(30)->toDateString()]);
        Employee::factory()->inDepartment('Sales')->create();
        EmployeeProfile::factory()->for($employee)->create(['hire_date' => now()->subYears(4)->subDay()->toDateString()]);
        $employee->skills()->attach(Skill::factory(2)->create());

        $summary = app(EmployeeDashboardService::class)->summaryFor($employee);

        $this->assertSame(4, $summary['years_of_service']);
        $this->assertSame(0, $summary['days_to_birthday']); // birthday is today
        $this->assertSame(2, $summary['department_size']);
        $this->assertSame(2, $summary['skills']);
    }

    public function test_admin_can_suspend_and_reactivate_a_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = $this->employeeUser();
        $data = fn (bool $active) => [
            'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'role' => 'employee', 'employee_id' => $user->employee_id, 'is_active' => $active ? '1' : '0',
        ];

        $this->put(route('admin.users.update', $user), $data(false))->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
        $row = collect($this->getJson(route('admin.users.data'))->json('data'))->firstWhere('id', $user->id);
        $this->assertFalse($row['is_active']); // shown as "Suspended" in Manage Users

        $this->put(route('admin.users.update', $user), $data(true))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_you_cannot_suspend_yourself(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email,
            'role' => 'admin', 'is_active' => '0',
        ])->assertSessionHasErrors(['is_active' => 'You cannot suspend your own account.']);

        $this->assertTrue($admin->fresh()->is_active);
    }
}
