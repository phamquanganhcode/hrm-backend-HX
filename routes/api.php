<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// --- IMPORT CÁC CONTROLLER THẬT ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\AttendanceController; 

Route::prefix('v1')->group(function () {
    
    // ==========================================
    // 1. PUBLIC ROUTES (Không yêu cầu đăng nhập)
    // ==========================================
    
    // Đăng nhập
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Route cho Máy chấm công (Giao tiếp qua Secret Key riêng, không dùng Token User)
    Route::post('/attendance/sync', [AttendanceController::class, 'sync']);
    
    
    // ==========================================
    // 2. PROTECTED ROUTES (BẮT BUỘC CÓ TOKEN)
    // ==========================================
    Route::middleware('auth:sanctum')->group(function () {
        
        // -- AUTH & PROFILE --
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

        // ==========================================
        // API CHO MANAGER (Đã đưa vào trong Auth để hàm auth()->user() hoạt động)
        // ==========================================
        
        // -- NHÂN SỰ --
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::put('/employees/{id}', [EmployeeController::class, 'update']);
        Route::put('/employees/{id}/department', [EmployeeController::class, 'updateDepartment']);

        // -- TỔ LÀM VIỆC --
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);

        // -- CA LÀM VIỆC --
        Route::get('/shifts', [ShiftController::class, 'index']);
        Route::post('/shifts', [ShiftController::class, 'store']);

        // -- XẾP LỊCH --
        Route::get('/schedules/scheduled-dates', [ScheduleController::class, 'getScheduledDates']);
        Route::get('/schedule/{date}', [ScheduleController::class, 'getByDate']);
        Route::post('/schedule/{date}', [ScheduleController::class, 'updateByDate']);

        // -- CHẤM CÔNG & GHI ĐÈ BỞI QUẢN LÝ --
        Route::get('/attendance/{date}', [AttendanceController::class, 'getByDate']);
        Route::post('/attendance/override', [AttendanceController::class, 'override']);


        // ==========================================
        // API DÀNH CHO NHÂN VIÊN (Employee App)
        // ==========================================
        
        // -- CHẤM CÔNG CÁ NHÂN --
        Route::get('/daily-attendances', [AttendanceController::class, 'getDailyAttendances']);
        Route::get('/time-logs/realtime', [AttendanceController::class, 'getRealtimeLogs']);
        Route::post('/time-logs/exception', [AttendanceController::class, 'updateException']);
        
        // -- MOCK API CÁ NHÂN (Tránh lỗi Crash giao diện Frontend) --
        Route::get('/work-schedules', function() {
            return response()->json(['status' => 'success', 'data' => []], 200);
        });
        Route::get('/salary-history', function() {
            return response()->json(['status' => 'success', 'data' => []], 200);
        });
    });
});