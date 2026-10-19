<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Models\Skill;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_branch_department_employee_chain(): void
    {
        $company = Company::factory()->create(['name' => 'Acme']);
        $branch = Branch::factory()->for($company)->create(['name' => 'Beirut']);
        $department = Department::factory()->for($branch)->create(['name' => 'Sales']);
        $employee = Employee::factory()->for($department)->create();

        // hasMany
        $this->assertTrue($company->branches->contains($branch));
        $this->assertTrue($branch->departments->contains($department));
        $this->assertTrue($department->employees->contains($employee));
        // belongsTo
        $this->assertTrue($employee->department->is($department));
        $this->assertTrue($department->branch->is($branch));
        $this->assertTrue($branch->company->is($company));
        // hasManyThrough
        $this->assertTrue($branch->employees->contains($employee));
        $this->assertSame('Acme · Beirut', $department->location);
    }

    public function test_employee_has_one_profile(): void
    {
        $employee = Employee::factory()->create();
        $profile = EmployeeProfile::factory()->for($employee)->create(['phone' => '+961 1 234 567']);

        $this->assertTrue($employee->profile->is($profile));
        $this->assertTrue($profile->employee->is($employee));

        // employee_profiles.employee_id is unique: a second profile is refused.
        $this->expectException(QueryException::class);
        EmployeeProfile::factory()->for($employee)->create();
    }

    public function test_employee_belongs_to_many_skills_with_pivot_level(): void
    {
        $employee = Employee::factory()->create();
        [$php, $sql] = Skill::factory(2)->create();

        $employee->skills()->attach([$php->id => ['level' => 'expert'], $sql->id => ['level' => 'beginner']]);

        $this->assertEqualsCanonicalizing([$php->id, $sql->id], $employee->skills->pluck('id')->all());
        $this->assertSame('expert', $employee->skills->firstWhere('id', $php->id)->pivot->level);
        $this->assertTrue($php->employees->contains($employee));
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $this->expectException(QueryException::class); // restrictOnDelete
        $employee->department->forceDelete(); // a real DELETE (delete() is now a soft delete)
    }

    public function test_department_still_blocked_by_employee_in_trash(): void
    {
        $employee = Employee::factory()->create();
        $employee->delete(); // soft delete: the row (and its department_id) still exists

        $this->expectException(QueryException::class);
        $employee->department->forceDelete(); // a real DELETE (delete() is now a soft delete)
    }

    public function test_deleting_a_company_cascades_to_branches_and_departments(): void
    {
        $department = Department::factory()->create();
        $company = $department->branch->company;

        $company->delete();

        $this->assertModelMissing($department->branch);
        $this->assertModelMissing($department);
    }

    public function test_force_delete_cascades_to_profile_and_skill_links(): void
    {
        $employee = Employee::factory()->create();
        $profile = EmployeeProfile::factory()->for($employee)->create();
        $employee->skills()->attach(Skill::factory()->create());

        $employee->forceDelete();

        $this->assertModelMissing($profile);
        $this->assertSame(0, DB::table('employee_skill')->where('employee_id', $employee->id)->count());
    }

    public function test_with_details_factory_state_creates_profile_and_skills(): void
    {
        Skill::factory(3)->create();

        $employee = Employee::factory()->withDetails()->create();

        $this->assertNotNull($employee->profile);
        $this->assertGreaterThanOrEqual(1, $employee->skills()->count());
    }
}
