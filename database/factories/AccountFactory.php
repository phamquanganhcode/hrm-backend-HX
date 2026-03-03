<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AccountFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            // employee_id và role sẽ được gán lúc chạy Seeder để đồng bộ với Employee
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('123'), // Đặt pass mặc định là 123 cho dễ test
        ];
    }
}