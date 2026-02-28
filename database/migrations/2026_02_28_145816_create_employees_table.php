<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phonenumber')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('fingerprint_id')->nullable(); // ID vân tay
            $table->string('role')->nullable(); // Vai trò theo ERD
            $table->unsignedBigInteger('branch_id')->nullable(); // Nơi làm việc
            $table->enum('type', ['full', 'part'])->default('full'); // full / part - time
            $table->decimal('base_salary', 15, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            // Khóa ngoại liên kết sang bảng branches
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};