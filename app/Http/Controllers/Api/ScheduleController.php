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
        // Truy vấn bảng thật, bỏ qua các bản ghi bị xóa mềm
        $dates = DB::table('work_schedules')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('date');
        
        return response()->json(['scheduledDates' => $dates], 200);
    }

    /**
     * Lấy lịch làm việc của 1 ngày cụ thể
     * Tương đương: @app.get("/api/schedule/{date}")
     */
    // app/Http/Controllers/Api/ScheduleController.php

    public function getByDate($date)
    {
        $user = auth()->user();
        $query = DB::table('work_schedules')->where('date', $date)->whereNull('deleted_at');

        if ($user && $user->role === 'C2') {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $branchId = $manager->branch_id;
                // Chỉ lấy lịch của nhân viên thuộc chi nhánh này
                $query->whereExists(function ($q) use ($branchId) {
                    $q->select(DB::raw(1))
                    ->from('employees')
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

        if ($user && $user->role === 'C2') {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            $branchId = $manager->branch_id;

            DB::transaction(function() use ($date, $assignments, $branchId) {
                // CHỈ XÓA lịch của những nhân viên thuộc chi nhánh này
                DB::table('work_schedules')
                    ->where('date', $date)
                    ->whereExists(function ($q) use ($branchId) {
                        $q->select(DB::raw(1))
                        ->from('employees')
                        ->whereColumn('employees.id', 'work_schedules.employee_id')
                        ->where('employees.branch_id', $branchId);
                    })
                    ->delete();

                // Chỉ chèn lịch mới (Nên có thêm bước kiểm tra ID nhân viên gửi lên có thuộc branchId không)
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
            return response()->json(['message' => 'Cập nhật lịch thành công']);
        }
        
        return response()->json(['message' => 'Không có quyền thực hiện'], 403);
    }
}