<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;
use App\Models\Employee;
use App\Models\ShiftDefinition;
use App\Models\Branch;
use App\Models\Position;
use Carbon\Carbon;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa dữ liệu cũ của lịch làm việc để tránh rác khi chạy lại nhiều lần
        WorkSchedule::truncate();

        // Lấy tất cả nhân viên và ca làm việc hiện có
        $employees = Employee::all();
        $shifts = ShiftDefinition::all(); // Đảm bảo bảng này đã có dữ liệu nhé
        $branch = Branch::first();
        $position = Position::first();

        // Đảm bảo phải có nhân viên và ca làm việc thì mới xếp lịch được
        if ($employees->isEmpty() || $shifts->isEmpty()) {
            $this->command->info('Vui lòng tạo Employee và ShiftDefinition trước!');
            return;
        }

        $startOfWeek = Carbon::now()->startOfWeek(); // Thứ 2 của tuần này

        foreach ($employees as $employee) {
            // Chọn ngẫu nhiên 3 ngày trong tuần (từ 0 đến 6) cho nhân viên này
            $randomDays = collect([0, 1, 2, 3, 4, 5, 6])->random(3);

            foreach ($randomDays as $dayOffset) {
                WorkSchedule::create([
                    'employee_id' => $employee->id,
                    'date' => $startOfWeek->copy()->addDays($dayOffset)->toDateString(),
                    'shift_id' => $shifts->random()->id, // Chọn ngẫu nhiên 1 ca làm
                    'position_id' => $position ? $position->id : null,
                    'work_branch_id' => $branch ? $branch->id : null,
                    'is_published' => 'Published',
                    'status' => 'Scheduled',
                ]);
            }
        }
        
        $this->command->info('Đã tạo lịch làm việc ngẫu nhiên cho tuần này thành công!');
    }
}