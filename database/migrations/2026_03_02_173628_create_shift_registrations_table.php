<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_registrations', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại nối sang bảng Nhân viên
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Đổi từ date -> request_date theo đúng bản thiết kế
            $table->date('request_date'); 
            
            // Các ID liên kết (Khóa ngoại)
            $table->unsignedBigInteger('shift_id'); // Khóa ngoại sang bảng Ca làm (ShiftDefinition / Shift)
            $table->unsignedBigInteger('position_id')->nullable(); // Vị trí làm việc (Bàn, Bếp...)
            $table->unsignedBigInteger('weekly_plan_id')->nullable(); // Liên kết với kế hoạch tuần
            
            // Các cột trạng thái
            $table->integer('priority')->default(1); // Mức độ ưu tiên (Ví dụ: 1 là ưu tiên cao nhất)
            $table->boolean('is_assigned')->default(false); // Đã được quản lý xếp vào ca chưa? (true/false)
            
            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes(); // Tạo ra cột deleted_at
            
            // Ràng buộc (Tùy chọn): 1 Nhân viên không được đăng ký cùng 1 ca trong cùng 1 ngày
            $table->unique(['employee_id', 'request_date', 'shift_id'], 'emp_reqdate_shift_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_registrations');
    }
};