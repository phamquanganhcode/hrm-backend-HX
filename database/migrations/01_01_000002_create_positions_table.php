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
    Schema::create('positions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code', 50)->comment('C0, C1, C2, C3');
        $table->decimal('base_salary_default', 15, 2)->nullable();
        $table->decimal('hourly_rate', 15, 2)->nullable();
        $table->integer('allowed_leave_days')->nullable();
        $table->text('description')->nullable();
        $table->boolean('is_manager')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
