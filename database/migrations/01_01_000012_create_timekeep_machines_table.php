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
        Schema::create('timekeep_machines', function (Blueprint $table) {
            $table->id();
            $table->string('machine_name');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('ip_port', 100)->nullable();
            $table->string('status', 50)->default('Active');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timekeep_machines');
    }
};
