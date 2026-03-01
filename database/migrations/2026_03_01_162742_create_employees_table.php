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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phonenumber')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('role');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('type')->default('part'); // full hoặc part
            $table->decimal('base_salary', 15, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};