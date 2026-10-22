<?php

namespace Tests\Feature\Account;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bell: a full round trip through the real (database) notifications.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_round_trip_request_then_answer(): void
    {
        $sam = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam', 'last_name' => 'Lee']);
        $samLogin = User::factory()->create(['employee_id' => $sam->id]);
        $boss = Employee::factory()->create(['department_id' => $sam->department_id]);
        $manager = User::factory()->manager()->create(['name' => 'Mona Boss', 'employee_id' => $boss->id]);

        // 1. Sam asks for Monday afternoon → Tuesday.
        $this->actingAs($samLogin)->post(route('leave-requests.store'), [
            'start_date' => '2026-11-02', 'start_period' => 'afternoon', 'end_date' => '2026-11-03', 'end_period' => 'afternoon', 'reason' => 'Trip',
        ])->assertRedirect();
        $leave = LeaveRequest::sole();
        $this->assertSame(1.5, $leave->days);

        // 2. The manager's bell shows it.
        $this->assertSame(1, $manager->unreadNotifications()->count());
        $this->actingAs($manager)->get(route('leave-requests.index'))
            ->assertSee('New leave request')
            ->assertSee('Sam Lee asked for 1.5 days: 02 Nov 2026 (afternoon) → 03 Nov 2026.');

        // 3. Clicking it marks it read and opens the request.
        $notification = $manager->notifications()->sole();
        $this->get(route('notifications.open', $notification->id))->assertRedirect(route('leave-requests.show', $leave));
        $this->assertNotNull($notification->fresh()->read_at);

        // 4. Approve → Sam is told.
        $this->post(route('leave-requests.approve', $leave))->assertRedirect();
        $this->actingAs($samLogin)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Leave request approved')
            ->assertSee('was approved by Mona Boss');
    }

    public function test_nobody_can_open_someone_elses_notification(): void
    {
        $owner = User::factory()->hr()->create();
        $owner->notify(new \App\Notifications\LeaveRequestSubmitted(LeaveRequest::factory()->create()));
        $notification = $owner->notifications()->sole();

        $this->actingAs(User::factory()->hr()->create())
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_every_url_starts_with_the_users_role(): void
    {
        $expected = [
            'admin' => '/admin/leave-requests',
            'hr' => '/hr/leave-requests',
            'manager' => '/manager/leave-requests',
            'employee' => '/employee/leave-requests',
        ];

        foreach ($expected as $role => $path) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertSame(url($path), route('leave-requests.index'));
            $this->assertSame(url("/{$role}/account"), route('account.edit'));
            $this->assertSame(url("/{$role}/dashboard"), route('dashboard'));
            $this->get(route('account.edit'))->assertOk();
        }
    }

    public function test_another_roles_prefix_is_a_404(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $this->get('/manager/account')->assertOk();
        $this->get('/employee/account')->assertNotFound();
        $this->get('/hr/leave-requests')->assertNotFound();
        $this->get('/admin/dashboard')->assertNotFound();
    }

    public function test_old_addresses_without_a_prefix_redirect_to_the_users_own(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        $this->get('/employees')->assertStatus(301)->assertRedirect(url('/hr/employees'));
        $this->get('/employees/5/edit?x=1')->assertRedirect(url('/hr/employees/5/edit?x=1'));
        $this->get('/leave-requests/5')->assertRedirect(url('/hr/leave-requests/5'));
        $this->get('/account')->assertRedirect(url('/hr/account'));

        $this->actingAs(User::factory()->create(['role' => 'employee']));
        $this->get('/my-profile')->assertRedirect(url('/employee/my-profile'));
    }

    public function test_the_manager_dashboard_is_the_personal_one(): void
    {
        $boss = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Mona']);
        $this->actingAs(User::factory()->manager()->create(['employee_id' => $boss->id]));

        $this->get('/manager/dashboard')->assertOk()->assertSee('Welcome, Mona')->assertDontSee('Roles &amp; permissions', false);
    }

    public function test_a_notification_saved_with_the_old_url_still_opens_the_request(): void
    {
        $user = User::factory()->hr()->create();
        $leave = LeaveRequest::factory()->create();
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\LeaveRequestSubmitted::class,
            'data' => ['title' => 'New leave request', 'url' => "/leave-requests/{$leave->id}", 'leave_request_id' => $leave->id],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $user->notifications()->sole()->id))
            ->assertRedirect(route('leave-requests.show', $leave));
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->hr()->create();
        LeaveRequest::factory()->count(3)->create()->each(fn ($leave) => $user->notify(new \App\Notifications\LeaveRequestSubmitted($leave)));
        $this->actingAs($user);

        $this->get(route('notifications.index'))->assertSee('Mark all as read');
        $this->from(route('notifications.index'))->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
