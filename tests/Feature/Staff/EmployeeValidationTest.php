<?php

namespace Tests\Feature\Staff;

use App\Models\Employee;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeValidationTest extends TestCase
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
            'department_id' => Employee::factory()->inDepartment('Engineering')->make()->department_id,
            'salary' => '55000.50',
            'date_of_birth' => '1990-05-15',
        ], $overrides);
    }

    public function test_custom_messages_and_attribute_names_are_used(): void
    {
        $this->post(route('employees.store'), [])
            ->assertSessionHasErrors([
                'first_name' => 'Please enter the first name.',      // messages() 'required' + attributes()
                'date_of_birth' => 'Please enter the date of birth.',
                'department_id' => 'Please choose a department.',     // field-specific message
            ]);
    }

    public function test_department_must_exist(): void
    {
        $this->post(route('employees.store'), $this->validData(['department_id' => 999]))
            ->assertSessionHasErrors(['department_id' => 'The selected department does not exist. Please choose one from the list.']);
    }

    public function test_skills_must_exist(): void
    {
        $skill = Skill::factory()->create();

        $this->post(route('employees.store'), $this->validData(['skills' => [$skill->id, 999]]))
            ->assertSessionHasErrors(['skills.1' => 'One of the selected skills does not exist.']);
    }

    public function test_profile_fields_and_skills_are_nullable(): void
    {
        $this->post(route('employees.store'), $this->validData(['phone' => '', 'address' => '', 'hire_date' => '', 'skills' => '']))
            ->assertSessionHasNoErrors();

        $employee = Employee::where('email', 'jane@example.com')->sole();
        $this->assertNull($employee->profile->phone);
        $this->assertCount(0, $employee->skills);
    }

    public function test_profile_and_skills_are_saved(): void
    {
        [$php, $sql] = Skill::factory(2)->create();

        $this->post(route('employees.store'), $this->validData([
            'phone' => '+961 70 123 456', 'address' => 'Hamra, Beirut', 'hire_date' => '2020-02-01',
            'skills' => [$php->id, $sql->id],
        ]))->assertSessionHasNoErrors();

        $employee = Employee::where('email', 'jane@example.com')->sole();
        $this->assertSame('+961 70 123 456', $employee->profile->phone);
        $this->assertSame('2020-02-01', $employee->profile->hire_date->format('Y-m-d'));
        $this->assertEqualsCanonicalizing([$php->id, $sql->id], $employee->skills->pluck('id')->all());
    }

    public function test_hire_date_cannot_be_in_the_future_and_phone_must_look_like_a_phone(): void
    {
        $this->post(route('employees.store'), $this->validData(['hire_date' => now()->addDay()->toDateString(), 'phone' => 'call me']))
            ->assertSessionHasErrors([
                'hire_date' => 'The hire date cannot be in the future.',
                'phone' => 'The phone number field format is invalid.',
            ]);
    }

    public function test_update_with_sometimes_allows_partial_updates(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Keep', 'salary' => 1000]);

        // Only the salary is sent: the other fields are "sometimes", so they are not required.
        $this->put(route('employees.update', $employee), ['salary' => '2500'])->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('2500.00', $employee->salary);
        $this->assertSame('Keep', $employee->first_name);
    }

    public function test_update_with_filled_rejects_an_empty_value_that_is_sent(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Keep']);

        $this->put(route('employees.update', $employee), ['first_name' => ''])
            ->assertSessionHasErrors(['first_name' => 'The first name cannot be empty.']);

        $this->assertSame('Keep', $employee->fresh()->first_name);
    }

    public function test_partial_update_keeps_skills_but_an_empty_list_clears_them(): void
    {
        $employee = Employee::factory()->create();
        $employee->skills()->attach(Skill::factory()->create());

        $this->put(route('employees.update', $employee), ['salary' => '3000']);
        $this->assertCount(1, $employee->fresh()->skills);

        $this->put(route('employees.update', $employee), ['skills' => '']);
        $this->assertCount(0, $employee->fresh()->skills);
    }
}
