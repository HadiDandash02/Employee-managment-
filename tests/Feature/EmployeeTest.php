<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
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
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'department' => 'Engineering',
            'salary' => '55000.50',
            'date_of_birth' => '1990-05-15',
        ], $overrides);
    }

    /**
     * Call the DataTables endpoint the way the browser does.
     */
    private function dataTable(array $params = [])
    {
        $columns = collect(['', '', 'id', 'name', 'email', 'department', 'salary', 'date_of_birth', 'added_on', ''])
            ->map(fn ($name, $i) => ['data' => $i, 'name' => $name, 'orderable' => in_array($i, [0, 1, 9]) ? 'false' : 'true'])
            ->all();

        return $this->getJson(route('employees.data', array_replace_recursive([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'columns' => $columns,
        ], $params)));
    }

    private function names($response): array
    {
        return array_column($response->json('data'), 'full_name');
    }

    public function test_index_page_loads_the_datatable(): void
    {
        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSee('id="employees-table"', false)
            ->assertSee(json_encode(route('employees.data')), false);
    }

    public function test_index_passes_search_from_the_url(): void
    {
        $this->get(route('employees.index', ['search' => 'Sales']))
            ->assertOk()
            ->assertViewHas('search', 'Sales');
    }

    public function test_data_is_paginated(): void
    {
        Employee::factory(15)->create();

        $response = $this->dataTable(['draw' => 3])->assertOk();

        $response->assertJson(['draw' => 3, 'recordsTotal' => 15, 'recordsFiltered' => 15]);
        $this->assertCount(10, $response->json('data'));
        $this->assertCount(5, $this->dataTable(['start' => 10])->json('data'));
    }

    public function test_data_page_length_can_be_changed(): void
    {
        Employee::factory(30)->create();

        $this->assertCount(25, $this->dataTable(['length' => 25])->json('data'));
        $this->assertCount(30, $this->dataTable(['length' => -1])->json('data'));
        $this->assertCount(10, $this->dataTable(['length' => 7])->json('data'));
    }

    public function test_data_can_be_searched(): void
    {
        Employee::factory()->create(['first_name' => 'Zelda', 'department' => 'Sales']);
        Employee::factory()->create(['first_name' => 'Bob', 'department' => 'Finance']);

        $response = $this->dataTable(['search' => ['value' => 'zeld']]);
        $response->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1]);
        $this->assertStringStartsWith('Zelda', $this->names($response)[0]);

        $this->assertStringStartsWith('Bob', $this->names($this->dataTable(['search' => ['value' => 'Finance']]))[0]);
    }

    public function test_data_can_be_searched_by_full_name(): void
    {
        Employee::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Hyrule']);
        Employee::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Fitzgerald']);

        $this->assertSame(['Zelda Hyrule'], $this->names($this->dataTable(['search' => ['value' => '  zelda   hyrule ']])));
    }

    public function test_data_can_be_sorted(): void
    {
        Employee::factory()->create(['first_name' => 'Mid', 'last_name' => 'X', 'salary' => 50000]);
        Employee::factory()->create(['first_name' => 'Low', 'last_name' => 'X', 'salary' => 10000]);
        Employee::factory()->create(['first_name' => 'High', 'last_name' => 'X', 'salary' => 90000]);

        $this->assertSame(['Low X', 'Mid X', 'High X'], $this->names($this->dataTable(['order' => [['column' => 6, 'dir' => 'asc']]])));
        $this->assertSame(['High X', 'Mid X', 'Low X'], $this->names($this->dataTable(['order' => [['column' => 6, 'dir' => 'desc']]])));
        $this->assertSame(['High X', 'Low X', 'Mid X'], $this->names($this->dataTable(['order' => [['column' => 3, 'dir' => 'asc']]])));
    }

    public function test_data_can_be_filtered(): void
    {
        Employee::factory()->create(['first_name' => 'Ann', 'last_name' => 'X', 'department' => 'Sales', 'salary' => 40000, 'date_of_birth' => '1990-01-01']);
        Employee::factory()->create(['first_name' => 'Ben', 'last_name' => 'X', 'department' => 'Sales', 'salary' => 80000, 'date_of_birth' => '1980-06-15']);
        Employee::factory()->create(['first_name' => 'Cat', 'last_name' => 'X', 'department' => 'HR', 'salary' => 60000, 'date_of_birth' => '2000-03-10']);

        $names = fn (array $filters) => $this->names($this->dataTable(['filters' => $filters, 'order' => [['column' => 3, 'dir' => 'asc']]]));

        $this->assertSame(['Ann X', 'Ben X'], $names(['department' => 'Sales']));
        $this->assertSame(['Ben X', 'Cat X'], $names(['salary_min' => '50000']));
        $this->assertSame(['Ann X', 'Cat X'], $names(['salary_max' => '60000']));
        $this->assertSame(['Ann X', 'Cat X'], $names(['born_from' => '1985-01-01']));
        $this->assertSame(['Ben X'], $names(['born_to' => '1985-01-01']));
        $this->assertSame(['Ann X'], $names(['department' => 'Sales', 'salary_max' => '50000']));
        $this->assertSame(['Ann X', 'Ben X', 'Cat X'], $names(['added_from' => now()->toDateString(), 'added_to' => now()->toDateString()]));
        $this->assertSame([], $names(['added_to' => now()->subDay()->toDateString()]));

        $this->dataTable(['filters' => ['department' => 'Sales']])->assertJson(['recordsTotal' => 3, 'recordsFiltered' => 2]);
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        Employee::factory(3)->create();

        $this->dataTable(['filters' => [
            'salary_min' => 'abc',
            'born_from' => '31/12/1990',
            'added_to' => '2024-02-30',
            'department' => ['x'],
        ]])->assertOk()->assertJson(['recordsFiltered' => 3]);
    }

    public function test_index_lists_departments_for_the_filter(): void
    {
        Employee::factory()->create(['department' => 'Marketing']);

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSee('<option value="Marketing">', false)
            ->assertSee('Generate Report');
    }

    public function test_data_ignores_unknown_or_unorderable_sort_columns(): void
    {
        Employee::factory(3)->create();

        $this->dataTable(['order' => [['column' => 0, 'dir' => 'asc']]])->assertOk();
        $this->dataTable(['order' => [['column' => 99, 'dir' => 'sideways']]])->assertOk();
        $this->dataTable(['columns' => [6 => ['name' => 'password; drop table employees']], 'order' => [['column' => 6]]])->assertOk();
    }

    public function test_data_rows_contain_display_fields_and_urls(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'salary' => 1234.5]);

        $this->dataTable()->assertJsonPath('data.0', fn ($row) => $row['initials'] === 'JD'
            && $row['salary_formatted'] === '1,234.50'
            && $row['urls']['edit'] === route('employees.edit', $employee));
    }

    public function test_selected_employees_can_be_bulk_deleted(): void
    {
        [$first, $second, $kept] = Employee::factory(3)->create();

        $this->deleteJson(route('employees.bulk-destroy'), ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->assertJson(['deleted' => 2, 'message' => '2 employees deleted successfully.']);

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertModelExists($kept);
    }

    public function test_bulk_delete_requires_ids(): void
    {
        Employee::factory()->create();

        $this->deleteJson(route('employees.bulk-destroy'), ['ids' => []])->assertUnprocessable();
        $this->deleteJson(route('employees.bulk-destroy'), ['ids' => ['abc']])->assertUnprocessable();
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_employee_can_be_deleted_with_ajax(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->deleteJson(route('employees.destroy', $employee))
            ->assertOk()
            ->assertJson(['message' => 'Employee Jane Doe deleted successfully.']);

        $this->assertModelMissing($employee);
    }

    public function test_employee_can_be_created(): void
    {
        $this->post(route('employees.store'), $this->validData())
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['email' => 'jane@example.com', 'department' => 'Engineering']);
    }

    public function test_store_validates_input(): void
    {
        Employee::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('employees.store'), [
            'email' => 'taken@example.com',
            'salary' => -5,
            'date_of_birth' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'department', 'salary', 'date_of_birth']);

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_employee_can_be_viewed_and_edited(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('employees.show', $employee))->assertOk()->assertSee($employee->email);
        $this->get(route('employees.edit', $employee))->assertOk()->assertSee($employee->email);
    }

    public function test_show_page_lists_colleagues_and_department_average(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jane', 'department' => 'Sales', 'salary' => 60000]);
        Employee::factory()->create(['first_name' => 'Colin', 'department' => 'Sales', 'salary' => 40000]);
        Employee::factory()->create(['first_name' => 'Otto', 'department' => 'Finance']);

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertViewHas('departmentCount', 2)
            ->assertViewHas('departmentAverage', 50000.0)
            ->assertSee('Colin')
            ->assertDontSee('Otto')
            ->assertSee('20.0%');
    }

    public function test_show_page_works_for_only_employee_in_department(): void
    {
        $employee = Employee::factory()->create(['department' => 'Legal']);

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Only employee in this department');
    }

    public function test_edit_page_suggests_existing_departments(): void
    {
        $employee = Employee::factory()->create(['department' => 'Engineering']);
        Employee::factory()->create(['department' => 'Marketing']);

        $this->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('<option value="Marketing">', false)
            ->assertSee('Danger zone');
    }

    public function test_employee_can_be_updated_keeping_same_email(): void
    {
        $employee = Employee::factory()->create(['email' => 'jane@example.com']);

        $this->put(route('employees.update', $employee), $this->validData(['last_name' => 'Smith']))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Smith', $employee->fresh()->last_name);
    }

    public function test_employee_can_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $this->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertModelMissing($employee);
    }
}
