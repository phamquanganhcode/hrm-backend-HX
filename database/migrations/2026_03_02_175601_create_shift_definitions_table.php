<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên ca: Ca Sáng, Ca Chiều, Ca Gãy
            $table->time('start_time'); // Giờ bắt đầu
            
            // Thời gian nghỉ giữa ca (Dành cho ca gãy)
            $table->time('break_start')->nullable(); 
            $table->time('break_end')->nullable();
            // $table->string('fe_time_format')->nullable()->comment('Định dạng hiển thị FE (vd: 6:00-10:00)');
            $table->time('end_time'); // Giờ kết thúc
            
            // Hệ số lương (Ví dụ làm Lễ Tết là 1.5, 2.0...)
            $table->decimal('coefficient', 4, 2)->default(1.0); 
            
            // Cờ trạng thái
            $table->boolean('is_active')->default(true);
            $table->boolean('is_overnight')->default(false); // Ca qua đêm
            
            // Màu sắc hiển thị lên Frontend (Ví dụ: #3B82F6)
            $table->string('color')->nullable(); 
            
            $table->timestamps();
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_definitions');
    }
};