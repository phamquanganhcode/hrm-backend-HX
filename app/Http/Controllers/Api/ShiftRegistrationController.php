<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftDefinition; 
use App\Models\ShiftRegistration;
use Carbon\Carbon;

class ShiftRegistrationController extends Controller
{
    // 1. LẤY CẤU HÌNH ĐĂNG KÝ CA
    public function getRegistrationConfig(Request $request)
    {
        $dateParam = $request->query('date', Carbon::now()->addWeek()->startOfWeek()->toDateString());
        $startOfWeek = Carbon::parse($dateParam)->startOfWeek();
        $endOfWeek = Carbon::parse($dateParam)->endOfWeek();

        $weekRange = $startOfWeek->format('d/m') . ' - ' . $endOfWeek->format('d/m/Y');
        $employeeId = $request->user()->employee_id;

        // A. Ngày trong tuần
        $realDays = [];
        $dayNames = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i);
            $realDays[] = [
                'date' => $currentDate->toDateString(),
                'label' => $dayNames[$currentDate->dayOfWeek]
            ];
        }

        // B. Danh sách ca
        $shifts = ShiftDefinition::all();
        $realShiftTypes = $shifts->map(function($shift) {
            return [
                'id' => $shift->name, // FE vẫn cần chữ "Ca Sáng"
                'time' => $shift->fe_time_format ?? '08:00 - 14:00'
            ];
        });

        $shiftDemands = [];
        $fixedOffShifts = [];

        // ⚠️ ĐÃ ĐỔI: Dùng cột 'request_date' theo chuẩn CSDL mới
        $allRegistrations = ShiftRegistration::whereBetween('request_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])->get();
        $myRegistrations = $allRegistrations->where('employee_id', $employeeId);

        foreach ($realDays as $day) {
            $dateStr = $day['date'];
            $shiftDemands[$dateStr] = [];
            $fixedOffShifts[$dateStr] = [];

            foreach ($shifts as $shift) {
                $shiftName = $shift->name;
                $shiftId = $shift->id; // ID của ca

                // ⚠️ ĐÃ ĐỔI: Lọc theo 'shift_id' và 'request_date'
                $registeredCount = $allRegistrations->where('request_date', $dateStr)->where('shift_id', $shiftId)->count();
                $requiredCount = $shift->required_staff ?? 5; // Có thể linh hoạt số lượng

                $shiftDemands[$dateStr][$shiftName] = [
                    'registered' => $registeredCount,
                    'required' => $requiredCount
                ];

                $isFull = $registeredCount >= $requiredCount;
                $isAlreadyRegistered = $myRegistrations->where('request_date', $dateStr)->where('shift_id', $shiftId)->isNotEmpty();

                if ($isFull || $isAlreadyRegistered) {
                    $fixedOffShifts[$dateStr][] = $shiftName;
                }
            }
        }

        return response()->json([
            'weekRange' => $weekRange,
            'realDays' => $realDays,
            'realShiftTypes' => $realShiftTypes,
            'shiftDemands' => $shiftDemands,
            'fixedOffShifts' => $fixedOffShifts
        ], 200);
    }

    // 2. LƯU ĐĂNG KÝ CA LÀM
    public function registerShifts(Request $request)
    {
        // 1. Lấy thông tin nhân viên từ Token
        $employeeId = $request->user()->employee_id;
        $registrations = $request->input('registrations'); // Nhận { "date": ["Sáng", "Gãy"] }

        if (empty($registrations)) {
            return response()->json(['message' => 'Không có dữ liệu đăng ký'], 400);
        }

        // 2. Lấy bản đồ tên ca sang ID để lưu vào cột shift_id
        $shiftMap = ShiftDefinition::pluck('id', 'name'); // VD: ['Sáng' => 1, 'Gãy' => 3]

        try {
            DB::beginTransaction();

            // Duyệt qua từng ngày và từng ca được chọn
            foreach ($registrations as $date => $shiftNames) {
                foreach ($shiftNames as $name) {
                    if (isset($shiftMap[$name])) {
                        // Lưu hoặc cập nhật nguyện vọng
                        ShiftRegistration::updateOrCreate(
                            [
                                'employee_id'  => $employeeId,
                                'request_date' => $date, // Cột request_date trong thiết kế
                                'shift_id'     => $shiftMap[$name],
                            ],
                            [
                                'priority'    => 1, // Mặc định ưu tiên 1
                                'is_assigned' => false, // Chưa được phân ca chính thức
                            ]
                        );
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Lưu nguyện vọng thành công!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}