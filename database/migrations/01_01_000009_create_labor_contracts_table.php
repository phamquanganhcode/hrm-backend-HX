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
    Schema::create('labor_contracts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id')->constrained('employees');
        $table->string('contract_type', 100);
        $table->decimal('salary_percentage', 5, 2)->default(100.00);
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->string('contract_file')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_contracts');
    }
};
