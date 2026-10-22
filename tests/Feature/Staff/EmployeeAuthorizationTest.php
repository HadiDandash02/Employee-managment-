<?php

namespace Tests\Feature\Staff;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EmployeePolicy: every URL is typed by hand here, as an attacker would, without clicking any button.
 * Hidden buttons are only cosmetic; the backend must answer 403 by itself.
 */
class EmployeeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Employee $sales;

    private Employee $salesColleague;

    private Employee $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = Employee::factory()->inDepartment('Sales')->create(['first_name' => 'Sam', 'salary' => 1111]);
        $this->salesColleague = Employee::factory()->create(['first_name' => 'Sara', 'department_id' => $this->sales->department_id]);
        $this->support = Employee::factory()->inDepartment('Support')->create(['first_name' => 'Tom', 'salary' => 98765]);
    }

    private function loginAs(string $role, ?Employee $employee = null): User
    {
        $user = User::factory()->create(['role' => $role, 'employee_id' => $employee?->id]);
        $this->actingAs($user);

        return $user;
    }

    private function dataIds(array $query = []): array
    {
        return collect($this->getJson(route('employees.data', $query))->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    }

    public function test_hr_can_view_create_edit_and_delete_every_employee(): void
    {
        $this->loginAs('hr');

        $this->assertSame([$this->sales->id, $this->salesColleague->id, $this->support->id], $this->dataIds());
        $this->get(route('employees.show', $this->support))->assertOk();
        $this->get(route('employees.create'))->assertOk();
        $this->get(route('employees.edit', $this->support))->assertOk();
        $this->delete(route('employees.destroy', $this->support))->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted($this->support);
    }

    public function test_typing_the_edit_url_by_hand_is_blocked_for_manager_and_employee(): void
    {
        foreach (['manager' => $this->sales, 'employee' => $this->salesColleague] as $role => $own) {
            $this->loginAs($role, $own);

            // Even their own record: a full edit (salary, department) is HR work.
            foreach ([$own, $this->support] as $target) {
                $this->get(url("/{$role}/employees/{$target->id}/edit"))->assertForbidden();
                $this->put(route('employees.update', $target), ['salary' => 1])->assertForbidden();
                $this->delete(route('employees.destroy', $target))->assertForbidden();
            }
        }

        $this->assertSame('1111.00', $this->sales->fresh()->salary);
        $this->assertNotSoftDeleted($this->support);
    }

    public function test_manager_sees_only_their_own_department(): void
    {
        $this->loginAs('manager', $this->sales);

        $this->assertSame([$this->sales->id, $this->salesColleague->id], $this->dataIds());
        $this->get(route('employees.show', $this->salesColleague))->assertOk();
        $this->get(route('employees.show', $this->support))->assertForbidden();

        // A filter for another department cannot widen the list.
        $this->assertSame([], $this->dataIds(['filters' => ['department_id' => $this->support->department_id]]));
        // The JSON has no Edit/Delete for the manager's rows.
        $this->getJson(route('employees.data'))->assertJsonPath('data.0.can', ['update' => false, 'delete' => false]);
    }

    public function test_manager_without_a_linked_employee_sees_no_one(): void
    {
        $this->loginAs('manager');

        $this->assertSame([], $this->dataIds());
        $this->get(route('employees.show', $this->sales))->assertForbidden();
    }

    public function test_the_trash_is_refused_to_users_who_cannot_delete(): void
    {
        $this->support->delete();
        $this->loginAs('manager', $this->support);

        $this->getJson(route('employees.data', ['status' => 'trash']))->assertForbidden();
        $this->post(route('employees.restore', $this->support))->assertForbidden();
        $this->delete(route('employees.force-delete', $this->support))->assertForbidden();
        $this->get(route('employees.index'))->assertOk()->assertDontSee('id="empty-trash"', false);
    }

    public function test_employee_cannot_open_the_staff_pages_or_another_employee(): void
    {
        $this->loginAs('employee', $this->sales);

        $this->get(route('employees.index'))->assertForbidden();
        $this->getJson(route('employees.data'))->assertForbidden();
        $this->get(route('employees.show', $this->sales))->assertForbidden();
        $this->get(route('employees.show', $this->support))->assertForbidden();
        $this->get(route('employees.create'))->assertForbidden();
    }

    public function test_employee_sees_their_own_salary_but_never_someone_elses(): void
    {
        $this->loginAs('employee', $this->sales);

        $this->get(route('profile.show'))->assertOk()->assertSee('1,111.00')->assertDontSee('98,765.00');
    }

    public function test_employee_can_edit_their_own_phone_and_address_only(): void
    {
        $this->loginAs('employee', $this->sales);

        $this->get(route('profile.show'))->assertSee('Edit contact info');
        $this->get(route('profile.edit'))->assertOk()->assertSee('Phone')->assertDontSee('Salary');

        $this->put(route('profile.update'), [
            'phone' => '+961 70 123 456',
            'address' => 'Beirut',
            // Extra fields an attacker might add to the form: ignored.
            'salary' => 999999,
            'department_id' => $this->support->department_id,
            'first_name' => 'Hacked',
        ])->assertRedirect(route('profile.show'));

        $fresh = $this->sales->fresh('profile');
        $this->assertSame('+961 70 123 456', $fresh->profile->phone);
        $this->assertSame('Beirut', $fresh->profile->address);
        $this->assertSame('1111.00', $fresh->salary);
        $this->assertSame($this->sales->department_id, $fresh->department_id);
        $this->assertSame('Sam', $fresh->first_name);
    }

    public function test_contact_info_is_validated(): void
    {
        $this->loginAs('employee', $this->sales);

        $this->from(route('profile.edit'))
            ->put(route('profile.update'), ['phone' => 'call me <script>', 'address' => str_repeat('a', 300)])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors(['phone', 'address']);
    }

    public function test_contact_edit_needs_a_linked_employee_record(): void
    {
        $this->loginAs('employee');

        $this->get(route('profile.show'))->assertOk()->assertDontSee('Edit contact info');
        $this->get(route('profile.edit'))->assertNotFound();
        $this->put(route('profile.update'), ['phone' => '123'])->assertNotFound();
    }

    public function test_policy_answers_per_role(): void
    {
        $hr = User::factory()->hr()->create();
        $manager = User::factory()->manager()->create(['employee_id' => $this->sales->id]);
        $employee = User::factory()->create(['role' => 'employee', 'employee_id' => $this->salesColleague->id]);

        $this->assertTrue($hr->can('update', $this->support));
        $this->assertTrue($hr->can('delete', $this->support));

        $this->assertTrue($manager->can('view', $this->salesColleague));
        $this->assertFalse($manager->can('view', $this->support));
        $this->assertFalse($manager->can('update', $this->salesColleague));
        $this->assertFalse($manager->can('delete', $this->salesColleague));

        $this->assertTrue($employee->can('view', $this->salesColleague));
        $this->assertFalse($employee->can('view', $this->sales));
        $this->assertTrue($employee->can('updateContact', $this->salesColleague));
        $this->assertFalse($employee->can('updateContact', $this->sales));
        $this->assertFalse($employee->can('delete', $this->salesColleague));
    }
}
