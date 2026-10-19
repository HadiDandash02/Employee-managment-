<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table for Employee belongsToMany Skill (many-to-many).
     */
    public function up(): void
    {
        Schema::create('employee_skill', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20)->nullable(); // extra pivot data: beginner / intermediate / expert
            $table->timestamps();

            // An employee has each skill at most once.
            $table->primary(['employee_id', 'skill_id']);
            $table->index('skill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_skill');
    }
};
