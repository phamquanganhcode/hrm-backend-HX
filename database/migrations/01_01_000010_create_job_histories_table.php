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
    Schema::create('job_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id')->constrained('employees');
        $table->foreignId('branch_id')->constrained('branches');
        $table->foreignId('position_id')->constrained('positions');
        $table->foreignId('pay_grade_id')->nullable()->constrained('pay_grades');
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->decimal('base_salary_amount', 15, 2)->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_histories');
    }
};
