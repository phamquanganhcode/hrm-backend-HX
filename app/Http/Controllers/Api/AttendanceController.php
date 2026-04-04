<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\AttendanceRealtimeEvent;
use App\Models\Employee;
use App\Models\DailyAttendance;
use App\Models\AttendanceSession;

class AttendanceController extends Controller
{
    /**
     * Nhận dữ liệu đồng bộ từ máy chấm công (hoặc tool giả lập Python)
     * Tương đương: @app.post("/api/v1/attendance/sync")
     */
    public function sync(Request $request)
    {
        // 1. Kiểm tra khóa bảo mật
        if ($request->secret_key !== 'HoiBaoMat_ChiCuaHangMoiBiet') {
            return response()->json(['status' => 'error', 'message' => 'Sai khóa bảo mật!'], 401);
        }

        $logs = $request->input('logs', []);
        $insertedCount = 0;
        $debugMessages = []; // Mảng lưu lý do bị lỗi để báo về Tool Python

        foreach ($logs as $log) {
            $empCode = $log['employee_code'] ?? 'Không xác định';
            $emp = Employee::where('employee_code', $empCode)->first();
            
            // LỖI 1: Không tìm thấy nhân viên trong DB
            if (!$emp) {
                $debugMessages[] = "Mã [$empCode] không tồn tại trong CSDL MySQL.";
                continue; 
            }

            $scanTime = Carbon::parse($log['check_time']);
            $timeFloat = $scanTime->hour + ($scanTime->minute / 60.0);

            $shiftType = null;
            $actionType = null;
            $logicalDate = $scanTime->format('Y-m-d');

            // 2. Phân loại ca
            if ($timeFloat >= 7.0 && $timeFloat <= 10.5) {
                $shiftType = 'CA_TRUA'; $actionType = 'IN';
            } elseif ($timeFloat >= 13.5 && $timeFloat <= 15.0) {
                $shiftType = 'CA_TRUA'; $actionType = 'OUT';
            } elseif ($timeFloat >= 15.5 && $timeFloat <= 17.5) {
                $shiftType = 'CA_TOI';  $actionType = 'IN';
            } elseif ($timeFloat >= 20.5 || $timeFloat <= 3.0) {
                $shiftType = 'CA_TOI';  $actionType = 'OUT';
                if ($timeFloat <= 3.0) {
                    $logicalDate = $scanTime->copy()->subDay()->format('Y-m-d');
                }
            } else {
                // LỖI 2: Quẹt ngoài khung giờ cho phép
                $debugMessages[] = "Nhân viên [$empCode] bị loại vì quẹt lúc " . $log['check_time'] . " (Ngoài khung giờ ca).";
                continue; 
            }

            // 3. TẠO BẢN GHI
            $record = DailyAttendance::firstOrCreate(
                ['employee_id' => $emp->id, 'date' => $logicalDate],
                [
                    'actual_branch_id' => $request->branch_id ?? 1, 
                    'status' => 'normal', 
                    'is_holiday' => false, 
                    'is_manually_adjusted' => false
                ]
            );

            // 4. LƯU CHI TIẾT
            if (!$record->is_manually_adjusted) {
                $session = AttendanceSession::firstOrCreate([
                    'employee_id'         => $emp->id,
                    'daily_attendance_id' => $record->id,
                    'date'                => $logicalDate,
                ]);

                if ($actionType === 'IN') {
                    if (!$session->check_in_time || $scanTime->lt(Carbon::parse($session->check_in_time))) {
                        $session->check_in_time = $scanTime;
                    }
                } else {
                    if (!$session->check_out_time || $scanTime->gt(Carbon::parse($session->check_out_time))) {
                        $session->check_out_time = $scanTime;
                    }
                }
                $session->save();
                $record->touch();
            }

            $insertedCount++;

            // 5. BẮN PUSHER
            broadcast(new AttendanceRealtimeEvent([
                'message' => 'Có người vừa quẹt thẻ!',
                'employee_code' => $emp->employee_code,
                'employee_name' => $emp->full_name ?? 'Nhân viên ' . $emp->employee_code,
                'check_time' => $log['check_time'],
                'image' => $log['image'] ?? null
            ]))->toOthers();
        }

        // Tùy biến câu trả lời để hiển thị log rõ ràng
        if ($insertedCount == 0) {
            $reason = implode(" | ", $debugMessages);
            $msg = "Đã đồng bộ 0 lần quẹt thẻ. LÝ DO: " . $reason;
        } else {
            $msg = "Đã đồng bộ $insertedCount lần quẹt thẻ thành công!";
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg
        ], 200);
    }

    /**
     * Lấy dữ liệu chấm công của 1 ngày
     * Tương đương: @app.get("/api/attendance/{date}")
     */
    public function getByDate($date)
    {
        // 1. Xác định người đang đăng nhập & Chi nhánh của họ
        $user = auth()->user();
        // Lấy thông tin nhân viên gắn với tài khoản này
        $currentEmp = DB::table('employees')->where('id', $user->employee_id)->first();
        
        // 2. Khởi tạo Query cơ bản
        $schedulesQuery = DB::table('work_schedules')->where('date', $date)->whereNull('deleted_at');
        $attendancesQuery = DB::table('daily_attendances')->where('date', $date)->whereNull('deleted_at');

        // 3. LOGIC PHÂN QUYỀN: Nếu là C1/C2 -> Chỉ lọc nhân viên thuộc chi nhánh mình quản lý
        if ($user && in_array($user->role, ['C1', 'C2']) && $currentEmp) {
            $branchId = $currentEmp->branch_id;

            // Lọc lịch trình: Chỉ lấy lịch của nhân viên thuộc branch_id này
            $schedulesQuery->whereExists(function ($query) use ($branchId) {
                $query->select(DB::raw(1))
                    ->from('employees')
                    ->whereColumn('employees.id', 'work_schedules.employee_id')
                    ->where('employees.branch_id', $branchId);
            });

            // Lọc chấm công: Chỉ lấy bản ghi chấm công tại chi nhánh này
            $attendancesQuery->where('actual_branch_id', $branchId);
        }

        $schedules = $schedulesQuery->get();
        $attendances = $attendancesQuery->get();
        $attDict = $attendances->keyBy('employee_id');

        // 4. Xác định danh sách ID nhân viên cần hiển thị (Có lịch HOẶC có đi làm)
        $empIds = collect($schedules->pluck('employee_id'))
                    ->merge($attendances->pluck('employee_id'))
                    ->unique();

        // 5. Lấy thông tin chi tiết nhân viên & Log quẹt thẻ
        $employees = DB::table('employees')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->whereIn('employees.id', $empIds)
            ->select('employees.*', 'branches.name as branch_name')
            ->get()
            ->keyBy('id');

        $fingerprintIds = $employees->pluck('fingerprint_id')->filter()->toArray();
        $timeLogsRaw = DB::table('time_logs')
            ->whereDate('timestamp', $date)
            ->whereIn('fingerprint_id', $fingerprintIds)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('fingerprint_id');

        $records = [];

        foreach ($empIds as $empId) {
            $emp = $employees->get($empId);
            if (!$emp) continue;

            $att = $attDict->get($empId);
            $sched = $schedules->firstWhere('employee_id', $empId);

            // --- XỬ LÝ LOG QUẸT THẺ REALTIME ---
            $logs = [];
            $rawCheckIn = null;
            $rawCheckOut = null;
            
            if ($emp->fingerprint_id && $timeLogsRaw->has($emp->fingerprint_id)) {
                $empLogs = $timeLogsRaw->get($emp->fingerprint_id);
                $minTime = $empLogs->min('timestamp');
                $maxTime = $empLogs->max('timestamp');
                
                $rawCheckIn = Carbon::parse($minTime)->format('H:i');
                $rawCheckOut = ($minTime !== $maxTime) ? Carbon::parse($maxTime)->format('H:i') : null;

                $logs[] = [
                    'rawCheckIn' => $rawCheckIn,
                    'rawCheckOut' => $rawCheckOut,
                    'isLateIn' => $att ? ($att->late_minutes > 0) : false,
                    'isEarlyOut' => $att ? ($att->early_minutes > 0) : false,
                ];
            }

            // --- XÉT TRẠNG THÁI ---
            $status = $att ? $att->status : 'absent';
            $isOverridden = $att ? (bool) $att->is_manually_adjusted : false;

            $records[] = [
                'id' => $emp->employee_code, 
                'employeeId' => $emp->employee_code,
                'shiftId' => (string) ($sched ? $sched->shift_id : 'Không rõ'),
                'status' => $status,
                'isOverridden' => $isOverridden,
                'overrideDetails' => $isOverridden ? [
                    'reason' => $att->note ?? 'Đã chỉnh sửa thủ công',
                    'overriddenBy' => 'Quản lý'
                ] : null,
                'timeLogs' => empty($logs) ? [['rawCheckIn' => null, 'rawCheckOut' => null, 'isLateIn' => false, 'isEarlyOut' => false]] : $logs,
                'personalInfo' => [
                    'fullName' => $emp->full_name,
                    'avatarUrl' => $emp->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($emp->full_name)
                ],
                'employment' => [
                    'department' => $emp->branch_name ?? 'Chưa phân chi nhánh',
                    'role' => $emp->role ?? 'C0'
                ]
            ];
        }

        return response()->json($records, 200);
    }

    /**
     * Ghi đè chấm công bằng tay (Manager)
     * Tương đương: @app.post("/api/attendance/override")
     */
    public function override(Request $request)
    {
        $date = $request->input('date');
        $empCode = $request->input('employeeId'); // EMP_001
        $newStatus = $request->input('newStatus');
        $reason = $request->input('reason');
        $reqTimeLogs = $request->input('timeLogs');

        $emp = Employee::where('employee_code', $empCode)->first();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên'], 404);
        }

        $schedule = DB::table('work_schedules')->where('date', $date)->where('employee_id', $emp->id)->first();

        DB::beginTransaction();
        try {
            // 1. Cập nhật bảng Daily Attendances (Đã chốt)
            $att = DailyAttendance::where('date', $date)->where('employee_id', $emp->id)->first();

            $attData = [
                'employee_id' => $emp->id,
                'work_schedule_id' => $schedule ? $schedule->id : null,
                'actual_branch_id' => $emp->branch_id,
                'date' => $date,
                'status' => $newStatus,
                'is_manually_adjusted' => true,
                'note' => $reason,
                'updated_at' => now()
            ];

            if ($att) {
                $att->update($attData);
            } else {
                $attData['created_at'] = now();
                DailyAttendance::insert($attData);
            }

            // 2. Chèn Log giả lập vào bảng Time Logs (Nếu quản lý có sửa cả giờ)
            if (is_array($reqTimeLogs) && $emp->fingerprint_id) {
                // Xóa log cũ của ngày hôm đó
                DB::table('time_logs')
                    ->where('fingerprint_id', $emp->fingerprint_id)
                    ->whereDate('timestamp', $date)
                    ->delete();

                $machine = DB::table('timekeep_machines')->first();
                $machineId = $machine ? $machine->id : 1; // Fallback máy chấm công ID = 1

                $insertLogs = [];
                foreach ($reqTimeLogs as $log) {
                    if (!empty($log['rawCheckIn'])) {
                        $insertLogs[] = [
                            'machine_id' => $machineId,
                            'fingerprint_id' => $emp->fingerprint_id,
                            'timestamp' => $date . ' ' . $log['rawCheckIn'] . ':00',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    if (!empty($log['rawCheckOut'])) {
                        $insertLogs[] = [
                            'machine_id' => $machineId,
                            'fingerprint_id' => $emp->fingerprint_id,
                            'timestamp' => $date . ' ' . $log['rawCheckOut'] . ':00',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
                if (!empty($insertLogs)) {
                    DB::table('time_logs')->insert($insertLogs);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ghi đè thành công'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi ghi đè: ' . $e->getMessage()], 500);
        }
    }
}