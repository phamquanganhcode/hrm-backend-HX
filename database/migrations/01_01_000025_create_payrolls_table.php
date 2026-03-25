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
        Schema::create('payrolls', function (Blueprint $table) {
    $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('branch_id')->constrained('branches');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('standard_work_days_of_month', 5, 2)->nullable();
            $table->integer('allowed_leave_days')->default(0);
            $table->decimal('total_work_days', 5, 2)->default(0.00);
            $table->decimal('base_salary_amount', 15, 2)->default(0.00);
            $table->decimal('allowance_amount', 15, 2)->default(0.00);
            $table->decimal('deduction_amount', 15, 2)->default(0.00);
            $table->decimal('final_salary', 15, 2)->default(0.00);
            $table->string('status', 50)->default('Draft');
            $table->boolean('signed_by_employee')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
