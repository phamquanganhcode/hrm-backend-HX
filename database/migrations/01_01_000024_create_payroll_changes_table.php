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
        Schema::create('payroll_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('payroll_item_type_id')->constrained('payroll_item_types');
            $table->decimal('amount', 15, 2);
            $table->integer('month');
            $table->integer('year');
            $table->string('type', 50);
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_changes');
    }
};
