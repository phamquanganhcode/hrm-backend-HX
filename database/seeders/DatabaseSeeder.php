<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Employee;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo 3 nhân viên mẫu
        $adminEmp = Employee::create([
            'employee_code' => 'NV_ADMIN',
            'full_name' => 'Nguyễn Chủ Tịch',
        ]);

        $managerEmp = Employee::create([
            'employee_code' => 'NV_MANAGER',
            'full_name' => 'Trần Quản Lý',
        ]);

        $staffEmp = Employee::create([
            'employee_code' => 'NV_STAFF',
            'full_name' => 'Lê Nhân Viên',
        ]);

        // 2. Tạo 3 tài khoản đăng nhập (Mật khẩu: 123 theo đúng UI Frontend)
        Account::create([
            'employee_id' => $adminEmp->id,
            'username' => 'admin',
            'password' => Hash::make('123'),
            'role' => 'admin', // Quyền Chủ DN
            'is_active' => true,
        ]);

        Account::create([
            'employee_id' => $managerEmp->id,
            'username' => 'manager',
            'password' => Hash::make('123'),
            'role' => 'manager', // Quyền Trưởng chi nhánh
            'is_active' => true,
        ]);

        Account::create([
            'employee_id' => $staffEmp->id,
            'username' => 'nhanvien',
            'password' => Hash::make('123'),
            'role' => 'employee', // Quyền Nhân viên thường
            'is_active' => true,
        ]);
    }
}