<?php
namespace Database\Factories;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $roles = ['manager', 'employee_chef', 'employee_staff'];
        return [
            'employee_code' => 'NV' . $this->faker->unique()->numberBetween(1000, 9999),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phonenumber' => $this->faker->numerify('09########'),
            'role' => $this->faker->randomElement($roles),
            'branch_id' => Branch::inRandomOrder()->value('id') ?? Branch::factory(),
            'avatar_url' => 'https://i.pravatar.cc/150?u=' . $this->faker->uuid(),
            'type' => $this->faker->randomElement(['full', 'part']),
            'base_salary' => $this->faker->numberBetween(5, 20) * 1000000,
            'status' => $this->faker->randomElement(['active', 'active', 'inactive']), // Tỉ lệ active cao hơn
        ];
    }
}