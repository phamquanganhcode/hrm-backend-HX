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
        Schema::create('attendance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_attendance_id')->constrained('daily_attendances')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('exception_type', 100); // VD: Đi muộn, Thiếu Check-out, Vắng mặt, v.v.
            $table->string('system_logged_value')->nullable(); // Ghi nhận hệ thống (Muộn 15p)
            $table->string('c2_action_type', 100)->nullable(); // Chấp nhận / Điều chỉnh giờ
            $table->dateTime('old_check_in_time')->nullable();
            $table->dateTime('old_check_out_time')->nullable();
            $table->boolean('is_penalty')->default(false);
            $table->text('c2_note')->nullable();
            $table->string('status', 50)->default('Pending'); // Pending, Resolved
            $table->foreignId('resolved_by')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_exceptions');
    }
};
