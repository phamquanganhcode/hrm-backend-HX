<?php

namespace Database\Factories;

use App\Models\JobHistory;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobHistoryFactory extends Factory
{
    protected $model = JobHistory::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        return [
            // Lấy ngẫu nhiên một nhân viên, chi nhánh và chức vụ đã có trong DB
            'employee_id' => Employee::inRandomOrder()->value('id') ?? Employee::factory(),
            'branch_id' => Branch::inRandomOrder()->value('id') ?? Branch::factory(),
            'position_id' => Position::inRandomOrder()->value('id') ?? Position::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $this->faker->optional(0.5)->dateTimeBetween($startDate, 'now')?->format('Y-m-d'),
        ];
    }
}