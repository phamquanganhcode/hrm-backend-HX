<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Chi nhánh ' . $this->faker->unique()->city(),
            'address' => $this->faker->streetAddress(),
        ];
    }
}