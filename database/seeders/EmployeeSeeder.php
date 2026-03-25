<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Account;
use App\Models\JobHistory;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'code' => 'HX_ADMIN', 'name' => 'Lê Tổng Quản', 'role' => 'C3', 
                'branch_id' => 1, 'pos_id' => 1, 'username' => 'admin'
            ],
            [
                'code' => 'HX_MANAGER', 'name' => 'Nguyễn Quản Lý', 'role' => 'C2', 
                'branch_id' => 1, 'pos_id' => 2, 'username' => 'manager'
            ],
            [
                'code' => 'HX_STAFF', 'name' => 'Trần Chạy Bàn', 'role' => 'C0', 
                'branch_id' => 1, 'pos_id' => 4, 'username' => 'staff'
            ],
        ];

        foreach ($users as $u) {
            // 1. Tạo Nhân viên
            $emp = Employee::create([
                'employee_code' => $u['code'],
                'full_name' => $u['name'],
                'role' => $u['role'],
                'branch_id' => $u['branch_id'],
                'type' => 'full-time',
                'pay_grade_id' => 1,
                'status' => 'active',
                'phonenumber' => '09'.rand(10000000, 99999999)
            ]);

            // 2. Tạo Tài khoản đăng nhập (Mật khẩu chung: 123)
            Account::create([
                'employee_id' => $emp->id,
                'username' => $u['username'],
                'password' => Hash::make('123'),
                'role' => $u['role'],
                'is_active' => true
            ]);

            // 3. Tạo Lịch sử công tác ban đầu
            JobHistory::create([
                'employee_id' => $emp->id,
                'branch_id' => $u['branch_id'],
                'position_id' => $u['pos_id'],
                'pay_grade_id' => 1,
                'start_date' => now()->subMonths(6)->toDateString(),
                'base_salary_amount' => 5500000
            ]);
        }
    }
}