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
