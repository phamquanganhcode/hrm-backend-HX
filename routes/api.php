<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    
    // Nhóm Auth (Public)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Nhóm Private (Bắt buộc phải có Token)
    Route::middleware('auth:sanctum')->group(function () {
        // Sau này nhét API danh sách nhân viên, v.v. vào đây
        Route::get('/auth/me', [AuthController::class, 'me']);
    });

});