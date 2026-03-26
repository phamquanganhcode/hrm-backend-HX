<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyAttendance;
use App\Models\Employee;

class AttendanceController extends Controller
{
    // ==========================================
    // API 5.2: Lấy danh sách Bảng công trong ngày
    // ==========================================
    public function getDailyAttendances(Request $request)
    {
        try {
            // Nhận tham số từ Frontend (Có giá trị mặc định nếu không truyền)
            $branchId = $request->query('branch_id', 1);
            $date = $request->query('date', now()->toDateString());

            // 1. Query thẳng vào bảng daily_attendances
            $attendances = DailyAttendance::with('employee')
                ->where('actual_branch_id', $branchId)
                ->whereDate('date', $date)
                ->get();

            // 2. Map dữ liệu CHUẨN 100% THEO API SPEC 5.2
            $mappedData = $attendances->map(function($record) {
                return [
                    'id'                   => $record->id,
                    'employee_id'          => $record->employee_id,
                    'employee_name'        => $record->employee ? $record->employee->full_name : 'Không xác định',
                    'date'                 => $record->date,
                    'actual_branch_id'     => $record->actual_branch_id,
                    'total_work_hours'     => (float) $record->total_work_hours,
                    'late_minutes'         => (int) $record->late_minutes,
                    'early_minutes'        => (int) $record->early_minutes,
                    'overtime_hours'       => (float) $record->overtime_hours,
                    'status'               => $record->status ?? 'Chờ duyệt',
                    'is_holiday'           => (bool) $record->is_holiday,
                    'is_manually_adjusted' => (bool) $record->is_manually_adjusted
                ];
            });

            // 3. Trả về Frontend
            return response()->json([
                'status' => 'success',
                'data'   => $mappedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}