<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Half days: a leave starts on a morning or afternoon and ends on a morning or afternoon
 * (App\Enums\LeavePeriod). "days" stores the working days it takes (e.g. 2.5).
 *
 * Existing requests become full days (morning → afternoon) and get their days counted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('start_period', 10)->default('morning')->after('start_date');
            $table->string('end_period', 10)->default('afternoon')->after('end_date');
            $table->decimal('days', 5, 1)->default(0)->after('end_period');
        });

        // Working days (Mon–Fri) of the existing full-day requests.
        DB::table('leave_requests')->orderBy('id')->each(function ($row) {
            $days = 0;
            for ($day = Carbon::parse($row->start_date); $day->lte(Carbon::parse($row->end_date)); $day->addDay()) {
                $days += $day->isWeekend() ? 0 : 1;
            }

            DB::table('leave_requests')->where('id', $row->id)->update(['days' => $days]);
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['start_period', 'end_period', 'days']);
        });
    }
};
