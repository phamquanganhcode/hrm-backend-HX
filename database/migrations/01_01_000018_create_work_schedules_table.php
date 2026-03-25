<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_plan_id')->constrained('weekly_plans');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('shift_id')->constrained('shift_definitions');
            $table->foreignId('work_branch_id')->constrained('branches');
            $table->date('date');
            $table->time('custom_start_time')->nullable();
            $table->time('custom_end_time')->nullable();
            $table->string('is_published', 50)->default('Draft');
            $table->string('status', 50)->default('Scheduled');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
