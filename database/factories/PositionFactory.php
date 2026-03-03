<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'base_salary_default' => $this->faker->numberBetween(5, 20) * 1000000, // Lương từ 5tr - 20tr
            'hourly_rate' => $this->faker->numberBetween(20, 50) * 1000,
            'is_manager' => $this->faker->boolean(20), // 20% xác suất là quản lý
        ];
    }
}