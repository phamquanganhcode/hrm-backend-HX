<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date'); // Ngày làm việc (YYYY-MM-DD)
            $table->string('shift_name'); // Tên ca (Ca Sáng, Ca Gãy...)
            
            // Thời gian ca 1 (Bắt buộc)
            $table->time('start_time_1'); 
            $table->time('end_time_1');
            
            // Thời gian ca 2 (Dành cho CA GÃY, có thể để trống)
            $table->time('start_time_2')->nullable(); 
            $table->time('end_time_2')->nullable();
            
            $table->string('location')->nullable(); // Khu vực làm việc
            $table->string('note')->nullable(); // Ghi chú
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};