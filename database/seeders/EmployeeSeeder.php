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
        // 1. Đọc dữ liệu từ file JSON trong thư mục public
        $json = File::get(public_path('db_employees.json'));
        $employees = json_decode($json, true);

        foreach ($employees as $index => $empData) {
            
            // Logic gán chi nhánh chính xác theo yêu cầu
            $empId = $empData['id'];
            if ($empId === 'EMP_QL02') {
                $branchId = 1; // Thủy Lợi
            } elseif ($empId === 'EMP_QL03') {
                $branchId = 2; // Nguyễn Tuân
            } else {
                // Các nhân viên khác có thể chia đều dựa trên index hoặc phòng ban
                $branchId = ($index % 2) + 1; 
            }

            $dept = $empData['employment']['department'];
            $roleStr = $empData['employment']['role'];

            // 2. LOGIC TỰ ĐỘNG PHÂN LOẠI VAI TRÒ (C0, C1, C2, C3) 
            // Mặc định là C0 (Nhân viên Bàn, Bếp, Nướng, Bia, Bảo vệ, Tạp vụ)
            $cLevel = 'C0';
            $posId = 4; 
            $baseSalary = 5500000;

            if (str_contains($roleStr, 'Quản lý chung')) {
                // Giám đốc / Admin
                $cLevel = 'C3'; 
                $posId = 1;
                $baseSalary = 15000000;
                $branchId = 1; // Admin gắn tạm cố định ở nhánh 1
            } elseif (str_contains($roleStr, 'Kế toán trưởng') || str_contains($roleStr, 'Giám sát')) {
                // Quản lý cơ sở
                $cLevel = 'C2'; 
                $posId = 2;
                $baseSalary = 10000000;
            } elseif ($dept === 'Quản lý') {
                // Khối Kế toán / Quỹ / Thủ kho / Kế toán Thuế
                $cLevel = 'C1'; 
                $posId = 3;
                $baseSalary = 7000000;
            }

            // 3. Lưu thông tin vào bảng employees
            $emp = Employee::create([
                'employee_code' => $empData['id'],
                'full_name'     => $empData['personalInfo']['fullName'],
                'phonenumber'   => $empData['personalInfo']['phone'],
                'role'          => $cLevel,
                'branch_id'     => $branchId,
                'type'          => 'full', // Mặc định full-time
                'pay_grade_id'  => 1,
                'status'        => 'Active',
            ]);

            // 4. Lưu tài khoản vào bảng accounts
            // Username chính là ID nhân viên viết thường (VD: emp_m01, emp_t01...)
            // Mật khẩu mặc định chung: 123
            Account::create([
                'employee_id' => $emp->id,
                'username'    => strtolower($empData['id']),
                'password'    => Hash::make('123'),
                'role'        => $cLevel,
                'is_active'   => true
            ]);

            // 5. Lưu lịch sử công tác vào bảng job_histories
            JobHistory::create([
                'employee_id'        => $emp->id,
                'branch_id'          => $branchId,
                'position_id'        => $posId,
                'pay_grade_id'       => 1,
                'start_date'         => $empData['employment']['joinDate'],
                'base_salary_amount' => $baseSalary
            ]);

            // 6. Gán Quản lý cho Chi nhánh
            // Khi duyệt trúng nhân viên C2, hệ thống sẽ gán họ làm manager cho Chi nhánh tương ứng
            if ($cLevel === 'C2') {
                DB::table('branches')
                  ->where('id', $branchId)
                  ->update(['manager_id' => $emp->id]);
            }
        }
    }
}