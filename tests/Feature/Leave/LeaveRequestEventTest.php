<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveAction;
use App\Enums\LeaveStatus;
use App\Events\LeaveRequestStatusChanged;
use App\Exceptions\BusinessRuleException;
use App\Listeners\LogLeaveRequestActivity;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The LeaveRequestStatusChanged event (app/Events) and its listener LogLeaveRequestActivity (app/Listeners).
 */
class LeaveRequestEventTest extends TestCase
{
    use RefreshDatabase;

    private LeaveRequestService $service;

    private Employee $sam;

    private User $samLogin;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-29 09:00:00');
        $this->service = app(LeaveRequestService::class);
        $this->sam = Employee::factory()->inDepartment('Sales')->create();
        $this->samLogin = User::factory()->create(['role' => 'employee', 'employee_id' => $this->sam->id]);
        $boss = Employee::factory()->create(['department_id' => $this->sam->department_id]);
        $this->manager = User::factory()->manager()->create(['employee_id' => $boss->id]);
    }

    private function pending(): LeaveRequest
    {
        return LeaveRequest::factory()->create(['employee_id' => $this->sam->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-03']);
    }

    public function test_the_listener_is_registered_automatically(): void
    {
        Event::fake();

        Event::assertListening(LeaveRequestStatusChanged::class, LogLeaveRequestActivity::class);
    }

    public function test_each_action_fires_the_event_with_who_did_it(): void
    {
        Event::fake([LeaveRequestStatusChanged::class]);

        $submitted = $this->service->submit($this->sam, ['start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'reason' => 'Dentist'], $this->samLogin);
        $this->service->approve($this->pending(), $this->manager);
        $this->service->reject($this->pending(), $this->manager, 'Busy week');
        $this->service->cancel($submitted, $this->samLogin);

        $fired = fn (LeaveAction $action, User $by) => Event::assertDispatched(
            LeaveRequestStatusChanged::class,
            fn (LeaveRequestStatusChanged $e) => $e->action === $action && $e->by->is($by),
        );
        $fired(LeaveAction::Submitted, $this->samLogin);
        $fired(LeaveAction::Approved, $this->manager);
        $fired(LeaveAction::Rejected, $this->manager);
        $fired(LeaveAction::Cancelled, $this->samLogin);
        Event::assertDispatchedTimes(LeaveRequestStatusChanged::class, 4);
    }

    public function test_no_event_when_a_rule_fails_and_nothing_is_saved(): void
    {
        Event::fake([LeaveRequestStatusChanged::class]);
        $leave = LeaveRequest::factory()->approved()->create(['employee_id' => $this->sam->id]);

        try {
            $this->service->reject($leave, $this->manager, 'Too late');
        } catch (BusinessRuleException) {
            // expected: APPROVED → REJECTED is blocked
        }

        Event::assertNotDispatched(LeaveRequestStatusChanged::class);
        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    public function test_the_listener_writes_the_action_to_the_log(): void
    {
        Log::spy();
        $leave = $this->pending();

        $this->service->approve($leave, $this->manager);

        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context) => $message === "Leave request #{$leave->id} approved by {$this->manager->name}"
            && $context['action'] === 'approved'
            && $context['status'] === 'approved'
            && $context['by_user_id'] === $this->manager->id);
    }
}
