<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of every leave request: who submitted, approved or rejected it, and when.
 * Rows are only ever added (never edited), so the history cannot be rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            // Who did it; the history row stays if that user is deleted.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);          // App\Enums\LeaveAction
            $table->string('from_status', 20)->nullable(); // App\Enums\LeaveStatus (null when submitted)
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_actions');
    }
};
