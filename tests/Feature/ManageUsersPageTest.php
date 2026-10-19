<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageUsersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Main Admin', 'username' => 'mainadmin']);
        $this->actingAs($this->admin);
    }

    /**
     * Call the users DataTables endpoint the way the browser does.
     */
    private function usersData(array $params = [])
    {
        $columns = collect(['', 'name', 'role', '', ''])
            ->map(fn ($name, $i) => ['data' => $i, 'name' => $name, 'orderable' => in_array($i, [1, 2]) ? 'true' : 'false'])
            ->all();

        return $this->getJson(route('admin.users.data', array_replace_recursive([
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns,
        ], $params)));
    }

    private function names($response): array
    {
        return array_column($response->json('data'), 'name');
    }

    public function test_users_page_is_at_admin_users_with_filters(): void
    {
        $this->assertSame(url('/admin/users'), route('admin.users.index'));

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manage Users')
            ->assertSee('Generate Report')
            ->assertSee('<option value="hr">HR</option>', false)
            ->assertSeeInOrder(['<th>Name</th>', '<th>Role</th>', '<th>Password</th>', '<th>Actions</th>'], false);
    }

    public function test_users_can_be_filtered_by_name_and_role(): void
    {
        User::factory()->hr()->create(['name' => 'Helen Hart']);
        User::factory()->manager()->create(['name' => 'Mona Hart']);
        User::factory()->create(['name' => 'Victor Viewer']);

        $this->assertSame(['Helen Hart', 'Mona Hart'], $this->names($this->usersData(['filters' => ['name' => 'hart']])));
        $this->assertSame(['Mona Hart'], $this->names($this->usersData(['filters' => ['role' => 'manager']])));
        $this->assertSame(['Helen Hart'], $this->names($this->usersData(['filters' => ['name' => 'hart', 'role' => 'hr']])));
        $this->assertSame(['Main Admin'], $this->names($this->usersData(['filters' => ['name' => 'mainadmin']])));
        $this->assertSame(4, $this->usersData()->json('recordsTotal'));
    }

    public function test_users_can_be_sorted_by_role(): void
    {
        User::factory()->create(['name' => 'Eve Employee']);
        User::factory()->hr()->create(['name' => 'Helen HR']);

        // alphabetical by role: admin, employee, hr
        $this->assertSame(['Main Admin', 'Eve Employee', 'Helen HR'], $this->names($this->usersData(['order' => [['column' => 2, 'dir' => 'asc']]])));
    }

    public function test_rows_never_contain_passwords(): void
    {
        $row = $this->usersData()->json('data.0');

        $this->assertArrayNotHasKey('password', $row);
        $this->assertStringNotContainsString($this->admin->password, $this->usersData()->getContent());
        $this->assertStringEndsWith('#set-password', $row['urls']['password']);
    }

    public function test_cannot_delete_yourself_or_from_the_table(): void
    {
        $this->assertFalse($this->usersData()->json('data.0.can_delete'));

        $this->deleteJson(route('admin.users.destroy', $this->admin))
            ->assertStatus(422)
            ->assertJson(['message' => 'You cannot delete your own account.']);

        $other = User::factory()->create();
        $this->deleteJson(route('admin.users.destroy', $other))->assertOk();
        $this->assertModelMissing($other);
    }

    public function test_hr_users_page_is_at_hr_users_and_hides_admins(): void
    {
        User::factory()->manager()->create(['name' => 'Mona Manager']);
        $this->actingAs(User::factory()->hr()->create(['name' => 'Helen HR']));

        $this->assertSame(url('/hr/users'), route('admin.users.index'));
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('Admin');
        $this->get(url('/admin/users'))->assertNotFound();

        $this->assertSame(['Helen HR', 'Mona Manager'], $this->names($this->usersData()));
        $this->assertSame([], $this->names($this->usersData(['filters' => ['role' => 'admin']])));
        $this->assertSame([], $this->names($this->usersData(['filters' => ['name' => 'Main Admin']])));
        $this->assertSame(2, $this->usersData()->json('recordsTotal'));
    }

    public function test_viewer_cannot_open_users_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(url('/admin/users'))->assertNotFound();
        $this->getJson(url('/admin/users/data'))->assertNotFound();
    }

    public function test_menu_uses_manage_names(): void
    {
        $this->get(route('employees.index'))
            ->assertSee('Manage Employees')
            ->assertSee('Manage Users')
            ->assertSee(route('admin.users.index'), false);
    }
}
