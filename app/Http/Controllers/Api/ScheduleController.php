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
    public function getByDate($date)
    {
        // Join với bảng employees để lấy được mã employee_code trả về cho React
        $schedules = DB::table('work_schedules')
            ->join('employees', 'work_schedules.employee_id', '=', 'employees.id')
            ->where('work_schedules.date', $date)
            ->whereNull('work_schedules.deleted_at')
            ->select('work_schedules.shift_id', 'employees.employee_code')
            ->get();

        $assignments = $schedules->map(function ($s) {
            return [
                'employeeId' => $s->employee_code, // Phiên dịch: ID số -> Mã chuỗi
                'shiftId' => (string) $s->shift_id
            ];
        });

        return response()->json([
            'date' => $date,
            'assignments' => $assignments
        ], 200);
    }

    /**
     * Cập nhật/Lưu lịch làm việc
     * Tương đương: @app.post("/api/schedule/{date}")
     */
    public function updateByDate(Request $request, $date)
    {
        $assignments = $request->input('assignments', []);

        // 1. Chuẩn bị dữ liệu Mapping (Từ Mã Nhân viên chuỗi -> ID số thực tế)
        $empCodes = collect($assignments)->pluck('employeeId')->unique()->toArray();
        $employees = DB::table('employees')->whereIn('employee_code', $empCodes)->get()->keyBy('employee_code');

        // 2. Lấy hoặc Tự động tạo Kế hoạch tuần (weekly_plan) để không vi phạm khóa ngoại
        $weeklyPlan = DB::table('weekly_plans')->first();
        if ($weeklyPlan) {
            $weeklyPlanId = $weeklyPlan->id;
        } else {
            // Tự động sinh ra 1 Kế hoạch tuần mặc định nếu trong DB chưa có
            $weeklyPlanId = DB::table('weekly_plans')->insertGetId([
                'name' => 'Kế hoạch tự động ' . Carbon::parse($date)->weekOfYear,
                'start_date' => Carbon::parse($date)->startOfWeek(),
                'end_date' => Carbon::parse($date)->endOfWeek(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Lấy Chi nhánh mặc định (đề phòng nhân viên chưa được phân chi nhánh)
        $defaultBranch = DB::table('branches')->first();
        $defaultBranchId = $defaultBranch ? $defaultBranch->id : 1;

        DB::beginTransaction();
        try {
            // Xóa mềm toàn bộ lịch cũ của ngày này để xếp lại
            DB::table('work_schedules')
                ->where('date', $date)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);

            // Chuẩn bị mảng Insert mới
            $insertData = [];
            foreach ($assignments as $a) {
                $emp = $employees->get($a['employeeId']);
                if (!$emp) continue; // Bỏ qua nếu mã nhân viên bị lỗi

                $insertData[] = [
                    'weekly_plan_id' => $weeklyPlanId, // Ràng buộc khóa ngoại Kế hoạch
                    'employee_id' => $emp->id,         // Ràng buộc khóa ngoại Nhân viên
                    'shift_id' => $a['shiftId'],       // Ràng buộc khóa ngoại Ca làm
                    'work_branch_id' => $emp->branch_id ?? $defaultBranchId, // Nhánh làm việc
                    'date' => $date,
                    'is_published' => 'Published',     // Đánh dấu là đã xuất bản thay vì Draft
                    'status' => 'Scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                DB::table('work_schedules')->insert($insertData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lưu lịch thành công'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi lưu lịch: ' . $e->getMessage()], 500);
        }
    }
}