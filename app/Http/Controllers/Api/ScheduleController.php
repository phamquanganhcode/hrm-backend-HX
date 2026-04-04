<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Lấy các ngày đã có lịch làm việc
     * Tương đương: @app.get("/api/schedules/scheduled-dates")
     */
    public function getScheduledDates()
    {
        $user = auth()->user();
        $query = DB::table('work_schedules')->whereNull('deleted_at');

        // BỘ LỌC CƠ SỞ
        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $branchId = $manager->branch_id;
                $query->whereExists(function ($q) use ($branchId) {
                    $q->select(DB::raw(1))->from('employees')
                      ->whereColumn('employees.id', 'work_schedules.employee_id')
                      ->where('employees.branch_id', $branchId);
                });
            }
        }
        
        return response()->json(['scheduledDates' => $query->distinct()->pluck('date')], 200);
    }

    public function getByDate($date)
    {
        $user = auth()->user();
        $query = DB::table('work_schedules')->where('date', $date)->whereNull('deleted_at');

        // BỘ LỌC CƠ SỞ
        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $branchId = $manager->branch_id;
                $query->whereExists(function ($q) use ($branchId) {
                    $q->select(DB::raw(1))->from('employees')
                      ->whereColumn('employees.id', 'work_schedules.employee_id')
                      ->where('employees.branch_id', $branchId);
                });
            }
        }

        $assignments = $query->get()->map(function($item) {
            return [ 'employeeId' => $item->employee_id, 'shiftId' => $item->shift_id ];
        });

        return response()->json(['date' => $date, 'assignments' => $assignments]);
    }

    public function updateByDate(Request $request, $date)
    {
        $user = auth()->user();
        $assignments = $request->input('assignments', []);

        if (!$user) {
            return response()->json(['message' => 'Chưa đăng nhập'], 401);
        }

        $role = strtoupper($user->role);

        // 1. DÀNH CHO QUẢN LÝ CƠ SỞ (C1, C2)
        if (in_array($role, ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            
            // Chống lỗi nếu tài khoản Quản lý chưa được map với nhân sự
            if (!$manager) {
                return response()->json(['message' => 'Tài khoản chưa được liên kết với nhân sự nào'], 400);
            }

            $branchId = $manager->branch_id;

            DB::transaction(function() use ($date, $assignments, $branchId) {
                // CHỈ XÓA lịch của nhân viên thuộc chi nhánh này
                DB::table('work_schedules')
                    ->where('date', $date)
                    ->whereExists(function ($q) use ($branchId) {
                        $q->select(DB::raw(1))->from('employees')
                          ->whereColumn('employees.id', 'work_schedules.employee_id')
                          ->where('employees.branch_id', $branchId);
                    })
                    ->delete();

                // Lọc kỹ: Chỉ lưu lịch nếu nhân viên đó đúng là của chi nhánh này
                $validEmpIds = DB::table('employees')->where('branch_id', $branchId)->pluck('id')->toArray();

                foreach ($assignments as $item) {
                    if (in_array($item['employeeId'], $validEmpIds)) {
                        DB::table('work_schedules')->insert([
                            'date' => $date,
                            'employee_id' => $item['employeeId'],
                            'shift_id' => $item['shiftId'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            });
            return response()->json(['message' => 'Cập nhật lịch cho cơ sở thành công']);
        }

        // 2. DÀNH CHO GIÁM ĐỐC / ADMIN (C3) - Có quyền ghi đè toàn bộ lịch công ty
        if (in_array($role, ['C3', '3'])) {
            DB::transaction(function() use ($date, $assignments) {
                DB::table('work_schedules')->where('date', $date)->delete();
                foreach ($assignments as $item) {
                    DB::table('work_schedules')->insert([
                        'date' => $date,
                        'employee_id' => $item['employeeId'],
                        'shift_id' => $item['shiftId'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            });
            return response()->json(['message' => 'Giám đốc cập nhật lịch thành công']);
        }
        
        return response()->json(['message' => 'Không có quyền thực hiện'], 403);
    }
}