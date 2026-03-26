<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Account;
use App\Models\JobHistory;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Khởi tạo Faker với bộ từ vựng Tiếng Việt
        $faker = Faker::create('vi_VN');

        // ==========================================
        // 1. GIỮ LẠI 4 TÀI KHOẢN CỐT LÕI ĐỂ ANH EM MÌNH TEST
        // ==========================================
        $coreUsers = [
            ['code' => 'HX_ADMIN', 'name' => 'Lê Tổng Quản', 'role' => 'C3', 'branch_id' => 1, 'pos_id' => 1, 'username' => 'admin'],
            ['code' => 'HX_MANAGER', 'name' => 'Nguyễn Quản Lý', 'role' => 'C2', 'branch_id' => 1, 'pos_id' => 2, 'username' => 'manager'],
            ['code' => 'HX_ACCOUNTING', 'name' => 'Trần Kế Toán', 'role' => 'C1', 'branch_id' => 1, 'pos_id' => 3, 'username' => 'accounting'],
            ['code' => 'HX_STAFF', 'name' => 'Phạm Chạy Bàn', 'role' => 'C0', 'branch_id' => 1, 'pos_id' => 4, 'username' => 'nhanvienban'],
        ];

        foreach ($coreUsers as $u) {
            $emp = Employee::create([
                'employee_code' => $u['code'],
                'full_name'     => $u['name'],
                'role'          => $u['role'],
                'branch_id'     => $u['branch_id'],
                'type'          => 'full',
                'pay_grade_id'  => 1,
                'status'        => 'active',
                'phonenumber'   => '09' . $faker->randomNumber(8, true) // Sinh sđt ngẫu nhiên
            ]);

            Account::create([
                'employee_id' => $emp->id,
                'username'    => $u['username'],
                'password'    => Hash::make('123'),
                'role'        => $u['role'],
                'is_active'   => true
            ]);

            JobHistory::create([
                'employee_id'        => $emp->id,
                'branch_id'          => $u['branch_id'],
                'position_id'        => $u['pos_id'],
                'pay_grade_id'       => 1,
                'start_date'         => now()->subMonths(6)->toDateString(),
                'base_salary_amount' => 5500000
            ]);
        }

        // ==========================================
        // 2. DÙNG VÒNG LẶP ĐẺ RA 40 NHÂN VIÊN ẢO
        // ==========================================
        for ($i = 1; $i <= 40; $i++) {
            
            // Random Chi nhánh (1 hoặc 2)
            $branchId = $faker->randomElement([1, 2]);
            
            // Random Chức vụ (Chủ yếu là C0 - Phục vụ/Bếp, thi thoảng có 1 ông C1)
            $role = $faker->randomElement(['C0', 'C0', 'C0', 'C0', 'C1']);
            $posId = ($role === 'C1') ? 3 : 4; 
            
            // Tạo mã nhân viên tịnh tiến: HX_0101, HX_0102...
            $empCode = 'HX_' . str_pad($i + 100, 4, '0', STR_PAD_LEFT);

            // Sinh tên tiếng Việt cực kỳ chân thực
            $fullName = $faker->lastName . ' ' . $faker->middleName . ' ' . $faker->firstName;

            // Tạo Hồ sơ Nhân viên
            $emp = Employee::create([
                'employee_code' => $empCode,
                'full_name'     => $fullName,
                'role'          => $role,
                'branch_id'     => $branchId,
                'type'          => $faker->randomElement(['full', 'part']),
                'pay_grade_id'  => 1, 
                'status'        => 'active',
                'phonenumber'   => $faker->phoneNumber
            ]);

            // Tạo Account cho họ (Username chính là mã nhân viên viết thường)
            Account::create([
                'employee_id' => $emp->id,
                'username'    => strtolower($empCode), // VD: hx_0101
                'password'    => Hash::make('123'),
                'role'        => $role,
                'is_active'   => true
            ]);

            // Tạo lịch sử công tác
            JobHistory::create([
                'employee_id'        => $emp->id,
                'branch_id'          => $branchId,
                'position_id'        => $posId,
                'pay_grade_id'       => 1,
                // Random ngày vào làm từ 1 năm trước đến 1 tháng trước
                'start_date'         => $faker->dateTimeBetween('-1 years', '-1 months')->format('Y-m-d'),
                'base_salary_amount' => ($role === 'C1') ? 7000000 : 5500000
            ]);
        }
    }
}