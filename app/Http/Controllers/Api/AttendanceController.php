<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Lấy dữ liệu chấm công của 1 ngày
     * Tương đương: @app.get("/api/attendance/{date}")
     */
    public function getByDate($date)
    {
        // 1. Lấy tất cả nhân viên ĐƯỢC XẾP LỊCH ngày hôm đó
        $schedules = DB::table('work_schedules')->where('date', $date)->whereNull('deleted_at')->get();
        
        // 2. Lấy dữ liệu CHẤM CÔNG THỰC TẾ đã xử lý
        $attendances = DB::table('daily_attendances')->where('date', $date)->whereNull('deleted_at')->get();
        $attDict = $attendances->keyBy('employee_id');

        // Gộp danh sách những người có lịch HOẶC có đi làm (phòng trường hợp đi làm không xếp lịch)
        $empIds = collect($schedules->pluck('employee_id'))->merge($attendances->pluck('employee_id'))->unique();

        // Lấy thông tin chi tiết của các nhân viên này
        $employees = DB::table('employees')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->whereIn('employees.id', $empIds)
            ->select('employees.*', 'branches.name as branch_name')
            ->get()
            ->keyBy('id');

        // 3. Lấy LOG QUẸT THẺ (Từ máy chấm công)
        // Vì hệ thống lưu log thô, ta sẽ tìm giờ quét sớm nhất (CheckIn) và muộn nhất (CheckOut)
        $fingerprintIds = $employees->pluck('fingerprint_id')->filter()->toArray();
        $timeLogsRaw = DB::table('time_logs')
            ->whereDate('timestamp', $date)
            ->whereIn('fingerprint_id', $fingerprintIds)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('fingerprint_id');

        $records = [];
        $expectedHeadcount = count($schedules);
        $actualHeadcount = 0;
        $overrideCount = 0;

        foreach ($empIds as $empId) {
            $emp = $employees->get($empId);
            if (!$emp) continue;

            $att = $attDict->get($empId);
            $sched = $schedules->firstWhere('employee_id', $empId);

            // --- XỬ LÝ LOG QUẸT THẺ ---
            $logs = [];
            $rawCheckIn = null;
            $rawCheckOut = null;
            if ($emp->fingerprint_id && $timeLogsRaw->has($emp->fingerprint_id)) {
                $empLogs = $timeLogsRaw->get($emp->fingerprint_id);
                // Giờ quẹt đầu tiên và cuối cùng trong ngày
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
            if ($status !== 'absent' && $status !== 'Chờ duyệt') {
                $actualHeadcount++;
            }
            $isOverridden = $att ? (bool) $att->is_manually_adjusted : false;
            if ($isOverridden) {
                $overrideCount++;
            }

            $records[] = [
                'id' => $emp->employee_code, // ID dành cho Frontend
                'employeeId' => $emp->employee_code,
                'shiftId' => (string) ($sched ? $sched->shift_id : 'Không rõ'),
                'status' => $status,
                'isOverridden' => $isOverridden,
                'overrideDetails' => $isOverridden ? [
                    'reason' => $att->note ?? 'Đã chỉnh sửa thủ công',
                    'overriddenBy' => 'Quản lý' // Có thể mở rộng lấy user đăng nhập sau
                ] : null,
                'timeLogs' => empty($logs) ? [['rawCheckIn' => null, 'rawCheckOut' => null, 'isLateIn' => false, 'isEarlyOut' => false]] : $logs,
                'personalInfo' => [
                    'fullName' => $emp->full_name,
                    'avatarUrl' => $emp->avatar_url ?? 'bg-blue-500'
                ],
                'employment' => [
                    'department' => $emp->branch_name ?? 'Chưa phân chi nhánh',
                    'role' => $emp->role ?? ''
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

        $emp = DB::table('employees')->where('employee_code', $empCode)->first();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên'], 404);
        }

        $schedule = DB::table('work_schedules')->where('date', $date)->where('employee_id', $emp->id)->first();

        DB::beginTransaction();
        try {
            // 1. Cập nhật bảng Daily Attendances (Đã chốt)
            $att = DB::table('daily_attendances')->where('date', $date)->where('employee_id', $emp->id)->first();

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
                DB::table('daily_attendances')->where('id', $att->id)->update($attData);
            } else {
                $attData['created_at'] = now();
                DB::table('daily_attendances')->insert($attData);
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