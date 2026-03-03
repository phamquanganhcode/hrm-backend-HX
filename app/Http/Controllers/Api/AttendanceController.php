<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ShiftDefinition;
use App\Models\ShiftRegistration;

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
    // 1. HÀM TRẢ VỀ CẤU HÌNH CHO MODAL (Hiện danh sách Thứ, Ngày, Ca)
    public function getRegistrationConfig(Request $request)
    {
        $dateParam = $request->query('date', Carbon::now()->addWeek()->toDateString());
        $startOfWeek = Carbon::parse($dateParam)->startOfWeek();

        // Nặn danh sách 7 ngày
        $realDays = [];
        $weekDays = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $realDays[] = [
                'date' => $currentDay->format('Y-m-d'),
                'label' => $weekDays[$currentDay->dayOfWeek]
            ];
        }

        // Nặn danh sách Ca từ Database
        $shifts = ShiftDefinition::where('is_active', true)->get();
        $realShiftTypes = $shifts->map(function($s) {
            return [
                'id' => $s->name, 
                'time' => $s->fe_time_format
            ];
        });

        return response()->json([
            'weekRange' => $startOfWeek->format('d/m/Y') . ' - ' . $startOfWeek->copy()->endOfWeek()->format('d/m/Y'),
            'realDays' => $realDays,
            'realShiftTypes' => $realShiftTypes,
            'shiftDemands' => [], // Bỏ qua nếu cho phép tự do đăng ký
            'fixedOffShifts' => [] 
        ], 200);
    }

    // 2. HÀM LƯU TRỰC TIẾP VÀO BẢNG WORK SCHEDULE
    // public function registerShifts(Request $request)
    // {
    //     $employeeId = $request->user()->employee_id;
    //     $registrations = $request->input('registrations'); 

    //     if (empty($registrations)) {
    //         return response()->json(['message' => 'Không có dữ liệu'], 400);
    //     }

    //     $shiftMap = ShiftDefinition::pluck('id', 'name'); 

    //     try {
    //         DB::beginTransaction();
    //         foreach ($registrations as $date => $shiftNames) {
    //             foreach ($shiftNames as $name) {
    //                 if (isset($shiftMap[$name])) {
    //                     // LƯU THẲNG VÀO LỊCH LÀM VIỆC CHÍNH THỨC
    //                     WorkSchedule::updateOrCreate(
    //                         [
    //                             'employee_id' => $employeeId,
    //                             'date' => $date,
    //                             'shift_id' => $shiftMap[$name]
    //                         ],
    //                         [
    //                             'is_published' => 'Published', // Đăng lên luôn
    //                             'status' => 'Scheduled'        // Sẵn sàng làm việc
    //                         ]
    //                     );
    //                 }
    //             }
    //         }
    //         DB::commit();
    //         return response()->json(['message' => 'Đăng ký thành công!'], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
    //     }
    // }
    // 2. HÀM LƯU VÀO BẢNG NGUYỆN VỌNG (SHIFT REGISTRATIONS)
    public function registerShifts(Request $request)
    {
        $employeeId = $request->user()->employee_id;
        $registrations = $request->input('registrations'); 

        if (empty($registrations)) {
            return response()->json(['message' => 'Không có dữ liệu'], 400);
        }

        $shiftMap = ShiftDefinition::pluck('id', 'name'); 

        try {
            DB::beginTransaction();
            foreach ($registrations as $date => $shiftNames) {
                foreach ($shiftNames as $name) {
                    if (isset($shiftMap[$name])) {
                        // 👉 LƯU VÀO BẢNG SHIFT REGISTRATION THEO ĐÚNG THIẾT KẾ
                        ShiftRegistration::updateOrCreate(
                            [
                                'employee_id' => $employeeId,
                                'request_date' => $date,         // Tên cột chuẩn theo DB của bạn
                                'shift_id' => $shiftMap[$name]
                            ],
                            [
                                'is_assigned' => 0, // Mặc định là 0 (Chưa được quản lý xếp ca)
                                'priority' => 1     // Mặc định độ ưu tiên
                            ]
                        );
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Đăng ký nguyện vọng thành công!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}