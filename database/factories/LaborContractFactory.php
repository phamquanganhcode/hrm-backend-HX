<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LaborContractFactory extends Factory
{
    public function definition(): array
    {
        // Random xem là thử việc hay chính thức
        $isProbation = $this->faker->boolean(30); // 30% xác suất là nhân viên đang thử việc
        
        return [
            'contract_type' => $isProbation ? 'thuviec' : 'chinhthuc',
            // Lương trong thời gian thử việc bằng 85% tổng mức lương thỏa thuận [cite: 14, 16]
            // Hết thử việc hưởng 100% [cite: 19]
            'salary_percentage' => $isProbation ? 85.00 : 100.00, 
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => $isProbation ? $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d') : null,
        ];
    }
}