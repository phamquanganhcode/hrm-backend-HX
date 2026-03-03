<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\AttendanceController;

Route::prefix('v1')->group(function () {
    
    // Nhóm Auth (Public)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Nhóm Private (Bắt buộc phải có Token)
        Route::middleware('auth:sanctum')->group(function () {
            // API Profile
            Route::get('/auth/me', [AuthController::class, 'me']);
            
            // API Lịch làm việc (Mở khóa dòng này, bỏ dấu // đi)
            Route::get('/work-schedules', [AttendanceController::class, 'getWeeklySchedule']);
            
            // API Lấy danh sách ngày & ca trống cho Modal Đăng ký
            Route::get('/work-schedules/registration-config', [AttendanceController::class, 'getRegistrationConfig']);

            // API Lưu thẳng lịch làm việc do nhân viên tự chọn
            Route::post('/work-schedules/register', [AttendanceController::class, 'registerShifts']);
        });

});

// Tuyệt chiêu chạy lệnh hệ thống qua URL (Sau này làm xong thì xóa đi cho bảo mật)
Route::get('/setup-database', function () {
    try {
        // Chạy lệnh migrate và seed bắt buộc
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Chúc mừng! Đã tạo bảng và bơm dữ liệu thành công.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});