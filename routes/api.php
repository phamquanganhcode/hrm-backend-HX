<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
// use App\Http\Controllers\Api\AttendanceController; // Tạm tắt nếu bạn chưa code file này

Route::prefix('v1')->group(function () {
    
    // ==========================================
    // 1. Module Auth & Profile
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);
        // 🟢 API CHẤM CÔNG (MỚI THÊM)
        Route::get('/daily-attendances/summary', [AttendanceController::class, 'getMonthlySummary']);
        // ==========================================
        // MOCK API: Đỡ đạn cho Frontend khỏi bị Crash 
        // (Trả về mảng rỗng để FE render được giao diện Dashboard)
        // ==========================================
        Route::get('/work-schedules', function() {
            return response()->json(['status' => 'success', 'data' => []], 200);
        });
        Route::get('/salary-history', function() {
            return response()->json(['status' => 'success', 'data' => []], 200);
        });

        /*
         * Tạm comment các route cũ lại cho đến khi chúng ta viết Controller thật
         *
         * Route::get('/shifts/definitions', [AttendanceController::class, 'getRegistrationConfig']);
         * Route::post('/shift-registrations', [AttendanceController::class, 'registerShifts']);
         * Route::get('/shift-registrations/me', [AttendanceController::class, 'getMyRegistrations']);
         */
    });
});