<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WorkSchedule;
use App\Models\Employee;
use App\Models\ShiftDefinition; // Đổi lại đúng tên Model Ca làm của bạn
use App\Models\Branch;
use App\Models\Position;
use Carbon\Carbon;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        // Random 1 ngày bất kỳ trong tuần này để hiển thị lên UI luôn
        $randomDayInThisWeek = Carbon::now()->startOfWeek()->addDays(rand(0, 6))->toDateString();

        return [
            // Lấy ngẫu nhiên 1 ID có sẵn trong các bảng (hoặc để null nếu chưa có)
            'employee_id' => Employee::inRandomOrder()->first()->id ?? 1,
            'date' => $randomDayInThisWeek,
            'shift_id' => ShiftDefinition::inRandomOrder()->first()->id ?? 1,
            'position_id' => Position::inRandomOrder()->first()->id ?? null,
            'work_branch_id' => Branch::inRandomOrder()->first()->id ?? null,
            'is_published' => 'Published', // Cố định Published để FE nhìn thấy
            'status' => 'Scheduled',
        ];
    }
}