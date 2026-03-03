<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkSchedule;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function getWeeklySchedule(Request $request)
    {
        // 1. NHẬN DỮ LIỆU THÔ TỪ FE
        $passedEmployeeId = $request->query('employee_id');
        $passedStartDate = $request->query('start_date');

        // 2. THUẬT TOÁN "GÁNH TEAM" FRONTEND (Sửa lỗi FE truyền nhầm ngày vào ID)
        if ($passedEmployeeId && preg_match('/^\d{4}-\d{2}-\d{2}$/', $passedEmployeeId)) {
            $passedStartDate = $passedEmployeeId; // Cứu lấy cái ngày
            $passedEmployeeId = null;             // Xóa bỏ cái ID sai
        }

        // 3. CHỐT GIÁ TRỊ CHUẨN
        $dateParam = $passedStartDate ?: Carbon::now()->toDateString();
        // Nếu không có employee_id hợp lệ, lấy ID của người đang đăng nhập
        $employeeId = $passedEmployeeId ?: $request->user()->employee_id;
        
        $startOfWeek = Carbon::parse($dateParam)->startOfWeek()->toDateString();
        $endOfWeek = Carbon::parse($dateParam)->endOfWeek()->toDateString();

        // 4. QUERY BẢNG MỚI (Dùng with để lấy quan hệ)
        $schedules = WorkSchedule::with(['shift', 'workBranch'])
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->where('is_published', 'Published') 
            ->where('status', '!=', 'Canceled')
            ->orderBy('date', 'asc')
            ->get();

        // 5. MAPPING CHUẨN FORM FRONTEND
        $formattedSchedules = $schedules->map(function ($work) {
            return [
                'id'        => $work->id,
                'date'      => $work->date,
                'shiftName' => $work->shift ? $work->shift->name : 'Ca không xác định',
                'time'      => $work->shift ? $work->shift->fe_time_format : 'Chưa xếp giờ',
                'location'  => $work->workBranch ? $work->workBranch->name : 'Chưa phân bổ',
                'note'      => $work->status === 'Completed' ? 'Đã hoàn thành' : ''
            ];
        });

        return response()->json($formattedSchedules, 200);
    }
}