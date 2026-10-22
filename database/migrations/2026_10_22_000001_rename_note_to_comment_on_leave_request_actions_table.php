<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Week 3 spec: leave_request_actions has a "comment" column (it was called "note").
 * renameColumn keeps the existing history text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_request_actions', function (Blueprint $table) {
            $table->renameColumn('note', 'comment');
        });
    }

    public function down(): void
    {
        Schema::table('leave_request_actions', function (Blueprint $table) {
            $table->renameColumn('comment', 'note');
        });
    }
};
