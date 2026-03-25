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
        Schema::create('system_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('actor_id')->nullable()->constrained('accounts'); // Hoặc employees tùy logic
    $table->string('action');
    $table->string('target_table', 100)->nullable();
    $table->unsignedBigInteger('target_id')->nullable();
    $table->json('old_value')->nullable();
    $table->json('new_value')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
