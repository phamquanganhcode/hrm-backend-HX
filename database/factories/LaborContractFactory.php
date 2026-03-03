<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaborContractFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        return [
            'start_date' => $startDate->format('Y-m-d'),
            // 50% có ngày kết thúc (khoảng 1 năm sau ngày bắt đầu), 50% là hợp đồng vô thời hạn (null)
            'end_date' => $this->faker->optional(0.5)->dateTimeBetween($startDate, '+2 years')?->format('Y-m-d'),
        ];
    }
}