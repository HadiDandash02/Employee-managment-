<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The leave days an employee starts each year with (default 3, HR can change it per employee).
 * On top of this they earn config('leave.monthly_accrual') days every month (LeaveBalanceService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('base_leave_days', 4, 1)->default(3)->after('salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('base_leave_days');
        });
    }
};
