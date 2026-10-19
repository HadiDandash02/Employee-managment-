<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Runs the real migration on rows that still have the old free-text department column,
 * like the existing MySQL database had.
 */
class DepartmentBackfillMigrationTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * How many migrations to roll back to get to just before the department_id migration
     * (that one and every migration after it), so adding new migrations does not break this test.
     */
    private function migrationsSinceBackfill(): int
    {
        $files = array_map('basename', glob(database_path('migrations/*.php')));

        return count(array_filter($files, fn ($file) => $file >= '2026_10_19_000005'));
    }

    public function test_old_department_text_is_moved_into_the_foreign_key_and_kept(): void
    {
        // Go back to before the department_id migration.
        Artisan::call('migrate:rollback', ['--step' => $this->migrationsSinceBackfill()]);
        $this->assertTrue(Schema::hasColumn('employees', 'department'));
        $this->assertFalse(Schema::hasColumn('employees', 'department_id'));

        $now = now();
        foreach ([['Ann', 'Sales'], ['Ben', 'Sales'], ['Cat', 'HR']] as [$name, $department]) {
            DB::table('employees')->insert([
                'first_name' => $name, 'last_name' => 'Old', 'email' => strtolower($name).'@old.test',
                'department' => $department, 'salary' => 1000, 'date_of_birth' => '1990-01-01',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Artisan::call('migrate');

        // Old column kept (renamed), new FK filled.
        $this->assertTrue(Schema::hasColumn('employees', 'department_legacy'));
        $this->assertFalse(Schema::hasColumn('employees', 'department'));
        $this->assertSame(0, DB::table('employees')->whereNull('department_id')->count());

        $rows = DB::table('employees')
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->join('branches', 'branches.id', '=', 'departments.branch_id')
            ->join('companies', 'companies.id', '=', 'branches.company_id')
            ->get(['employees.department_legacy', 'departments.name', 'branches.name as branch', 'companies.name as company']);

        foreach ($rows as $row) {
            $this->assertSame($row->department_legacy, $row->name);
            $this->assertSame('Head Office', $row->branch);
            $this->assertSame('Default Company', $row->company);
        }

        // One department per distinct old value: Sales, HR.
        $this->assertSame(2, DB::table('departments')->count());
    }

    public function test_migration_can_be_rolled_back(): void
    {
        Artisan::call('migrate:rollback', ['--step' => $this->migrationsSinceBackfill()]);

        $this->assertTrue(Schema::hasColumn('employees', 'department'));
        $this->assertFalse(Schema::hasColumn('employees', 'department_id'));
        $this->assertFalse(Schema::hasColumn('employees', 'deleted_at'));
    }
}
