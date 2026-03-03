<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\LaborContract;
use App\Models\JobHistory;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $branchTL = Branch::where('name', 'Chi nhánh Thủy Lợi')->first();
        $branchHM = Branch::where('name', 'Chi nhánh Hoàng Mai')->first();

        // 1. Tạo 4 nhân viên CỐ ĐỊNH để cấp tài khoản
        $admin = Employee::create([
            'employee_code' => 'ADMIN01', 'full_name' => 'Nguyễn Quản Trị',
            'email' => 'admin@haixom.com', 'phonenumber' => '0900000000',
            'role' => 'admin', 'branch_id' => $branchTL->id,
            'type' => 'full', 'base_salary' => 20000000, 'status' => 'active',
        ]);

        $manager = Employee::create([
            'employee_code' => 'NV001', 'full_name' => 'Trần Quản Lý',
            'email' => 'quanly@haixom.com', 'phonenumber' => '0911111111',
            'role' => 'manager', 'branch_id' => $branchTL->id,
            'type' => 'full', 'base_salary' => 15000000, 'status' => 'active',
        ]);

        $chef = Employee::create([
            'employee_code' => 'NV002', 'full_name' => 'Nguyễn Đầu Bếp',
            'email' => 'bep@haixom.com', 'phonenumber' => '0922222222',
            'role' => 'employee_chef', 'branch_id' => $branchHM->id,
            'type' => 'full', 'base_salary' => 12000000, 'status' => 'active',
        ]);

        $staff = Employee::create([
            'employee_code' => 'NV003', 'full_name' => 'Nguyễn Văn Bàn',
            'email' => 'ban@haixom.com', 'phonenumber' => '0987654321',
            'role' => 'employee_staff', 'branch_id' => $branchTL->id,
            'type' => 'part', 'base_salary' => 35000, 'status' => 'active',
        ]);

        // 2. Tạo 50 nhân viên ảo (Bỏ phần tạo Account ở đây)
        Employee::factory(50)->create()->each(function ($employee) {
            LaborContract::factory()->create(['employee_id' => $employee->id]);
            JobHistory::factory(rand(1, 2))->create(['employee_id' => $employee->id]);
        });
    }
}