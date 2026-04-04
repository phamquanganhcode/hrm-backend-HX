<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    // 1. Lấy lịch làm việc theo ngày (GET /api/v1/schedules?date=YYYY-MM-DD)
    public function getByDate(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $user = auth()->user();
        
        $query = DB::table('work_schedules')
            ->where('date', $date)
            ->whereNull('deleted_at');

        // BẢO MẬT: Lọc lịch làm việc theo chi nhánh của Quản lý đang đăng nhập
        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $branchId = $manager->branch_id;
                $query->whereExists(function ($q) use ($branchId) {
                    $q->select(DB::raw(1))
                      ->from('employees')
                      ->whereColumn('employees.id', 'work_schedules.employee_id')
                      ->where('employees.branch_id', $branchId);
                });
            }
        }

        $assignments = $query->select('employee_id', 'shift_id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments
        ], 200);
    }

    // 2. Cập nhật/Lưu lịch làm việc (POST /api/v1/schedules)
    public function updateByDate(Request $request)
    {
        $date = $request->input('date');
        $assignments = $request->input('assignments', []);

        if (!$date) {
            return response()->json(['success' => false, 'message' => 'Thiếu tham số ngày (date).'], 400);
        }

        $user = auth()->user();
        $branchId = null;

        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            $branchId = $manager ? $manager->branch_id : null;
        }

        DB::beginTransaction();
        try {
            // Tìm các ID nhân viên thuộc chi nhánh của quản lý này
            $empQuery = DB::table('employees');
            if ($branchId) {
                $empQuery->where('branch_id', $branchId);
            }
            $validEmpIds = $empQuery->pluck('id')->toArray();

            // XÓA sạch lịch cũ của những nhân viên này trong ngày
            DB::table('work_schedules')
                ->where('date', $date)
                ->whereIn('employee_id', $validEmpIds)
                ->delete();

            // TẠO mảng lịch mới
            $insertData = [];
            foreach ($assignments as $assign) {
                // Tương thích cả 2 chuẩn key (employee_id và employeeId)
                $empId = $assign['employeeId'] ?? $assign['employee_id'] ?? null;
                $shiftId = $assign['shiftId'] ?? $assign['shift_id'] ?? null;

                // Chỉ xếp lịch khi nhân viên hợp lệ và có ca khác 'OFF'
                if ($empId && in_array($empId, $validEmpIds) && $shiftId && $shiftId !== 'OFF') {
                    $insertData[] = [
                        'date' => $date,
                        'employee_id' => $empId,
                        'shift_id' => $shiftId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // Lưu lịch mới vào DB
            if (!empty($insertData)) {
                DB::table('work_schedules')->insert($insertData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật lịch thành công'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    // 3. Lấy danh sách các ngày đã xếp lịch (GET /api/v1/schedules/dates)
    public function getScheduledDates()
    {
        $user = auth()->user();
        $query = DB::table('work_schedules')->whereNull('deleted_at');

        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $branchId = $manager->branch_id;
                $query->whereExists(function ($q) use ($branchId) {
                    $q->select(DB::raw(1))
                      ->from('employees')
                      ->whereColumn('employees.id', 'work_schedules.employee_id')
                      ->where('employees.branch_id', $branchId);
                });
            }
        }

        // Lấy danh sách các ngày duy nhất (Distinct) có lịch
        $dates = $query->select('date')->distinct()->pluck('date')->toArray();

        return response()->json([
            'status' => 'success',
            'data' => $dates
        ], 200);
    }
}