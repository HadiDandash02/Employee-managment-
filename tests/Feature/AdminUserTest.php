<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Main Admin']);
        $this->actingAs($this->admin);
    }

    public function test_admin_dashboard_shows_stats_users_and_permissions(): void
    {
        Employee::factory(3)->inDepartment('Sales')->create();
        User::factory()->create(['name' => 'Vera Viewer']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Main Admin')
            ->assertSee('Vera Viewer')
            ->assertSee('Sales')
            ->assertSee('Manage users')->assertSee('Admin Dashboard')
            ->assertViewHas('stats', fn ($stats) => $stats['employees'] === 3 && $stats['users'] === 2);
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'New Manager',
            'username' => 'new_manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('username', 'new_manager')->sole();
        $this->assertSame('manager', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_create_user_validates_input(): void
    {
        User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);

        $this->post(route('admin.users.store'), [
            'username' => 'taken',
            'email' => 'taken@example.com',
            'role' => 'superuser',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'username', 'email', 'role', 'password']);
    }

    public function test_admin_can_update_a_user_and_keep_the_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Renamed',
            'username' => $user->username,
            'email' => $user->email,
            'role' => 'manager',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertSame('manager', $user->role);
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $this->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'role' => 'viewer',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_can_delete_other_users_but_not_themselves(): void
    {
        $user = User::factory()->create();

        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($user);

        $this->delete(route('admin.users.destroy', $this->admin))->assertSessionHasErrors('user');
        $this->assertModelExists($this->admin);
    }

    public function test_hr_has_admin_permissions_but_cannot_see_admins(): void
    {
        $hr = User::factory()->hr()->create(['name' => 'Helen HR']);
        User::factory()->manager()->create(['name' => 'Mona Manager']);
        $this->actingAs($hr);

        foreach (array_keys(config('permissions.permissions')) as $permission) {
            $this->assertTrue($hr->can($permission), "HR should have {$permission}");
        }

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Helen HR')
            ->assertSee('Mona Manager')
            ->assertDontSee('Main Admin')
            ->assertViewHas('stats', fn ($stats) => $stats['users'] === 2)
            ->assertViewHas('roles', fn ($roles) => ! array_key_exists('admin', $roles));

        $this->get(route('admin.users.edit', $this->admin))->assertNotFound();
        $this->put(route('admin.users.update', $this->admin), ['name' => 'Hacked'])->assertNotFound();
        $this->delete(route('admin.users.destroy', $this->admin))->assertNotFound();
        $this->assertSame('Main Admin', $this->admin->fresh()->name);
    }

    public function test_hr_pages_never_mention_admin(): void
    {
        $hr = User::factory()->hr()->create();
        $viewer = User::factory()->create();
        $this->actingAs($hr);

        $this->assertSame(url('/hr/dashboard'), route('admin.dashboard'));
        $this->get(url('/admin'))->assertNotFound();
        $this->get(url('/admin/users/create'))->assertNotFound();

        foreach ([route('admin.dashboard'), route('admin.users.create'), route('admin.users.edit', $viewer), route('employees.index'), route('account.edit')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Admin');
        }

        $this->get(route('admin.dashboard'))->assertSee('HR Dashboard');
    }

    public function test_sidebar_shows_profile_and_role_based_menu(): void
    {
        $html = $this->get(route('account.edit'))->assertSee('id="change-password"', false)->getContent();

        // Sidebar: app name at the top, then the menu. No "Add Employee" and no profile in it.
        $sidebar = Str::between($html, '<aside', '</aside>');
        $this->assertMatchesRegularExpression('/Employee Management.*Dashboard.*Manage Employees.*Manage Users/s', $sidebar);
        $this->assertStringNotContainsString('Add Employee', $sidebar);
        $this->assertStringNotContainsString('Log out', $sidebar);

        // Top bar, right side: today's date, then the profile with its menu:
        // Add User + "My account & password" submenu (My account, Change password) + Log out.
        $topbar = Str::between($html, '<header class="topbar">', '</header>');
        $this->assertMatchesRegularExpression(
            '/'.preg_quote(now()->format('l, d M Y'), '/').'.*Main Admin.*Add User.*My account &amp; password.*My account.*Change password.*Log out/s',
            $topbar
        );

        $this->actingAs(User::factory()->create(['name' => 'Vic Viewer']))
            ->get(route('employees.index'))
            ->assertSee('Vic Viewer')
            ->assertSee('Viewer')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Add User')
            ->assertDontSee('Add Employee');
    }

    public function test_hr_cannot_give_the_admin_role(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        $this->get(route('admin.users.create'))->assertOk()->assertDontSee('value="admin"', false);

        $this->post(route('admin.users.store'), [
            'name' => 'Sneaky',
            'username' => 'sneaky',
            'email' => 'sneaky@example.com',
            'role' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'sneaky']);
    }

    public function test_hr_can_manage_hr_manager_and_viewer_users(): void
    {
        $this->actingAs(User::factory()->hr()->create());
        $viewer = User::factory()->create();

        $this->post(route('admin.users.store'), [
            'name' => 'Second HR',
            'username' => 'hr2',
            'email' => 'hr2@example.com',
            'role' => 'hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        $this->put(route('admin.users.update', $viewer), [
            'name' => $viewer->name,
            'username' => $viewer->username,
            'email' => $viewer->email,
            'role' => 'manager',
        ])->assertSessionHasNoErrors();

        $this->assertSame('hr', User::where('username', 'hr2')->value('role'));
        $this->assertSame('manager', $viewer->fresh()->role);

        $this->delete(route('admin.users.destroy', $viewer))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($viewer);
    }

    public function test_admin_sees_and_manages_hr_users(): void
    {
        $hr = User::factory()->hr()->create(['name' => 'Helen HR']);

        $this->get(route('admin.dashboard'))
            ->assertSee('Helen HR')
            ->assertViewHas('roles', fn ($roles) => array_keys($roles) === ['admin', 'hr', 'manager', 'viewer']);
        $this->get(route('admin.users.edit', $hr))->assertOk();
    }

    public function test_another_admin_can_be_demoted_but_not_the_last_one(): void
    {
        $other = User::factory()->admin()->create();

        $this->put(route('admin.users.update', $other), [
            'name' => $other->name,
            'username' => $other->username,
            'email' => $other->email,
            'role' => 'viewer',
        ])->assertSessionHasNoErrors();

        $this->assertSame('viewer', $other->fresh()->role);
    }
}
