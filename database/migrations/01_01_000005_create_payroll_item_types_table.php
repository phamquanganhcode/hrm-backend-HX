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
    Schema::create('payroll_item_types', function (Blueprint $table) {
        $table->id();
        $table->string('code', 100)->unique();
        $table->string('name');
        $table->tinyInteger('sign')->comment('1: Cộng, -1: Trừ');
        $table->string('calc_method', 50)->comment('Fixed, Prorated_by_day, Formula');
        $table->boolean('is_taxable')->default(false);
        $table->boolean('is_system')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_item_types');
    }
};
