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
    $employeeId = $request->user()->employee_id;
    $date = $request->query('date', now()->addWeek()->startOfWeek()->toDateString());
    
    // 1. Lấy danh sách 7 ngày của tuần cần đăng ký
    $startOfWeek = \Carbon\Carbon::parse($date)->startOfWeek();
    $displayDays = [];
    for ($i = 0; $i < 7; $i++) {
        $currentDay = $startOfWeek->copy()->addDays($i);
        $displayDays[] = [
            'label' => 'Thứ ' . ($i + 2 == 8 ? 'Nhật' : $i + 2),
            'date' => $currentDay->toDateString()
        ];
    }

    // 2. Lấy 5 ca làm việc (Sáng, Trưa, Chiều, Tối, Gãy)
    $shiftTypes = \App\Models\ShiftDefinition::where('is_active', true)
        ->get(['id', 'name', 'start_time', 'end_time']);

    // 3. Tính toán shiftDemands (Định mức & Đã đăng ký)
    $shiftDemands = [];
    foreach ($displayDays as $day) {
        foreach ($shiftTypes as $shift) {
            // Đếm số người ĐÃ đăng ký ca này trong DB
            $registeredCount = \App\Models\ShiftRegistration::where('request_date', $day['date'])
                ->where('shift_id', $shift->id)
                ->count();
            
            // Giả định số người CẦN (Required). Bạn có thể lấy từ 1 bảng config hoặc để mặc định là 5
            $requiredCount = 5; 

            $shiftDemands[$day['date']][$shift->name] = [
                'registered' => $registeredCount,
                'required' => $requiredCount
            ];
        }
    }

    // 4. Tính toán fixedOffShifts (Ca bị khóa của nhân viên này)
    $fixedOffShifts = [];
    $employeeSchedules = \App\Models\EmployeeSchedule::where('employee_id', $employeeId)
        ->where('status', 'Active')
        ->get();

    foreach ($displayDays as $day) {
        $dayOfWeek = \Carbon\Carbon::parse($day['date'])->dayOfWeekIso; // 1=Thứ 2... 7=Chủ Nhật
        
        foreach ($employeeSchedules as $schedule) {
            if ($schedule->day_of_week == $dayOfWeek) {
                if (!isset($fixedOffShifts[$day['date']])) {
                    $fixedOffShifts[$day['date']] = [];
                }
                // Thêm tên ca bị khóa vào mảng
                $fixedOffShifts[$day['date']][] = $schedule->shift->name;
            }
        }
    }

    return response()->json([
        'weekRange' => $displayDays[0]['date'] . ' - ' . $displayDays[6]['date'],
        'realDays' => $displayDays,
        'realShiftTypes' => $shiftTypes->map(function($s) {
            return ['id' => $s->name, 'time' => \Carbon\Carbon::parse($s->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($s->end_time)->format('H:i')];
        }),
        'shiftDemands' => (object)$shiftDemands, // Ép kiểu object để JSON trả về {} thay vì []
        'fixedOffShifts' => (object)$fixedOffShifts
    ]);
}
    public function getMyRegistrations(Request $request)
    {
        $employeeId = $request->user()->employee_id; // Lấy mã nhân viên đang đăng nhập
        $date = $request->query('date', now()->toDateString());

        // Tìm ngày đầu tuần và cuối tuần
        $startOfWeek = \Carbon\Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = \Carbon\Carbon::parse($date)->endOfWeek()->toDateString();

        // Query bảng nguyện vọng
        $registrations = \App\Models\ShiftRegistration::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('request_date', [$startOfWeek, $endOfWeek])
            ->get();

        // Format lại dữ liệu cho Frontend khớp với initialSelected
        $result = [];
        foreach ($registrations as $reg) {
            $dateStr = $reg->request_date;
            // Kiểm tra xem shift có tồn tại không để tránh lỗi
            if ($reg->shift) {
                $shiftName = $reg->shift->name; 
                
                if (!isset($result[$dateStr])) {
                    $result[$dateStr] = [];
                }
                $result[$dateStr][] = $shiftName;
            }
        }

        return response()->json($result);
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