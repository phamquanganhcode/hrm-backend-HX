<?php

namespace App\Http\Controllers\Api;

use App\Events\AttendanceRealtimeEvent;
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
            $attendances = \App\Models\DailyAttendance::with(['employee'])
                ->where('actual_branch_id', $branchId)
                ->whereDate('date', $date)
                ->orderBy('updated_at', 'desc') // 🟢 THÊM DÒNG NÀY: Mới cập nhật (quẹt thẻ) sẽ nằm trên cùng
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
    // ==========================================
    // API dành cho Manager: Chấm công Realtime
    // ==========================================
    // ==========================================
    // API dành cho Manager: Chấm công Realtime
    // ==========================================
    public function getRealtimeLogs(Request $request)
    {
        try {
            $account = $request->user();
            $manager = \App\Models\Employee::find($account->employee_id);
            $branchId = $manager->branch_id;
            $date = $request->query('date', now()->toDateString());

            $attendances = \App\Models\DailyAttendance::with(['employee'])
                ->where('actual_branch_id', $branchId)
                ->whereDate('date', $date)
                ->orderBy('created_at', 'desc') 
                ->get();

            $realtimeData = $attendances->map(function($record) {
                $emp = $record->employee;
                
                // 1. Dịch tên Bộ phận
                $deptName = 'Phục vụ';
                if ($emp) {
                    $deptName = match($emp->role) {
                        'C3' => 'Giám đốc',
                        'C2' => 'Quản lý',
                        'C1' => 'Thu ngân / Kế toán',
                        'C0' => 'Bàn / Bếp',
                        default => 'Phục vụ'
                    };
                }

                // 2. Lấy giờ quẹt thẻ chuẩn múi giờ Việt Nam
                $createdAt = \Carbon\Carbon::parse($record->created_at)->setTimezone('Asia/Ho_Chi_Minh');
                $checkInTime = $createdAt->format('H:i:s');
                $hour = $createdAt->hour;
                $minute = $createdAt->minute;

                // 3. Thuật toán "Đoán Ca" và Giờ bắt đầu ca chuẩn
                $shift = 'Sáng';
                $startHour = 8; // Mặc định ca sáng bắt đầu lúc 08:00
                $startMinute = 0;

                if ($hour >= 5 && $hour < 11) {
                    $shift = 'Sáng';
                    $startHour = 8;
                } elseif ($hour >= 11 && $hour < 14) {
                    $shift = 'Trưa';
                    $startHour = 10; // Giả sử ca trưa tính từ 10h
                } elseif ($hour >= 14 && $hour < 18) {
                    $shift = 'Chiều';
                    $startHour = 14; // Ca chiều tính từ 14h
                } elseif ($hour >= 18 && $hour <= 23) {
                    $shift = 'Tối';
                    $startHour = 18; // Ca tối tính từ 18h
                } else {
                    $shift = 'Gãy';
                    $startHour = $hour; // Ca gãy tạm thời không tính muộn
                }

                // 4. Thuật toán tính đi muộn (Mặc định)
                $isLate = false;
                $lateMinutes = 0;
                $checkInTotalMins = ($hour * 60) + $minute;
                $startTotalMins = ($startHour * 60) + $startMinute;

                if ($checkInTotalMins > $startTotalMins) {
                    $isLate = true;
                    $lateMinutes = $checkInTotalMins - $startTotalMins;
                }

                $calculatedStatus = $isLate ? 'ĐI MUỘN' : 'ĐÚNG GIỜ';
                $calculatedNote = $isLate ? "Muộn {$lateMinutes} phút" : '-';

                // 🟢 5. CHỐT TRẠNG THÁI: 
                // Nếu Quản lý ĐÃ SỬA TAY (is_manually_adjusted = true), thì lấy dữ liệu trong Database.
                // Nếu chưa sửa, thì lấy kết quả hệ thống vừa tự động tính (calculated).
                $finalStatus = $record->is_manually_adjusted ? $record->status : $calculatedStatus;
                $finalNote = $record->is_manually_adjusted ? $record->note : $calculatedNote;

                return [
                    'record_id'   => $record->id, 
                    'id'          => $emp ? $emp->employee_code : 'NV000',
                    'name'        => $emp ? $emp->full_name : 'Không xác định',
                    'department'  => $deptName,
                    'shift'       => $shift,
                    'check_in'    => $checkInTime,
                    'check_out'   => '-',
                    'method'      => 'Vân tay',
                    'status'      => $finalStatus, // Lấy trạng thái đã chốt
                    'note'        => $finalNote    // Lấy ghi chú đã chốt
                ];

                // 5. Xác định Trạng thái
                $status = $isLate ? 'ĐI MUỘN' : 'ĐÚNG GIỜ';
                $note = $isLate ? "Muộn {$lateMinutes} phút" : '-';

                return [
                    'record_id'   => $record->id, // 🟢 BẮT BUỘC PHẢI THÊM DÒNG NÀY ĐỂ REACT BIẾT SỬA DÒNG NÀO
                    'id'          => $emp ? $emp->employee_code : 'NV000',
                    'name'        => $emp ? $emp->full_name : 'Không xác định',
                    'department'  => $deptName,
                    'shift'       => $shift, // Cập nhật Ca tự động
                    'check_in'    => $checkInTime,
                    'check_out'   => '-',
                    'method'      => 'Vân tay', // Cố định phương thức theo yêu cầu
                    'status'      => $status,
                    'note'        => $note
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $realtimeData
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    // ==========================================
    // API CỦA MÁY CHẤM CÔNG (IoT) ĐẨY DỮ LIỆU LÊN
    // ==========================================
    // ==========================================
    // API CỦA MÁY CHẤM CÔNG (IoT) ĐẨY DỮ LIỆU LÊN
    // ==========================================
    public function sync(Request $request)
    {
        if ($request->secret_key !== 'HoiBaoMat_ChiCuaHangMoiBiet') {
            return response()->json(['status' => 'error', 'message' => 'Sai khóa bảo mật!'], 401);
        }

        $logs = $request->logs;
        $today = now()->toDateString();
        $insertedCount = 0;

        foreach ($logs as $log) {
            $emp = \App\Models\Employee::where('employee_code', $log['employee_code'])->first();
            
            if ($emp) {
                // 🟢 THAY ĐỔI CHÍNH Ở ĐÂY: Dùng create() để TẠO MỚI HOÀN TOÀN một dòng
                $newRecord = \App\Models\DailyAttendance::create([
                    'employee_id'          => $emp->id,
                    'date'                 => $today,
                    'actual_branch_id'     => $request->branch_id ?? 1,
                    'total_work_hours'     => 0,
                    'late_minutes'         => 0,
                    'early_minutes'        => 0,
                    'overtime_hours'       => 0,
                    'status'               => 'CÓ MẶT', 
                    'is_holiday'           => false,
                    'is_manually_adjusted' => false,
                ]);

                $insertedCount++;
                // 🟢 ĐÂY LÀ DÒNG BÓP CÒ BẮN TÍN HIỆU LÊN PUSHER
                // Gửi data đơn giản thôi (Chỉ cần ID) để báo cho React biết có biến
                broadcast(new AttendanceRealtimeEvent([
                    'message' => 'Có người vừa quẹt thẻ!',
                    'employee_code' => $emp->employee_code
                ]));
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Đã tạo mới $insertedCount dòng chấm công."
        ], 200);
    }
    // ==========================================
    // API CẬP NHẬT XỬ LÝ NGOẠI LỆ TỪ QUẢN LÝ
    // ==========================================
    public function updateException(Request $request)
    {
        try {
            // Tìm đúng dòng dữ liệu trong Database
            $record = \App\Models\DailyAttendance::find($request->record_id);
            
            if ($record) {
                $record->status = $request->status;
                $record->note = $request->note;
                $record->is_manually_adjusted = true; // Đánh dấu là đã bị Quản lý can thiệp
                $record->save();

                return response()->json(['status' => 'success', 'message' => 'Cập nhật thành công!']);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bản ghi!'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}