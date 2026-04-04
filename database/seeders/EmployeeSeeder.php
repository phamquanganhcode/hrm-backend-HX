<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Employee;
use App\Models\Account;
use App\Models\JobHistory;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(public_path('db_employees.json'));
        $employees = json_decode($json, true);

        foreach ($employees as $index => $empData) {
            $empId = $empData['id'];
            
            // 1. Phân bổ chi nhánh đúng yêu cầu
            if ($empId === 'EMP_QL02') {
                $branchId = 1; // Thủy Lợi
            } elseif ($empId === 'EMP_QL03') {
                $branchId = 2; // Nguyễn Tuân
            } else {
                $branchId = ($index % 2) + 1; 
            }

            $dept = $empData['employment']['department'];
            $roleStr = $empData['employment']['role'];

            // 2. Logic phân loại vai trò (Lưu dạng chuỗi C0, C1, C2, C3)
            $cLevel = 'C0';
            $posId = 4; 
            $baseSalary = 5500000;

            if (str_contains($roleStr, 'Quản lý chung')) {
                $cLevel = 'C3'; 
                $posId = 1;
                $baseSalary = 15000000;
                $branchId = 1; 
            } elseif (str_contains($roleStr, 'Kế toán trưởng') || str_contains($roleStr, 'Giám sát')) {
                $cLevel = 'C2'; 
                $posId = 2;
                $baseSalary = 10000000;
            } elseif ($dept === 'Quản lý') {
                $cLevel = 'C1'; 
                $posId = 3;
                $baseSalary = 7000000;
            }

            // 3. Tạo nhân viên
            $emp = Employee::create([
                'employee_code' => $empId,
                'full_name'     => $empData['personalInfo']['fullName'],
                'phonenumber'   => $empData['personalInfo']['phone'],
                'role'          => $cLevel, // Lưu chuỗi 'C2'
                'branch_id'     => $branchId,
                'department'    => $dept, // <--- THÊM DÒNG NÀY ĐỂ LƯU TỔ
                'type'          => 'full',
                'pay_grade_id'  => 1,
                'status'        => 'Active',
            ]);

            // 4. Tạo tài khoản (Username là ID viết thường)
            Account::create([
                'employee_id' => $emp->id,
                'username'    => strtolower($empId), // ví dụ: emp_ql02
                'password'    => Hash::make('123'),
                'role'        => $cLevel, // Lưu chuỗi 'C2'
                'is_active'   => true
            ]);

            JobHistory::create([
                'employee_id'        => $emp->id,
                'branch_id'          => $branchId,
                'position_id'        => $posId,
                'pay_grade_id'       => 1,
                'start_date'         => $empData['employment']['joinDate'],
                'base_salary_amount' => $baseSalary
            ]);

            if ($cLevel === 'C2') {
                DB::table('branches')->where('id', $branchId)->update(['manager_id' => $emp->id]);
            }
        }
    }
}