<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // ID người quản lý
            $table->timestamps();
            $table->softDeletes(); // Tạo cột deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};