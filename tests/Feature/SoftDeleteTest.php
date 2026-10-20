<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function list(string $status = 'active')
    {
        return $this->getJson(route('employees.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => $status]));
    }

    public function test_deleting_moves_the_employee_to_the_trash(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Tina']);

        $this->deleteJson(route('employees.destroy', $employee))->assertOk();

        $this->assertSoftDeleted($employee);
        $this->assertSame(0, Employee::count());
        $this->assertSame(1, Employee::withTrashed()->count());
        $this->get(route('employees.show', $employee))->assertNotFound();
    }

    public function test_trash_list_shows_only_deleted_employees_with_restore_links(): void
    {
        $kept = Employee::factory()->create(['first_name' => 'Kept']);
        $trashed = Employee::factory()->create(['first_name' => 'Trashed']);
        $trashed->delete();

        $active = $this->list()->assertJson(['recordsTotal' => 1, 'trashTotal' => 1]);
        $this->assertSame([$kept->id], array_column($active->json('data'), 'id'));

        $trash = $this->list('trash')->assertJson(['recordsTotal' => 1]);
        $this->assertSame([$trashed->id], array_column($trash->json('data'), 'id'));
        $this->assertSame(route('employees.restore', $trashed), $trash->json('data.0.urls.restore'));
        $this->assertNotNull($trash->json('data.0.deleted_at'));

        $this->get(route('employees.index'))->assertViewHas('trashCount', 1)->assertSee('Trash');
    }

    public function test_employee_can_be_restored(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Rita', 'last_name' => 'Back']);
        $employee->delete();

        $this->postJson(route('employees.restore', $employee))
            ->assertOk()
            ->assertJson(['message' => 'Employee Rita Back restored.']);

        $this->assertNotSoftDeleted($employee);
    }

    public function test_force_delete_only_works_from_the_trash(): void
    {
        $employee = Employee::factory()->create();

        $this->deleteJson(route('employees.force-delete', $employee))->assertNotFound();
        $this->assertModelExists($employee);

        $employee->delete();
        $this->deleteJson(route('employees.force-delete', $employee))->assertOk();
        $this->assertSame(0, Employee::withTrashed()->count());
    }

    public function test_selected_trashed_employees_can_be_restored(): void
    {
        [$a, $b, $c] = Employee::factory(3)->create();
        collect([$a, $b, $c])->each->delete();

        $this->postJson(route('employees.trash.restore'), ['ids' => [$a->id, $b->id]])
            ->assertOk()
            ->assertJson(['message' => '2 employees restored.']);

        $this->assertNotSoftDeleted($a);
        $this->assertNotSoftDeleted($b);
        $this->assertSoftDeleted($c);
    }

    public function test_selected_trashed_employees_can_be_deleted_forever(): void
    {
        [$trashedA, $trashedB, $kept] = Employee::factory(3)->create();
        $trashedA->delete();
        $trashedB->delete();
        $kept->delete();
        $active = Employee::factory()->create();

        // The active employee's id is ignored: only rows in the trash can be deleted forever.
        $this->deleteJson(route('employees.trash.force'), ['ids' => [$trashedA->id, $trashedB->id, $active->id]])
            ->assertOk()
            ->assertJson(['message' => '2 employees permanently deleted.']);

        $this->assertModelMissing($trashedA);
        $this->assertModelMissing($trashedB);
        $this->assertSoftDeleted($kept);
        $this->assertNotSoftDeleted($active);
    }

    public function test_empty_trash_deletes_every_trashed_employee(): void
    {
        Employee::factory(12)->create()->each->delete(); // more than one page
        $active = Employee::factory()->create();

        $this->deleteJson(route('employees.trash.empty'))
            ->assertOk()
            ->assertJson(['message' => 'Trash emptied: 12 employees permanently deleted.']);

        $this->assertSame(0, Employee::onlyTrashed()->count());
        $this->assertModelExists($active);

        $this->deleteJson(route('employees.trash.empty'))->assertJson(['message' => 'The trash is already empty.']);
    }

    public function test_trash_bulk_actions_validate_ids_and_routes_do_not_clash(): void
    {
        $this->postJson(route('employees.trash.restore'), ['ids' => []])->assertUnprocessable();
        $this->deleteJson(route('employees.trash.force'), ['ids' => ['abc']])->assertUnprocessable();

        // "trash" is not treated as an employee id
        $this->assertSame(url('/employees/trash/restore'), route('employees.trash.restore'));
        $this->get(route('employees.index'))->assertSee('Empty trash')->assertSee('Restore selected')->assertSee('Delete forever');
    }

    public function test_manager_cannot_use_trash_bulk_actions(): void
    {
        $employee = Employee::factory()->create();
        $employee->delete();
        $this->actingAs(User::factory()->manager()->create());

        $this->postJson(route('employees.trash.restore'), ['ids' => [$employee->id]])->assertForbidden();
        $this->deleteJson(route('employees.trash.force'), ['ids' => [$employee->id]])->assertForbidden();
        $this->deleteJson(route('employees.trash.empty'))->assertForbidden();
        $this->assertSoftDeleted($employee);
    }

    public function test_restoring_an_active_employee_is_not_found(): void
    {
        $employee = Employee::factory()->create();

        $this->postJson(route('employees.restore', $employee))->assertNotFound();
    }

    public function test_manager_cannot_use_the_trash(): void
    {
        $employee = Employee::factory()->create();
        $employee->delete();
        $this->actingAs(User::factory()->manager()->create());

        $this->postJson(route('employees.restore', $employee))->assertForbidden();
        $this->deleteJson(route('employees.force-delete', $employee))->assertForbidden();
        $this->get(route('employees.index'))->assertDontSee('id="status-trash"', false);
        $this->assertSoftDeleted($employee);
    }

    public function test_email_of_a_trashed_employee_cannot_be_reused(): void
    {
        $employee = Employee::factory()->create(['email' => 'gone@example.com']);
        $employee->delete();

        $this->post(route('employees.store'), [
            'first_name' => 'New', 'last_name' => 'Person', 'email' => 'gone@example.com',
            'department_id' => $employee->department_id, 'salary' => 1000, 'date_of_birth' => '1990-01-01',
        ])->assertSessionHasErrors([
            'email' => 'Another employee already uses this email. If they were deleted, restore them from the trash instead.',
        ]);
    }

    public function test_dashboard_does_not_count_trashed_employees(): void
    {
        Employee::factory(2)->inDepartment('Sales')->create();
        Employee::factory()->inDepartment('Sales')->create()->delete();

        $this->get(route('admin.dashboard'))
            ->assertViewHas('stats', fn ($stats) => $stats['employees'] === 2)
            ->assertViewHas('departments', fn ($departments) => $departments->first()->employees_count === 2);
    }
}
