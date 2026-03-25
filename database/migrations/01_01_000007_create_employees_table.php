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
        $table->string('employee_code', 50)->unique();
        $table->string('full_name');
        $table->string('email')->nullable();
        $table->string('phonenumber', 20)->nullable();
        $table->string('avatar_url')->nullable();
        $table->string('fingerprint_id', 100)->nullable();
        $table->string('role', 50)->nullable();
        $table->date('hire_date')->nullable();
        $table->foreignId('branch_id')->nullable()->constrained('branches');
        $table->string('type', 50)->nullable()->comment('full / part - time');
        $table->foreignId('pay_grade_id')->nullable()->constrained('pay_grades');
        $table->string('status', 50)->default('Active');
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::table('branches', function (Blueprint $table) {
        $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
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
