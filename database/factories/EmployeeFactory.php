<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_code' => 'HX' . $this->faker->unique()->numberBetween(1000, 9999),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phonenumber' => $this->faker->phoneNumber(),
            'hire_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'type' => 'full-time',
            'status' => 'Active',
        ];
    }
}