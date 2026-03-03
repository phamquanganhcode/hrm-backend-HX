<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Ca ngẫu nhiên',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'coefficient' => 1.0,
            'is_active' => true,
            'is_overnight' => false,
            'color' => '#6366f1', // Màu chàm (Indigo)
        ];
    }
}