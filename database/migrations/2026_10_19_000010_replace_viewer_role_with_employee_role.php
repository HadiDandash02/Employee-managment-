<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The "viewer" role becomes "employee": a login that belongs to one employee record
 * (users.employee_id) and can only see that employee's own profile.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // One login per employee (unique). If the employee is permanently deleted, the login stays but is unlinked.
            $table->foreignId('employee_id')->nullable()->unique()->after('role')->constrained()->nullOnDelete();
        });

        DB::table('users')->where('role', 'viewer')->update(['role' => 'employee']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('employee')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'employee')->update(['role' => 'viewer']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('viewer')->change();
            $table->dropUnique(['employee_id']);
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
