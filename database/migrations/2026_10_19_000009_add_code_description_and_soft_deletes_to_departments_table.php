<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Departments get a short unique code (e.g. ENG-3), an optional description and soft deletes.
 *
 * Existing departments receive a generated code first, so the unique index can be added safely.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('name');
            $table->text('description')->nullable()->after('code');
            $table->softDeletes();
        });

        // First 3 letters of the name + id, e.g. "Engineering" (id 3) → ENG-3. The id makes it unique.
        DB::table('departments')->whereNull('code')->orderBy('id')->each(function ($department) {
            $letters = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $department->name), 0, 3)) ?: 'DEP';

            DB::table('departments')->where('id', $department->id)->update(['code' => $letters.'-'.$department->id]);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['code', 'description']);
        });
    }
};
