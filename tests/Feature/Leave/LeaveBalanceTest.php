<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Earned leave: 3 base days + 1.25 per month started. "Today" is 20 Oct 2026 in every test,
 * so a full-year employee has earned 3 + 10 × 1.25 = 15.5 days.
 */
class LeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Employee $sam;

    private User $samLogin;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-20 10:00:00');
        $this->sam = Employee::factory()->inDepartment('Sales')->create();
        $this->samLogin = User::factory()->create(['employee_id' => $this->sam->id]);
        $this->hr = User::factory()->hr()->create();
    }

    private function leave(string $start, string $end, float $days, LeaveStatus $status = LeaveStatus::Pending): LeaveRequest
    {
        return LeaveRequest::factory()->create([
            'employee_id' => $this->sam->id, 'start_date' => $start, 'end_date' => $end, 'days' => $days, 'status' => $status,
        ]);
    }

    private function balance(?int $year = null)
    {
        return app(LeaveBalanceService::class)->for($this->sam->fresh(), $year);
    }

    public function test_everyone_starts_with_3_days_and_earns_1_25_a_month(): void
    {
        $balance = $this->balance();

        $this->assertSame(3.0, $balance->base);
        $this->assertSame(10, $balance->months);   // January … October
        $this->assertSame(15.5, $balance->earned());
        $this->assertSame(15.5, $balance->remaining());
    }

    public function test_past_years_count_12_months_and_future_years_none(): void
    {
        $this->assertSame(18.0, $this->balance(2025)->earned()); // 3 + 12 × 1.25
        $this->assertSame(3.0, $this->balance(2027)->earned());  // only the base so far
    }

    public function test_someone_hired_this_year_earns_from_their_hire_month(): void
    {
        EmployeeProfile::factory()->for($this->sam)->create(['hire_date' => '2026-08-15']);

        $balance = $this->balance();
        $this->assertSame(3, $balance->months);            // August, September, October
        $this->assertSame(6.75, $balance->earned());        // 3 + 3 × 1.25

        $this->assertSame(0.0, $this->balance(2025)->earned()); // not hired yet in 2025
    }

    public function test_hr_can_give_one_employee_a_different_base(): void
    {
        $this->sam->update(['base_leave_days' => 5]);

        $this->assertSame(17.5, $this->balance()->earned()); // 5 + 10 × 1.25
    }

    public function test_taken_and_pending_are_subtracted_but_not_rejected_or_other_years(): void
    {
        $this->leave('2026-03-02', '2026-03-04', 3, LeaveStatus::Approved);
        $this->leave('2026-04-06', '2026-04-06', 0.5);
        $this->leave('2026-05-04', '2026-05-08', 5, LeaveStatus::Rejected);
        $this->leave('2025-12-01', '2025-12-02', 2, LeaveStatus::Approved);

        $balance = $this->balance();
        $this->assertSame(3.0, $balance->taken);
        $this->assertSame(0.5, $balance->pending);
        $this->assertSame(12.0, $balance->remaining()); // 15.5 − 3 − 0.5
    }

    public function test_a_request_cannot_ask_for_more_than_is_left(): void
    {
        $this->leave('2026-03-02', '2026-03-13', 10, LeaveStatus::Approved);
        $this->leave('2026-04-06', '2026-04-08', 3); // pending: reserved

        // 2.5 days left; Mon–Wed is 3.
        $this->expectExceptionObject(new BusinessRuleException(
            'Not enough leave left: this request is 3 days, but you have 2.5 days left in 2026 (15.5 earned so far, 10 taken, 3 waiting for approval).'
        ));
        app(LeaveRequestService::class)->submit($this->sam, ['start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'reason' => 'Trip'], $this->samLogin);
    }

    public function test_exactly_what_is_left_is_fine(): void
    {
        $this->leave('2026-03-02', '2026-03-13', 10, LeaveStatus::Approved);
        $this->leave('2026-04-06', '2026-04-08', 3);

        // Mon, Tue, Wed morning = the last 2.5 days.
        app(LeaveRequestService::class)->submit($this->sam, [
            'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'end_period' => 'morning', 'reason' => 'Trip',
        ], $this->samLogin);

        $this->assertSame(0.0, $this->balance()->remaining());
    }

    public function test_a_leave_cannot_run_into_the_next_year(): void
    {
        $this->expectException(BusinessRuleException::class);

        app(LeaveRequestService::class)->submit($this->sam, ['start_date' => '2026-12-30', 'end_date' => '2027-01-02', 'reason' => 'New year'], $this->samLogin);
    }

    public function test_approval_is_refused_when_hr_lowered_the_base_meanwhile(): void
    {
        $this->leave('2026-03-02', '2026-03-13', 10, LeaveStatus::Approved);
        $pending = $this->leave('2026-11-02', '2026-11-06', 5);
        $this->sam->update(['base_leave_days' => 0]); // earned is now 12.5: 10 + 5 does not fit

        try {
            app(LeaveRequestService::class)->approve($pending, $this->hr);
            $this->fail('Should have been refused.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('would go over the leave earned', $e->getMessage());
        }

        $this->assertSame(LeaveStatus::Pending, $pending->fresh()->status);
    }

    public function test_employee_sees_their_balance_on_the_leave_pages(): void
    {
        $this->leave('2026-03-02', '2026-03-04', 3, LeaveStatus::Approved);
        $this->actingAs($this->samLogin);

        $this->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee('My leave in 2026')
            ->assertSee('15.5</strong> days earned', false)
            ->assertSeeInOrder(['12.5', 'Remaining', '3', 'Taken', '0', 'Waiting for approval']);

        $this->get(route('leave-requests.create'))->assertOk()->assertSee('const remaining = 12.5;', false);
    }

    public function test_reviewer_sees_the_employees_balance_on_the_request(): void
    {
        $leave = $this->leave('2026-11-02', '2026-11-03', 2);

        $this->actingAs($this->hr)->get(route('leave-requests.show', $leave))
            ->assertOk()
            ->assertSee($this->sam->first_name."'s leave in 2026");
    }

    public function test_hr_sets_the_base_and_the_employee_cannot(): void
    {
        $this->actingAs($this->hr)->put(route('employees.update', $this->sam), ['base_leave_days' => 4.5])->assertRedirect();
        $this->assertSame(4.5, $this->sam->fresh()->base_leave_days);

        $this->actingAs($this->hr)->put(route('employees.update', $this->sam), ['base_leave_days' => 2.3])
            ->assertSessionHasErrors(['base_leave_days' => 'The base leave must be in whole or half days (e.g. 3 or 3.5).']);

        // The employee's own "Edit contact info" ignores it.
        $this->actingAs($this->samLogin)->put(route('profile.update'), ['phone' => '123', 'base_leave_days' => 60]);
        $this->assertSame(4.5, $this->sam->fresh()->base_leave_days);
    }
}
