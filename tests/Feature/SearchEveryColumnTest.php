<?php

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The search boxes search every column of their table, and dates are typed dd/mm/yyyy.
 */
class SearchEveryColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_dates_are_read_the_arabic_european_way(): void
    {
        $this->assertSame('2026-12-25', Search::date('25/12/2026'));
        $this->assertSame('2026-01-05', Search::date('5/1/2026'));
        $this->assertSame('2026-12', Search::date('12/2026'));
        $this->assertNull(Search::date('31/02/2026')); // no 31 February
        $this->assertNull(Search::date('ali'));
    }

    public function test_employees_by_every_column(): void
    {
        $this->actingAs(User::factory()->hr()->create(['username' => 'hr.person']));
        $sara = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sara', 'salary' => 4321, 'date_of_birth' => '1990-03-15']);
        $sara->profile()->create(['phone' => '+961 70 555 123', 'address' => 'Hamra street', 'hire_date' => '2020-06-01']);
        User::factory()->create(['username' => 'sara.login', 'employee_id' => $sara->id]);
        Employee::factory()->inDepartment('Support')->create(['first_name' => 'Omar']);

        $names = fn (string $search) => collect($this->getJson(route('employees.data', ['search' => ['value' => $search]]))->json('data'))->pluck('id')->all();

        $this->assertSame([$sara->id], $names('sales'));          // department
        $this->assertSame([$sara->id], $names('4321'));           // salary
        $this->assertSame([$sara->id], $names('555'));            // phone
        $this->assertSame([$sara->id], $names('hamra'));          // address
        $this->assertSame([$sara->id], $names('15/03/1990'));     // date of birth, dd/mm/yyyy
        $this->assertSame([$sara->id], $names('01/06/2020'));     // hire date
        $this->assertSame([$sara->id], $names('sara.login'));     // login username
        $this->assertContains($sara->id, $names((string) $sara->id)); // # id (a short number can also match other columns)
    }

    public function test_users_by_every_column(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'The Admin']));
        $employee = Employee::factory()->create(['first_name' => 'Linked', 'last_name' => 'Person']);
        User::factory()->manager()->create(['name' => 'Mona Manager', 'employee_id' => $employee->id]);
        User::factory()->create(['name' => 'Eve Employee', 'role' => 'employee']);

        $names = fn (string $search) => collect($this->getJson(route('admin.users.data', ['search' => ['value' => $search]]))->json('data'))->pluck('name')->all();

        $this->assertSame(['Mona Manager'], $names('manager'));   // role
        $this->assertSame(['Mona Manager'], $names('linked'));    // linked employee
        $this->assertSame(['Eve Employee'], $names('eve'));       // name
    }

    public function test_leave_requests_by_every_column(): void
    {
        $sam = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam']);
        $hr = User::factory()->hr()->create(['name' => 'Helen HR']);
        $this->actingAs($hr);
        LeaveRequest::factory()->create(['employee_id' => $sam->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-06', 'reason' => 'Trip', 'status' => LeaveStatus::Approved, 'approved_by' => $hr->id]);
        LeaveRequest::factory()->create(['start_date' => '2026-12-14', 'end_date' => '2026-12-14', 'reason' => 'Dentist']);

        $reasons = fn (string $search) => $this->get(route('leave-requests.index', ['search' => $search]))->viewData('leaveRequests')->pluck('reason')->all();

        $this->assertSame(['Trip'], $reasons('sales'));        // department
        $this->assertSame(['Trip'], $reasons('approved'));     // status
        $this->assertSame(['Trip'], $reasons('helen'));        // decided by
        $this->assertSame(['Trip'], $reasons('04/11/2026'));   // a day inside the leave
        $this->assertSame(['Dentist'], $reasons('12/2026'));   // a month
        $this->assertSame(['Dentist'], $reasons('pending'));
    }
}
