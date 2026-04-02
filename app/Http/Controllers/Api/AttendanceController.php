<?php

namespace App\Http\Controllers\Api;

use App\Events\AttendanceRealtimeEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyAttendance;
use App\Models\AttendanceSession; // 🟢 Gọi bảng Session của bạn vào
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // ==========================================
    // 1. API ĐÓN DỮ LIỆU TỪ MÁY CHẤM CÔNG (HYBRID: VÙNG THỜI GIAN + MIN-MAX)
    // ==========================================
    public function sync(Request $request)
    {
        if ($request->secret_key !== 'HoiBaoMat_ChiCuaHangMoiBiet') {
            return response()->json(['status' => 'error', 'message' => 'Sai khóa bảo mật!'], 401);
        }

        $logs = $request->logs;
        $insertedCount = 0;

        foreach ($logs as $log) {
            $emp = Employee::where('employee_code', $log['employee_code'])->first();
            
            if ($emp) {
                $scanTime = \Carbon\Carbon::parse($log['check_time']);
                
                // Quy đổi giờ ra số thập phân (Ví dụ 10:30 -> 10.5) để dễ so sánh vùng
                $timeFloat = $scanTime->hour + ($scanTime->minute / 60.0);

                // 🟢 BƯỚC 1: ĐƯA VÀO PHỄU LỌC 4 VÙNG THỜI GIAN
                $shiftType = null;
                $actionType = null;
                $logicalDate = $scanTime->format('Y-m-d');

                if ($timeFloat >= 7.0 && $timeFloat <= 10.5) {
                    // Vùng 1: 07:00 - 10:30
                    $shiftType = 'CA_TRUA';
                    $actionType = 'IN';
                } elseif ($timeFloat >= 13.5 && $timeFloat <= 15.0) {
                    // Vùng 2: 13:30 - 15:00
                    $shiftType = 'CA_TRUA';
                    $actionType = 'OUT';
                } elseif ($timeFloat >= 15.5 && $timeFloat <= 17.5) {
                    // Vùng 3: 15:30 - 17:30
                    $shiftType = 'CA_TOI';
                    $actionType = 'IN';
                } elseif ($timeFloat >= 20.5 || $timeFloat <= 3.0) {
                    // Vùng 4: 20:30 - 03:00 sáng hôm sau
                    $shiftType = 'CA_TOI';
                    $actionType = 'OUT';
                    // Nếu khách nhậu đến 1h-3h sáng, lùi ngày về hôm qua để tính công
                    if ($timeFloat <= 3.0) {
                        $logicalDate = $scanTime->copy()->subDay()->format('Y-m-d');
                    }
                } else {
                    // QUẸT NGOÀI 4 VÙNG NÀY -> RÁC -> BỎ QUA LUÔN
                    continue; 
                }

                // 🟢 BƯỚC 2: TÌM HOẶC TẠO BẢN GHI TỔNG TRONG NGÀY
                $record = DailyAttendance::firstOrCreate(
                    ['employee_id' => $emp->id, 'date' => $logicalDate],
                    [
                        'actual_branch_id' => $request->branch_id ?? 1, 
                        'status' => 'CÓ MẶT', 
                        'is_holiday' => false, 
                        'is_manually_adjusted' => false
                    ]
                );

                // 🟢 BƯỚC 3: TÌM PHIÊN LÀM VIỆC (SESSION) CỦA CA TƯƠNG ỨNG
                $session = AttendanceSession::where('daily_attendance_id', $record->id)
                    ->where(function ($q) use ($shiftType, $logicalDate) {
                        if ($shiftType === 'CA_TRUA') {
                            // Ranh giới tìm Session Ca Trưa
                            $q->whereBetween('check_in_time', [$logicalDate . ' 07:00:00', $logicalDate . ' 15:00:00'])
                              ->orWhereBetween('check_out_time', [$logicalDate . ' 07:00:00', $logicalDate . ' 15:00:00']);
                        } else {
                            // Ranh giới tìm Session Ca Tối (Kéo dài đến 3h sáng hôm sau)
                            $start = $logicalDate . ' 15:30:00';
                            $end = \Carbon\Carbon::parse($logicalDate)->addDay()->format('Y-m-d') . ' 03:00:00';
                            $q->whereBetween('check_in_time', [$start, $end])
                              ->orWhereBetween('check_out_time', [$start, $end]);
                        }
                    })
                    ->first();

                // 🟢 BƯỚC 4: LƯU DỮ LIỆU BẰNG THUẬT TOÁN MIN - MAX (CHỐNG GHI ĐÈ)
                // CHỈ cập nhật giờ nếu Quản lý CHƯA chốt tay sửa đổi
                if (!$record->is_manually_adjusted) {
                    if (!$session) {
                        // Lần đầu tiên có dữ liệu của Ca này
                        $newSession = new AttendanceSession([
                            'employee_id'         => $emp->id,
                            'daily_attendance_id' => $record->id,
                            'date'                => $logicalDate,
                            'check_in_time'       => null,
                            'check_out_time'      => null,
                        ]);

                        if ($actionType === 'IN') {
                            $newSession->check_in_time = $scanTime;
                        } else {
                            $newSession->check_out_time = $scanTime;
                        }
                        $newSession->save();
                    } else {
                        // Đã có phiên, bắt đầu so sánh để lấy MIN (cho IN) hoặc MAX (cho OUT)
                        if ($actionType === 'IN') {
                            if (!$session->check_in_time || $scanTime->lt(\Carbon\Carbon::parse($session->check_in_time))) {
                                $session->check_in_time = $scanTime;
                            }
                        } else {
                            if (!$session->check_out_time || $scanTime->gt(\Carbon\Carbon::parse($session->check_out_time))) {
                                $session->check_out_time = $scanTime;
                            }
                        }
                        $session->save();
                    }
                    
                    // Chạm vào record tổng để báo React cập nhật lên Top
                    $record->touch();
                } else {
                    // Nếu quản lý đã sửa (is_manually_adjusted = true) -> Bỏ qua, không cập nhật giờ nữa.
                    // (Bạn có thể ghi 1 log ẩn ở đây nếu cần theo dõi)
                }

                // Chạm vào record tổng để báo React cập nhật lên Top
                $record->touch();
                $insertedCount++;

                broadcast(new AttendanceRealtimeEvent([
                    'message' => 'Có người vừa quẹt thẻ!',
                    'employee_code' => $emp->employee_code
                ]));
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Đã đồng bộ $insertedCount lần quẹt thẻ (Hybrid Time-Windows)."
        ], 200);
    }

    // ==========================================
    // 2. API DÀNH CHO QUẢN LÝ (MANAGER): CHẤM CÔNG REALTIME
    // ==========================================
    public function getRealtimeLogs(Request $request)
    {
        try {
            $account = $request->user();
            $manager = Employee::find($account->employee_id);
            $branchId = $manager ? $manager->branch_id : 1;
            $date = $request->query('date', now()->toDateString());

            $attendances = DailyAttendance::with(['employee'])
                ->where('actual_branch_id', $branchId)
                ->whereDate('date', $date)
                ->orderBy('updated_at', 'desc') 
                ->get();

            $realtimeData = $attendances->map(function($record) {
                $emp = $record->employee;
                
                // Lấy thông tin phiên làm việc
                $session = AttendanceSession::where('daily_attendance_id', $record->id)->first();
                
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

                // 🟢 LẤY GIỜ TỪ BẢNG SESSION CỦA BẠN
                $checkInTime = ($session && $session->check_in_time) ? Carbon::parse($session->check_in_time)->format('H:i:s') : '-';
                $checkOutTime = ($session && $session->check_out_time) ? Carbon::parse($session->check_out_time)->format('H:i:s') : '-';
                
                // Thuật toán "Đoán Ca" và Tính đi muộn
                $shift = 'Chưa xác định';
                $isLate = false;
                $lateMinutes = 0;

                if ($session && $session->check_in_time) {
                    $inCarbon = Carbon::parse($session->check_in_time);
                    $hour = $inCarbon->hour;
                    $minute = $inCarbon->minute;
                    
                    $startHour = 8; $startMinute = 0;

                    if ($hour >= 5 && $hour < 11) {
                        $shift = 'Sáng'; $startHour = 8;
                    } elseif ($hour >= 11 && $hour < 14) {
                        $shift = 'Trưa'; $startHour = 10;
                    } elseif ($hour >= 14 && $hour < 18) {
                        $shift = 'Chiều'; $startHour = 14;
                    } elseif ($hour >= 18 && $hour <= 23) {
                        $shift = 'Tối'; $startHour = 18;
                    } else {
                        $shift = 'Gãy'; $startHour = $hour;
                    }

                    $checkInTotalMins = ($hour * 60) + $minute;
                    $startTotalMins = ($startHour * 60) + $startMinute;

                    if ($checkInTotalMins > $startTotalMins) {
                        $isLate = true;
                        $lateMinutes = $checkInTotalMins - $startTotalMins;
                    }
                }

                $calculatedStatus = $isLate ? 'ĐI MUỘN' : 'ĐÚNG GIỜ';
                $calculatedNote = $isLate ? "Muộn {$lateMinutes} phút" : '-';

                $finalStatus = $record->is_manually_adjusted ? $record->status : $calculatedStatus;
                $finalNote = $record->is_manually_adjusted ? $record->note : $calculatedNote;

                return [
                    'record_id'   => $record->id, 
                    'id'          => $emp ? $emp->employee_code : 'NV000',
                    'name'        => $emp ? $emp->full_name : 'Không xác định',
                    'department'  => $deptName,
                    'shift'       => $shift,
                    'check_in'    => $checkInTime, // 🟢 Dữ liệu từ bảng Session
                    'check_out'   => $checkOutTime,// 🟢 Dữ liệu từ bảng Session
                    'method'      => 'Vân tay',
                    'status'      => $finalStatus, 
                    'note'        => $finalNote    
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
    // 3. API CẬP NHẬT XỬ LÝ NGOẠI LỆ TỪ QUẢN LÝ (CÓ LƯU LOG)
    // ==========================================
    public function updateException(Request $request)
    {
        try {
            $record = DailyAttendance::find($request->record_id);
            
            if ($record) {
                // 1. CHỤP LẠI DỮ LIỆU CŨ TRƯỚC KHI SỬA
                $oldValue = [
                    'status' => $record->status,
                    'note' => $record->note,
                    'is_manually_adjusted' => $record->is_manually_adjusted
                ];

                // 2. CẬP NHẬT DỮ LIỆU MỚI
                $record->status = $request->status;
                $record->note = $request->note;
                $record->is_manually_adjusted = true; 
                $record->save();

                // 3. CHỤP LẠI DỮ LIỆU MỚI
                $newValue = [
                    'status' => $record->status,
                    'note' => $record->note,
                    'is_manually_adjusted' => true
                ];

                // 4. LƯU VÀO BẢNG NHẬT KÝ HỆ THỐNG (SYSTEM LOG)
                // Lấy ID của người đang thao tác (Quản lý)
                $actorId = $request->user()->id; 

                \App\Models\SystemLog::create([
                    'actor_id'     => $actorId,
                    'action'       => 'MANUAL_UPDATE_ATTENDANCE', // Tên hành động
                    'target_table' => 'daily_attendances',
                    'target_id'    => $record->id,
                    'old_value'    => json_encode($oldValue, JSON_UNESCAPED_UNICODE),
                    'new_value'    => json_encode($newValue, JSON_UNESCAPED_UNICODE),
                    'created_at'   => now()
                ]);

                return response()->json(['status' => 'success', 'message' => 'Cập nhật thành công!']);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bản ghi!'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. API DÀNH CHO KẾ TOÁN: BẢNG CÔNG TỔNG HỢP NGÀY
    // ==========================================
    public function getDailyAttendances(Request $request)
    {
        try {
            $branchId = $request->query('branch_id', 1);
            $date = $request->query('date', now()->toDateString());

            $attendances = DailyAttendance::with(['employee'])
                ->where('actual_branch_id', $branchId)
                ->whereDate('date', $date)
                ->orderBy('updated_at', 'desc')
                ->get();

            $mappedData = $attendances->map(function($record) {
                
                // 🟢 TÍNH GIỜ LÀM TỪ BẢNG SESSION
                // Sửa dòng này trong hàm getRealtimeLogs:
                $session = AttendanceSession::where('daily_attendance_id', $record->id)
                            ->orderBy('updated_at', 'desc') // Lấy ca vừa quẹt thẻ mới nhất để hiển thị lên bảng
                            ->first();
                $actualHours = 0;
                $lateEarlyStr = '0';
                $isLate = false;

                if ($session && $session->check_in_time && $session->check_out_time) {
                    $in = Carbon::parse($session->check_in_time);
                    $out = Carbon::parse($session->check_out_time);
                    
                    // Tính tổng số phút giữa In và Out
                    $totalMinutes = $out->diffInMinutes($in);
                    $actualHours = round($totalMinutes / 60, 2);
                }

                // Tính toán thông tin hiển thị (có thể nâng cấp thêm nếu muốn)
                if ($record->late_minutes > 0) {
                    $lateEarlyStr = $record->late_minutes . 'p';
                    $isLate = true;
                } elseif ($record->early_minutes > 0) {
                    $lateEarlyStr = 'Sớm ' . $record->early_minutes . 'p';
                }

                return [
                    'id'                   => $record->id,
                    'emp_code'             => $record->employee ? $record->employee->employee_code : 'NV000',
                    'emp_name'             => $record->employee ? $record->employee->full_name : 'Không xác định',
                    'status'               => $record->status ?? 'CHỜ DUYỆT',
                    'standard_hours'       => 8.0, 
                    'actual_hours'         => $actualHours, // 🟢 Giờ thực tế lấy từ Session
                    'late_early'           => $lateEarlyStr,
                    'is_late'              => $isLate,
                    'ot_hours'             => (float) $record->overtime_hours,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $mappedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}