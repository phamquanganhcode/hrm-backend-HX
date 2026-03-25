<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Bia Hải Xồm - Cơ sở ' . $this->faker->unique()->city(),
            'address' => $this->faker->address(),
            // manager_id sẽ được gán sau khi tạo nhân viên
        ];
    }
}