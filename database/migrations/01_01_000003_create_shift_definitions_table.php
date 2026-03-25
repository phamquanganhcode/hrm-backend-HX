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
    Schema::create('shift_definitions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->time('start_time');
        $table->time('break_start')->nullable();
        $table->time('break_end')->nullable();
        $table->time('end_time');
        $table->decimal('coefficient', 5, 2)->default(1.00);
        $table->boolean('is_active')->default(true);
        $table->boolean('is_overnight')->default(false);
        $table->string('color', 50)->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_definitions');
    }
};
