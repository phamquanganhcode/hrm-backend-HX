<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        // Các loại ca làm việc ví dụ
        $shifts = ['Ca Sáng', 'Ca Chiều', 'Ca Gãy', 'Ca Tối'];
        $shiftName = $this->faker->randomElement($shifts);

        // Khởi tạo thời gian mặc định
        $startTime1 = '08:00:00';
        $endTime1 = '12:00:00';
        $startTime2 = null;
        $endTime2 = null;

        // Thay đổi thời gian tùy theo loại ca
        if ($shiftName === 'Ca Chiều') {
            $startTime1 = '13:00:00';
            $endTime1 = '17:00:00';
        } elseif ($shiftName === 'Ca Tối') {
            $startTime1 = '18:00:00';
            $endTime1 = '22:00:00';
        } elseif ($shiftName === 'Ca Gãy') {
            $startTime1 = '08:00:00';
            $endTime1 = '12:00:00';
            $startTime2 = '13:30:00';
            $endTime2 = '17:30:00';
        }

        return [
            // Lấy ngẫu nhiên 1 employee_id có sẵn trong database
            'employee_id' => Employee::inRandomOrder()->value('id') ?? Employee::factory(),
            
            // Random ngày làm việc trong khoảng 1 tháng trước đến 1 tháng sau
            'date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            
            'shift_name' => $shiftName,
            
            'start_time_1' => $startTime1,
            'end_time_1' => $endTime1,
            'start_time_2' => $startTime2,
            'end_time_2' => $endTime2,
            
            // location và note có thể có hoặc null
            'location' => $this->faker->optional(0.7)->randomElement(['Khu vực A', 'Khu vực B', 'Văn phòng chính', 'Chi nhánh 1']), // 70% có data, 30% null
            'note' => $this->faker->optional(0.5)->sentence(),
        ];
    }
}