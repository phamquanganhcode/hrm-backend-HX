<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Chi nhánh
        $branches = [
            ['name' => 'Chi nhánh Thủy Lợi', 'address' => '175 Tây Sơn, Đống Đa', 'created_at' => now()],
            ['name' => 'Chi nhánh Nguyễn Tuân', 'address' => '99 Nguyễn Tuân, Thanh Xuân', 'created_at' => now()],
        ];
        DB::table('branches')->insert($branches);

        // 2. Chức vụ (C0, C1, C2, C3)
        $positions = [
            ['code' => 'C3', 'name' => 'Giám đốc / Chuyên gia', 'base_salary_default' => 15000000, 'allowed_leave_days' => 4, 'is_manager' => true, 'created_at' => now()],
            ['code' => 'C2', 'name' => 'Quản lý cơ sở', 'base_salary_default' => 10000000, 'allowed_leave_days' => 3, 'is_manager' => true, 'created_at' => now()],
            ['code' => 'C1', 'name' => 'Thu ngân / Kế toán', 'base_salary_default' => 7000000, 'allowed_leave_days' => 2, 'is_manager' => false, 'created_at' => now()],
            ['code' => 'C0', 'name' => 'Nhân viên Bàn/Bếp', 'base_salary_default' => 5500000, 'allowed_leave_days' => 2, 'is_manager' => false, 'created_at' => now()],
        ];
        DB::table('positions')->insert($positions);

        // 3. Bậc lương (Demo 3 bậc)
        // 3. Bậc lương (Gắn với các position_id tương ứng)
        $payGrades = [
            // Các bậc lương cho C0 (Nhân viên Bàn/Bếp - position_id = 4)
            ['position_id' => 4, 'level' => 1, 'base_salary' => 5500000, 'effective_date' => '2024-01-01', 'created_at' => now()],
            ['position_id' => 4, 'level' => 2, 'base_salary' => 6000000, 'effective_date' => '2024-01-01', 'created_at' => now()],
            ['position_id' => 4, 'level' => 3, 'base_salary' => 7000000, 'effective_date' => '2024-01-01', 'created_at' => now()],
            
            // Bậc lương cho C1 (Thu ngân / Kế toán - position_id = 3)
            ['position_id' => 3, 'level' => 1, 'base_salary' => 7000000, 'effective_date' => '2024-01-01', 'created_at' => now()],
            
            // Bậc lương cho C2 (Quản lý cơ sở - position_id = 2)
            ['position_id' => 2, 'level' => 1, 'base_salary' => 10000000, 'effective_date' => '2024-01-01', 'created_at' => now()],
            
            // Bậc lương cho C3 (Giám đốc / Chuyên gia - position_id = 1)
            ['position_id' => 1, 'level' => 1, 'base_salary' => 15000000, 'effective_date' => '2024-01-01', 'created_at' => now()],
        ];
        DB::table('pay_grades')->insert($payGrades);
        // 4. Danh mục Thành phần lương
        $payrollItems = [
            ['code' => 'BASE_SALARY', 'name' => 'Lương cơ bản', 'sign' => 1, 'calc_method' => 'Prorated_by_day', 'is_system' => true, 'created_at' => now()],
            ['code' => 'RESP_ALLOWANCE', 'name' => 'Phụ cấp trách nhiệm', 'sign' => 1, 'calc_method' => 'Fixed', 'is_system' => false, 'created_at' => now()],
            ['code' => 'LATE_PENALTY', 'name' => 'Phạt đi muộn', 'sign' => -1, 'calc_method' => 'Fixed', 'is_system' => true, 'created_at' => now()],
            ['code' => 'ORDER_BONUS', 'name' => 'Thưởng doanh thu', 'sign' => 1, 'calc_method' => 'Formula', 'is_system' => false, 'created_at' => now()],
        ];
        DB::table('payroll_item_types')->insert($payrollItems);

        // 5. Ca làm việc
        $shifts = [
            ['name' => 'Ca Sáng', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'coefficient' => 1, 'color' => '#FFD700', 'created_at' => now()],
            ['name' => 'Ca Tối', 'start_time' => '16:00:00', 'end_time' => '23:30:00', 'coefficient' => 1, 'color' => '#1E90FF', 'created_at' => now()],
        ];
        DB::table('shift_definitions')->insert($shifts);
    }
}