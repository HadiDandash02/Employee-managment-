<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Research',
            'code' => 'res-1',
            'branch_id' => Branch::factory()->create()->id,
            'description' => 'New products',
        ], $overrides);
    }

    public function test_index_shows_name_code_branch_and_employee_count(): void
    {
        $department = Department::factory()->create(['name' => 'Logistics', 'code' => 'LOG-7']);
        Employee::factory(2)->for($department)->create();
        Employee::factory()->for($department)->create()->delete(); // trashed: not counted

        $this->assertSame(url('/admin/departments'), route('admin.departments.index'));

        $this->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee('Manage Departments')
            ->assertSee('Logistics')
            ->assertSee('LOG-7')
            ->assertSee($department->branch->name)
            ->assertSee($department->branch->company->name)
            ->assertViewHas('departments', fn ($departments) => $departments->first()->employees_count === 2);
    }

    public function test_index_has_no_n_plus_one_queries(): void
    {
        $queriesFor = function (int $departments): int {
            Department::factory($departments)->create();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('admin.departments.index'))->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $few = $queriesFor(2);   // 2 departments on the page
        $many = $queriesFor(12); // 14 departments on the page

        $this->assertSame($few, $many, 'The number of queries must not grow with the number of departments.');
    }

    public function test_index_can_be_searched_by_name_or_code(): void
    {
        Department::factory()->create(['name' => 'Logistics', 'code' => 'LOG-1']);
        Department::factory()->create(['name' => 'Finance', 'code' => 'FIN-1']);

        $this->get(route('admin.departments.index', ['search' => 'log']))->assertSee('Logistics')->assertDontSee('FIN-1');
        $this->get(route('admin.departments.index', ['search' => 'FIN-1']))->assertSee('Finance')->assertDontSee('LOG-1');
    }

    public function test_department_can_be_created_and_code_is_uppercased(): void
    {
        $this->post(route('admin.departments.store'), $data = $this->validData())
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', ['name' => 'Research', 'code' => 'RES-1', 'branch_id' => $data['branch_id']]);
    }

    public function test_store_validates_name_code_and_branch(): void
    {
        $this->post(route('admin.departments.store'), ['code' => 'bad code!', 'branch_id' => 999])
            ->assertSessionHasErrors([
                'name' => 'Please enter the department name.',
                'code' => 'The code may only contain letters, numbers, dashes and underscores (e.g. ENG-1).',
                'branch_id' => 'The selected branch does not exist. Please choose one from the list.',
            ]);
    }

    public function test_code_must_be_unique_including_deleted_departments(): void
    {
        Department::factory()->create(['code' => 'TAKEN-1']);
        Department::factory()->create(['code' => 'GONE-1'])->delete();

        $this->post(route('admin.departments.store'), $this->validData(['code' => 'taken-1']))
            ->assertSessionHasErrors(['code' => 'Another department already uses this code.']);
        $this->post(route('admin.departments.store'), $this->validData(['code' => 'GONE-1']))
            ->assertSessionHasErrors('code');
    }

    public function test_name_is_unique_per_branch_only(): void
    {
        $existing = Department::factory()->create(['name' => 'Sales']);

        $this->post(route('admin.departments.store'), $this->validData(['name' => 'Sales', 'branch_id' => $existing->branch_id]))
            ->assertSessionHasErrors(['name' => 'This branch already has a department with this name.']);

        // Another branch may have its own "Sales".
        $this->post(route('admin.departments.store'), $this->validData(['name' => 'Sales', 'code' => 'SAL-9']))
            ->assertSessionHasNoErrors();
    }

    public function test_department_can_be_updated_keeping_its_code(): void
    {
        $department = Department::factory()->create(['name' => 'Old', 'code' => 'OLD-1']);

        $this->get(route('admin.departments.edit', $department))->assertOk()->assertSee('OLD-1');

        $this->put(route('admin.departments.update', $department), [
            'name' => 'New name',
            'code' => 'OLD-1',
            'branch_id' => $department->branch_id,
            'description' => '',
        ])->assertRedirect(route('admin.departments.index'))->assertSessionHasNoErrors();

        $this->assertSame('New name', $department->fresh()->name);
        $this->assertNull($department->fresh()->description);
    }

    public function test_empty_department_is_soft_deleted(): void
    {
        $department = Department::factory()->create();

        $this->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($department);
        $this->get(route('admin.departments.index'))->assertDontSee($department->code);
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $department = Department::factory()->create(['name' => 'Busy']);
        Employee::factory(2)->for($department)->create();

        $this->from(route('admin.departments.index'))
            ->delete(route('admin.departments.destroy', $department))
            ->assertRedirect(route('admin.departments.index'))
            ->assertSessionHasErrors(['department' => 'Busy still has 2 employees. Move them to another department first.']);

        $this->assertNotSoftDeleted($department);
    }

    public function test_hr_uses_the_hr_prefix(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        $this->assertSame(url('/hr/departments'), route('admin.departments.index'));
        $this->get(url('/hr/departments'))->assertOk();
        $this->get(url('/admin/departments'))->assertNotFound();
    }

    public function test_manager_and_viewer_cannot_manage_departments(): void
    {
        foreach ([User::factory()->manager()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user);

            $this->get(url('/admin/departments'))->assertNotFound();
            $this->post(url('/admin/departments'), $this->validData())->assertNotFound();

            $sidebar = Str::between($this->get(route('employees.index'))->getContent(), '<aside', '</aside>');
            $this->assertStringNotContainsString('Manage Departments', $sidebar);
        }

        $this->assertDatabaseMissing('departments', ['name' => 'Research']);
    }

    public function test_sidebar_shows_manage_departments_for_admin_and_hr(): void
    {
        $sidebar = Str::between($this->get(route('employees.index'))->getContent(), '<aside', '</aside>');
        $this->assertMatchesRegularExpression('/Manage Employees.*Manage Departments.*Manage Users/s', $sidebar);

        $this->actingAs(User::factory()->hr()->create());
        $sidebar = Str::between($this->get(route('employees.index'))->getContent(), '<aside', '</aside>');
        $this->assertStringContainsString('Manage Departments', $sidebar);
    }
}
