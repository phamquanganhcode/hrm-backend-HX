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
    Schema::create('rule_attendances', function (Blueprint $table) {
        $table->id();
        $table->integer('time_min')->nullable();
        $table->integer('time_max')->nullable();
        $table->decimal('amount', 15, 2)->nullable();
        $table->foreignId('created_by')->nullable()->constrained('employees');
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_attendances');
    }
};
