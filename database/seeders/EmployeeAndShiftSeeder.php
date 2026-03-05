<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use Carbon\Carbon;

class EmployeeAndShiftSeeder extends Seeder
{
    public function run()
    {
        // 0. Tắt khóa ngoại để dọn dẹp
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('shift_registrations')->truncate();
        DB::table('employees')->truncate(); 

        $now = Carbon::now();

        // Lấy chi nhánh giống file cũ của bạn (Nếu không có thì mặc định ID = 1)
        $branchTL = Branch::where('name', 'Chi nhánh Thủy Lợi')->first();
        $branchHM = Branch::where('name', 'Chi nhánh Hoàng Mai')->first();
        $branchTLId = $branchTL ? $branchTL->id : 1;
        $branchHMId = $branchHM ? $branchHM->id : 1;

        // ==========================================
        // 1. TẠO 4 NHÂN VIÊN CỐ ĐỊNH NHƯ FILE CỦA BẠN (ID 1 -> 4)
        // ==========================================
        $employees = [
            [
                'id' => 1, 'employee_code' => 'ADMIN01', 'full_name' => 'Nguyễn Quản Trị',
                'email' => 'admin@haixom.com', 'phonenumber' => '0900000000',
                'role' => 'admin', 'branch_id' => $branchTLId,
                'type' => 'full', 'base_salary' => 20000000, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2, 'employee_code' => 'NV001', 'full_name' => 'Trần Quản Lý',
                'email' => 'quanly@haixom.com', 'phonenumber' => '0911111111',
                'role' => 'manager', 'branch_id' => $branchTLId,
                'type' => 'full', 'base_salary' => 15000000, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 3, 'employee_code' => 'NV002', 'full_name' => 'Nguyễn Đầu Bếp',
                'email' => 'bep@haixom.com', 'phonenumber' => '0922222222',
                'role' => 'employee_chef', 'branch_id' => $branchHMId,
                'type' => 'full', 'base_salary' => 12000000, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 4, 'employee_code' => 'NV003', 'full_name' => 'Nguyễn Văn Bàn',
                'email' => 'ban@haixom.com', 'phonenumber' => '0987654321',
                'role' => 'employee_staff', 'branch_id' => $branchTLId,
                'type' => 'part', 'base_salary' => 35000, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ]
        ];

        // ==========================================
        // 2. TẠO 10 NHÂN VIÊN "ẢO" ĐỂ TEST CA LÀM VIỆC (ID 1000 -> 1009)
        // ==========================================
        for ($i = 1000; $i <= 1009; $i++) {
            $employees[] = [
                'id' => $i, 'employee_code' => 'NV' . $i, 'full_name' => 'Nhân viên Test ' . $i,
                'email' => "test{$i}@haixom.com", 'phonenumber' => "099999{$i}",
                'role' => 'employee_staff', 'branch_id' => $branchTLId,
                'type' => 'part', 'base_salary' => 35000, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        
        DB::table('employees')->insert($employees);

        // ==========================================
        // 3. TẠO ĐĂNG KÝ CA CÓ CHỦ ĐÍCH & NGẪU NHIÊN 
        // (Chỉ dùng các ID ảo từ 1000 -> 1009)
        // ==========================================
        $registrations = [];
        $usedCombinations = []; 

        // 👉 ĐIỂM NHẤN 1: CA FULL (5/5) - Thứ 2, Ca Sáng (ID: 1)
        for ($i = 1000; $i <= 1004; $i++) {
            $key = "$i-2026-03-09-1"; 
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 1, 'request_date' => '2026-03-09', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 ĐIỂM NHẤN 2: CA FULL (5/5) - Thứ 4, Ca Gãy (ID: 5)
        for ($i = 1005; $i <= 1009; $i++) {
            $key = "$i-2026-03-11-5"; 
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 5, 'request_date' => '2026-03-11', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 ĐIỂM NHẤN 3: CA SẮP FULL (4/5) - Thứ 6, Ca Chiều (ID: 3)
        for ($i = 1000; $i <= 1003; $i++) {
            $key = "$i-2026-03-13-3"; 
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 3, 'request_date' => '2026-03-13', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 TẠO CÁC CA NGẪU NHIÊN
        $days = ['2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12', '2026-03-13', '2026-03-14', '2026-03-15'];
        
        foreach ($days as $date) {
            $numberOfRegs = rand(2, 4); 
            $count = 0;
            $attempts = 0; 

            while ($count < $numberOfRegs && $attempts < 50) {
                // CHỈ LẤY ID TỪ 1000 ĐẾN 1009 (Chừa 4 nhân viên chính ra)
                $empId = rand(1000, 1009); 
                $shiftId = rand(1, 5);
                $key = "$empId-$date-$shiftId";

                if (!isset($usedCombinations[$key])) {
                    $usedCombinations[$key] = true; 
                    $registrations[] = [
                        'employee_id'  => $empId,
                        'shift_id'     => $shiftId,
                        'request_date' => $date,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                    $count++;
                }
                $attempts++;
            }
        }

        DB::table('shift_registrations')->insert($registrations);

        // Bật lại khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}