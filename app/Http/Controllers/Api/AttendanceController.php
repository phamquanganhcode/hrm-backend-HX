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
     */
    public function sync(Request $request)
    {
        if ($request->secret_key !== 'HoiBaoMat_ChiCuaHangMoiBiet') {
            return response()->json(['status' => 'error', 'message' => 'Sai khóa bảo mật!'], 401);
        }

        $logs = $request->input('logs', []);
        $insertedCount = 0;

        foreach ($logs as $log) {
            $empCode = $log['employee_code'] ?? 'Không xác định';
            $emp = Employee::where('employee_code', $empCode)->first();
            
            if (!$emp) continue; 

            $scanTime = Carbon::parse($log['check_time']);
            $logicalDate = $scanTime->format('Y-m-d');
            $minutes = $scanTime->hour * 60 + $scanTime->minute;
            
            // Xử lý qua nửa đêm
            if ($minutes <= 180) {
                $logicalDate = $scanTime->copy()->subDay()->format('Y-m-d');
            }

            // TẠO BẢN GHI
            $record = DailyAttendance::firstOrCreate(
                ['employee_id' => $emp->id, 'date' => $logicalDate],
                [
                    'actual_branch_id' => $request->branch_id ?? 1, 
                    'status' => 'normal', 
                    'is_holiday' => false, 
                    'is_manually_adjusted' => false
                ]
            );

            // LƯU CHI TIẾT VÀO TIME_LOGS
            if (!$record->is_manually_adjusted) {
                // 1. Ép cấp vân tay 
                $fingerprintId = $emp->fingerprint_id ?? $emp->id;
                if (!$emp->fingerprint_id) {
                    DB::table('employees')->where('id', $emp->id)->update(['fingerprint_id' => $fingerprintId]);
                }

                // 2. Xử lý Máy chấm công an toàn tuyệt đối
                $machine = DB::table('timekeep_machines')->first();
                
                if ($machine) {
                    $machineId = $machine->id;
                } else {
                    // Nếu chưa có máy nào, tự động đẻ ra máy ảo
                    $machineId = DB::table('timekeep_machines')->insertGetId([
                        'name' => 'Máy giả lập',
                        'branch_id' => $emp->branch_id,
                        'status' => 'active',
                        'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
                    ]);
                }

                // 3. Chèn giờ trực tiếp
                DB::table('time_logs')->insert([
                    'machine_id' => $machineId,
                    'fingerprint_id' => $fingerprintId,
                    'timestamp' => $scanTime->format('Y-m-d H:i:s'),
                    'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
                ]);
                $record->touch();
            }

            $insertedCount++;

            // BẮN PUSHER LÊN FRONTEND
            broadcast(new AttendanceRealtimeEvent([
                'message' => 'Có người vừa quẹt thẻ!',
                'employee_code' => $emp->employee_code,
                'employee_name' => $emp->full_name ?? 'Nhân viên ' . $emp->employee_code,
                'check_time' => $scanTime->format('H:i'), // Rút gọn thời gian hiển thị trên popup
                'image' => $log['image'] ?? null
            ]))->toOthers();
        }

        return response()->json(['status' => 'success', 'message' => "Đồng bộ $insertedCount bản ghi!"], 200);
    }

    public function getByDate($date)
    {
        $user = auth()->user();
        $currentEmp = DB::table('employees')->where('id', $user->employee_id)->first();
        
        $schedulesQuery = DB::table('work_schedules')->where('date', $date)->whereNull('deleted_at');
        $attendancesQuery = DB::table('daily_attendances')->where('date', $date)->whereNull('deleted_at');

        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2']) && $currentEmp) {
            $branchId = $currentEmp->branch_id;
            $schedulesQuery->whereExists(function ($query) use ($branchId) {
                $query->select(DB::raw(1))->from('employees')->whereColumn('employees.id', 'work_schedules.employee_id')->where('employees.branch_id', $branchId);
            });
            $attendancesQuery->where('actual_branch_id', $branchId);
        }

        $schedules = $schedulesQuery->get();
        $attendances = $attendancesQuery->get();
        $attDict = $attendances->keyBy('employee_id');

        $empIds = collect($schedules->pluck('employee_id'))->merge($attendances->pluck('employee_id'))->unique();

        $employees = DB::table('employees')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->whereIn('employees.id', $empIds)
            ->select('employees.*', 'branches.name as branch_name', 'employees.department as department')
            ->get()
            ->keyBy('id');

        // Lấy toàn bộ log trong ngày 
        $timeLogsRaw = DB::table('time_logs')
            ->whereDate('timestamp', $date)
            ->whereNull('deleted_at')
            ->get();

        $records = [];

        foreach ($empIds as $empId) {
            $emp = $employees->get($empId);
            if (!$emp) continue;

            $att = $attDict->get($empId);
            $sched = $schedules->firstWhere('employee_id', $empId);

            $milestones = [
                '9h'  => ['time' => null, 'status' => 'MISSING'],
                '14h' => ['time' => null, 'status' => 'MISSING'],
                '16h' => ['time' => null, 'status' => 'MISSING'],
                '21h' => ['time' => null, 'status' => 'MISSING'],
            ];

            if ($emp->fingerprint_id) {
                $fId = $emp->fingerprint_id;
                // Lọc bằng == để bắt dính cả String lẫn Int
                $empLogs = $timeLogsRaw->filter(function($log) use ($fId) {
                    return $log->fingerprint_id == $fId;
                })->sortBy('timestamp');

                foreach ($empLogs as $log) {
                    $time = Carbon::parse($log->timestamp);
                    $minutes = $time->hour * 60 + $time->minute; 
                    $formattedTime = $time->format('H:i');

                    if ($minutes >= 420 && $minutes <= 660) {
                        if (!$milestones['9h']['time']) {
                            $milestones['9h']['time'] = $formattedTime;
                            $milestones['9h']['status'] = ($minutes <= 555) ? 'ON_TIME' : 'LATE'; 
                        }
                    } elseif ($minutes >= 780 && $minutes <= 900) {
                        if (!$milestones['14h']['time']) {
                            $milestones['14h']['time'] = $formattedTime;
                            $milestones['14h']['status'] = ($minutes <= 855) ? 'ON_TIME' : 'LATE'; 
                        }
                    } elseif ($minutes >= 901 && $minutes <= 1080) {
                        if (!$milestones['16h']['time']) {
                            $milestones['16h']['time'] = $formattedTime;
                            $milestones['16h']['status'] = ($minutes <= 975) ? 'ON_TIME' : 'LATE'; 
                        }
                    } elseif ($minutes >= 1200 || $minutes <= 180) {
                        if (!$milestones['21h']['time']) {
                            $milestones['21h']['time'] = $formattedTime;
                            $milestones['21h']['status'] = ($minutes <= 1275 && $minutes > 180) ? 'ON_TIME' : 'LATE'; 
                        }
                    }
                }
            }

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
                'milestones' => $milestones,
                'personalInfo' => [
                    'fullName' => $emp->full_name,
                    'avatarUrl' => $emp->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($emp->full_name)
                ],
                'employment' => [
                    'department' => $emp->department ?? 'Chưa phân tổ',
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
        $empCode = $request->input('employeeId'); 
        $newStatus = $request->input('newStatus');
        $reason = $request->input('reason');
        $reqTimeLogs = $request->input('timeLogs');

        // 1. Tìm nhân viên cần ghi đè 
        $emp = DB::table('employees')->where('employee_code', $empCode)->first();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên'], 404);
        }

        // 2. BẢO MẬT: Kiểm tra quyền
        $user = auth()->user();
        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            
            if (!$manager || $manager->branch_id != $emp->branch_id) {
                return response()->json(['success' => false, 'message' => 'Bạn không có quyền ghi đè chấm công của cơ sở khác!'], 403);
            }
        }

        $schedule = DB::table('work_schedules')->where('date', $date)->where('employee_id', $emp->id)->first();

        DB::beginTransaction();
        try {
            // 3. Cập nhật bảng Daily Attendances
            $att = DB::table('daily_attendances')->where('date', $date)->where('employee_id', $emp->id)->first();

            $attData = [
                'employee_id' => $emp->id,
                'work_schedule_id' => $schedule ? $schedule->id : null,
                'actual_branch_id' => $emp->branch_id,
                'date' => $date,
                'status' => $newStatus,
                'is_manually_adjusted' => true,
                'note' => $reason,
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') 
            ];

            if ($att) {
                DB::table('daily_attendances')->where('id', $att->id)->update($attData);
            } else {
                $attData['created_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                DB::table('daily_attendances')->insert($attData);
            }

            // 4. Chèn Log giả lập vào bảng Time Logs
            if (is_array($reqTimeLogs)) {
                
                // CẤP ID VÂN TAY
                $fingerprintId = $emp->fingerprint_id ?? $emp->id;
                if (!$emp->fingerprint_id) {
                    DB::table('employees')->where('id', $emp->id)->update([
                        'fingerprint_id' => $fingerprintId
                    ]);
                }

                // Xóa log cũ của ngày hôm đó
                DB::table('time_logs')
                    ->where('fingerprint_id', $fingerprintId)
                    ->whereDate('timestamp', $date)
                    ->delete();

                // KIỂM TRA MÁY CHẤM CÔNG (Giống hệt hàm Sync)
                $machine = DB::table('timekeep_machines')->first();
                if ($machine) {
                    $machineId = $machine->id;
                } else {
                    $machineId = DB::table('timekeep_machines')->insertGetId([
                        'name' => 'Máy giả lập (Override)',
                        'branch_id' => $emp->branch_id,
                        'status' => 'active',
                        'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
                    ]);
                }

                $insertLogs = [];
                foreach ($reqTimeLogs as $log) {
                    if (!empty($log['rawCheckIn'])) {
                        $timeIn = date('H:i:s', strtotime($log['rawCheckIn']));
                        $insertLogs[] = [
                            'machine_id' => $machineId, // <--- SỬ DỤNG MACHINE CHUẨN XÁC
                            'fingerprint_id' => $fingerprintId,
                            'timestamp' => $date . ' ' . $timeIn,
                            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
                        ];
                    }
                    if (!empty($log['rawCheckOut'])) {
                        $timeOut = date('H:i:s', strtotime($log['rawCheckOut']));
                        $insertLogs[] = [
                            'machine_id' => $machineId, // <--- SỬ DỤNG MACHINE CHUẨN XÁC
                            'fingerprint_id' => $fingerprintId,
                            'timestamp' => $date . ' ' . $timeOut,
                            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
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