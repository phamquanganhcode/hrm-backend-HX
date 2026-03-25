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
        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete();;
            $table->foreignId('actual_branch_id')->nullable()->constrained('branches')->nullOnDelete();;
            $table->date('date');
            $table->integer('late_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->decimal('total_work_hours', 5, 2)->default(0.00);
            $table->string('status', 50)->default('Chờ duyệt');
            $table->text('note')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->boolean('is_paid_leave')->default(false);
            $table->boolean('is_manually_adjusted')->default(false);
            $table->decimal('temp_salary', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendances');
    }
};
