<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the free-text employees.department into a real foreign key, safely on existing data:
 *
 * 1. add employees.department_id (FK to departments, restrict on delete)
 * 2. create "Default Company" → "Head Office" and one department per distinct old text value
 * 3. fill employees.department_id from the old text
 * 4. keep the old text, renamed to department_legacy (so the name "department" is free
 *    for the Eloquent relationship) and made nullable for new rows
 */
return new class extends Migration
{
    private const COMPANY = 'Default Company';

    private const BRANCH = 'Head Office';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restrict: a department that still has employees cannot be deleted.
            $table->foreignId('department_id')->nullable()->after('email')->constrained()->restrictOnDelete();
        });

        $this->backfillDepartments();

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('department', 'department_legacy');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('department_legacy', 100)->nullable()->change();
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rows created after this migration only have department_id: copy the name back first.
        DB::table('employees')->whereNull('department_legacy')->whereNotNull('department_id')->orderBy('id')
            ->each(function ($employee) {
                DB::table('employees')->where('id', $employee->id)->update([
                    'department_legacy' => DB::table('departments')->where('id', $employee->department_id)->value('name'),
                ]);
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['last_name', 'first_name']);
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('department_legacy', 'department');
        });
    }

    /**
     * One department (under Default Company → Head Office) per distinct old department text.
     */
    private function backfillDepartments(): void
    {
        $names = DB::table('employees')->whereNotNull('department')->where('department', '!=', '')
            ->distinct()->orderBy('department')->pluck('department');

        if ($names->isEmpty()) {
            return;
        }

        $now = now();
        $companyId = DB::table('companies')->where('name', self::COMPANY)->value('id')
            ?? DB::table('companies')->insertGetId(['name' => self::COMPANY, 'created_at' => $now, 'updated_at' => $now]);
        $branchId = DB::table('branches')->where('company_id', $companyId)->where('name', self::BRANCH)->value('id')
            ?? DB::table('branches')->insertGetId([
                'company_id' => $companyId, 'name' => self::BRANCH, 'created_at' => $now, 'updated_at' => $now,
            ]);

        foreach ($names as $name) {
            $departmentId = DB::table('departments')->where('branch_id', $branchId)->where('name', $name)->value('id')
                ?? DB::table('departments')->insertGetId([
                    'branch_id' => $branchId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now,
                ]);

            DB::table('employees')->where('department', $name)->update(['department_id' => $departmentId]);
        }
    }
};
