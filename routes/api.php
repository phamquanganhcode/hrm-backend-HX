<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\File;
// use App\Http\Controllers\Api\AttendanceController; // Tạm tắt nếu bạn chưa code file này

Route::prefix('v1')->group(function () {
    
    // ==========================================
    // 1. Module Auth & Profile
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // 🟢 ROUTE CHO MÁY CHẤM CÔNG (Không cần Token, chỉ cần Secret Key)
    Route::post('/attendance/sync', [\App\Http\Controllers\Api\AttendanceController::class, 'sync']);
    
    // ==========================================
    // 2. MOCK API CHO MANAGER (DÀNH CHO FRONTEND)
    // Tạm để ngoài auth:sanctum để test giao diện không bị lỗi 401 (Unauthorized)
    // ==========================================
    
    // -- Nhân sự (Employees) --
    Route::get('/employees', function() {
        // Đọc file db_employees.json từ thư mục public của Frontend hoặc Backend
        $path = public_path('db_employees.json'); 
        
        if (!File::exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $json = File::get($path);
        $data = json_decode($json, true);

        return response()->json($data, 200);
    });
    Route::put('/employees/{id}', function($id) {
        return response()->json(['success' => true], 200);
    });
    Route::put('/employees/{id}/department', function($id) {
        return response()->json(['success' => true], 200);
    });

    // -- Phòng ban / Tổ (Departments) --
    Route::get('/departments', function() {
        return response()->json([], 200);
    });
    Route::post('/departments', function() {
        return response()->json(['success' => true], 200);
    });
    Route::delete('/departments/{id}', function($id) {
        return response()->json(['success' => true], 200);
    });

    // -- Ca làm việc (Shifts) --
    Route::get('/shifts', function() {
        return response()->json(['shiftCategories' => []], 200);
    });
    Route::post('/shifts', function() {
        return response()->json(['success' => true], 200);
    });

    // -- Xếp lịch (Schedule) --
    Route::get('/schedules/scheduled-dates', function() {
        return response()->json(['scheduledDates' => []], 200);
    });
    Route::get('/schedule/{date}', function($date) {
        return response()->json(['assignments' => []], 200);
    });
    Route::post('/schedule/{date}', function($date) {
        return response()->json(['success' => true], 200);
    });

    // -- Chấm công (Attendance Manager) --
    Route::get('/attendance/{date}', function($date) {
        return response()->json([], 200);
    });
    Route::post('/attendance/override', function() {
        return response()->json(['success' => true], 200);
    });


    // ==========================================
    // 3. CÁC ROUTE YÊU CẦU ĐĂNG NHẬP (CÓ TOKEN)
    // ==========================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

        // 🟢 API CHẤM CÔNG (MỚI THÊM)
        Route::get('/daily-attendances', [\App\Http\Controllers\Api\AttendanceController::class, 'getDailyAttendances']);

        // API Manager: Chấm công Realtime
        Route::get('/time-logs/realtime', [\App\Http\Controllers\Api\AttendanceController::class, 'getRealtimeLogs']);
        
        // API Xử lý ngoại lệ
        Route::post('/time-logs/exception', [\App\Http\Controllers\Api\AttendanceController::class, 'updateException']);
        
        // ==========================================
        // MOCK API: Đỡ đạn cho Frontend khỏi bị Crash (Bản cũ)
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