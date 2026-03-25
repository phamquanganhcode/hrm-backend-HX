<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->jobTitle(),
            'code' => 'C' . $this->faker->numberBetween(0, 3),
            'base_salary_default' => $this->faker->numberBetween(5000000, 20000000),
            'hourly_rate' => $this->faker->numberBetween(20000, 50000),
            'allowed_leave_days' => 12,
            'is_manager' => $this->faker->boolean(20),
        ];
    }
}