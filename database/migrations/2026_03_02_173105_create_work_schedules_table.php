<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            
            // Các trường cơ bản
            $table->unsignedBigInteger('weekly_plan_id')->nullable();
            $table->date('date');
            
            // Khóa ngoại
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('work_branch_id')->nullable();
            
            // Trạng thái (Quan trọng: Mặc định là Published để hiển thị lên FE luôn)
            $table->enum('is_published', ['Draft', 'Published'])->default('Published');
            $table->string('status')->default('Scheduled'); // Scheduled, Completed, Absent, Canceled...
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};