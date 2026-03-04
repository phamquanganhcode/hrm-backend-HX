<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->comment('Mã nhân viên (vd: EMP123)');
            
            // Lưu thứ trong tuần (vd: 1=Thứ 2, 2=Thứ 3... 0 hoặc 7=Chủ nhật)
            $table->integer('day_of_week')->nullable(); 
            
            $table->unsignedBigInteger('shift_id')->nullable()->comment('Ca làm việc cố định hoặc bị bận');
            $table->date('start_date')->comment('Ngày bắt đầu áp dụng');
            $table->date('end_date')->nullable()->comment('Ngày kết thúc (null = áp dụng vô thời hạn)');
            
            $table->string('reason')->nullable()->comment('Lý do (nếu báo bận)');
            $table->string('status')->default('Active')->comment('Trạng thái: Active, Inactive, Pending');
            
            // Phân loại: Lịch cố định (FIXED) hoặc Lịch bận (BUSY)
            $table->enum('type', ['FIXED', 'BUSY']);
            
            $table->string('approver_id')->nullable()->comment('Mã người duyệt');
            
            $table->timestamps();
            $table->softDeletes(); // Tạo cột deleted_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_schedules');
    }
};