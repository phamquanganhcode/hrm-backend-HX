<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeeklyPlan;
use App\Models\WorkSchedule;

class OperationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Kế hoạch tuần cho Chi nhánh 1
        $plan = WeeklyPlan::create([
            'branch_id' => 1,
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'status' => 'Published'
        ]);

        // 2. Xếp Ca sáng (shift_id = 1) cho Nhân viên Staff (employee_id = 3) trong 3 ngày
        for ($i = 0; $i < 3; $i++) {
            WorkSchedule::create([
                'weekly_plan_id' => $plan->id,
                'date' => now()->startOfWeek()->addDays($i)->toDateString(),
                'employee_id' => 3, // ID của Trần Chạy Bàn
                'shift_id' => 1, // Ca Sáng
                'work_branch_id' => 1,
                'is_published' => 'Published',
                'status' => 'Scheduled'
            ]);
        }
    }
}