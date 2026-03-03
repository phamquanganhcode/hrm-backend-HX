<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        Position::create(['name' => 'Quản lý cơ sở', 'code' => 'MNG', 'base_salary_default' => 15000000, 'is_manager' => true]);
        Position::create(['name' => 'Bếp trưởng', 'code' => 'CHEF', 'base_salary_default' => 12000000, 'is_manager' => false]);
        Position::create(['name' => 'Nhân viên phục vụ', 'code' => 'STAFF', 'hourly_rate' => 25000, 'is_manager' => false]);
        // Tạo thêm 5 chức vụ ảo
        // \App\Models\Position::factory(5)->create();
    }
}