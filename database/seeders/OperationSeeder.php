<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeeklyPlan;
use App\Models\WorkSchedule;
use App\Models\Employee;
use App\Models\Branch;
use Carbon\Carbon;

class OperationSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();

        // Lấy tất cả chi nhánh để tạo dữ liệu mẫu cho cả 2 bên
        $branches = Branch::all();

        foreach ($branches as $branch) {
            // 1. Tạo Kế hoạch tuần cho từng chi nhánh
            $plan = WeeklyPlan::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'start_date' => $startOfWeek->toDateString(),
                    'end_date' => $endOfWeek->toDateString(),
                ],
                ['status' => 'Published']
            );

            // 2. Lấy danh sách nhân viên thuộc chi nhánh này (để đổ lịch động)
            // Việc này giúp tránh lỗi hardcode ID = 3 nếu ID đó không thuộc chi nhánh 1
            $employees = Employee::where('branch_id', $branch->id)->get();

            foreach ($employees as $index => $emp) {
                // Xếp lịch cho NGÀY HÔM NAY 
                // (Giúp Dashboard hiện danh sách ngay lập tức sau khi seed)
                WorkSchedule::create([
                    'weekly_plan_id' => $plan->id,
                    'date' => $today->toDateString(),
                    'employee_id' => $emp->id,
                    'shift_id' => ($index % 2 == 0) ? 1 : 2, // Phân bổ ca Sáng/Tối xen kẽ
                    'work_branch_id' => $branch->id,
                    'is_published' => true, // Để dạng true/false theo Migration
                    'status' => 'Scheduled'
                ]);

                // Xếp thêm lịch cho NGÀY MAI (Để test tính năng xem lịch tương lai)
                WorkSchedule::create([
                    'weekly_plan_id' => $plan->id,
                    'date' => $today->copy()->addDay()->toDateString(),
                    'employee_id' => $emp->id,
                    'shift_id' => 1,
                    'work_branch_id' => $branch->id,
                    'is_published' => true,
                    'status' => 'Scheduled'
                ]);
            }
        }
    }
}