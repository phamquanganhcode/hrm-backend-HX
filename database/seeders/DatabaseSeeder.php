<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Account;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Chi nhánh mẫu
        $branch = Branch::create([
            'name' => 'Chi nhánh Hải Xồm - Thủy Lợi',
            'address' => '175 Tây Sơn, Đống Đa, Hà Nội',
        ]);

        // 2. Tạo dữ liệu Nhân viên (Employee)
        // Nhân viên Bếp
        $chef = Employee::create([
            'employee_code' => 'NV001',
            'full_name'     => 'Nguyễn Đầu Bếp',
            'email'         => 'bep@haixom.com',
            'phonenumber'   => '0912345678',
            'avatar_url'    => 'https://i.pravatar.cc/150?u=chef',
            'role'          => 'employee_chef',
            'branch_id'     => $branch->id,
            'type'          => 'full', // Toàn thời gian
            'base_salary'   => 12000000,
            'status'        => 'active',
        ]);

        // Nhân viên Chạy bàn (Đúng format bạn yêu cầu)
        $staff = Employee::create([
            'employee_code' => '0001',
            'full_name'     => 'Nguyễn Văn Bàn',
            'email'         => 'bannv82@haixom.com',
            'phonenumber'   => '0987654321',
            'avatar_url'    => 'https://i.pravatar.cc/150?u=truong',
            'role'          => 'employee_staff',
            'branch_id'     => $branch->id,
            'type'          => 'part', // Bán thời gian
            'base_salary'   => 35000,
            'status'        => 'active',
        ]);

        // 3. Tạo tài khoản đăng nhập (Account)
        // Tài khoản cho ông Bếp
        Account::create([
            'employee_id' => $chef->id,
            'username'    => 'nhanvienbep',
            'password'    => Hash::make('123'),
            'role'        => 'employee_chef',
            'is_active'   => true,
        ]);

        // Tài khoản cho ông Trường (Chạy bàn)
        Account::create([
            'employee_id' => $staff->id,
            'username'    => 'nhanvienban',
            'password'    => Hash::make('123'), // Khớp với pass giả lập FE
            'role'        => 'employee_staff',
            'is_active'   => true,
        ]);

        // Tài khoản Admin để quản lý
        $admin = Employee::create([
            'employee_code' => 'ADMIN01',
            'full_name'     => 'Quản Trị Viên',
            'role'          => 'admin',
            'branch_id'     => $branch->id,
        ]);

        Account::create([
            'employee_id' => $admin->id,
            'username'    => 'admin',
            'password'    => Hash::make('123'),
            'role'        => 'admin',
            'is_active'   => true,
        ]);
    }
}