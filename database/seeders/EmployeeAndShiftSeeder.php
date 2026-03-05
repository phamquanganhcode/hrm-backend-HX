<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeAndShiftSeeder extends Seeder
{
    public function run()
    {
        // 0. Tắt khóa ngoại để dọn dẹp dữ liệu cũ
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('shift_registrations')->truncate();
        DB::table('employees')->truncate(); 

        $now = Carbon::now();

        // ==========================================
        // 1. TẠO 10 NHÂN VIÊN
        // ==========================================
        $employees = [];
        for ($i = 1; $i <= 10; $i++) {
            // Đặt tên riêng cho vài ID quen thuộc
            $fullName = 'Nhân viên Test ' . $i;
            if ($i == 3) $fullName = 'Nguyễn Đầu Bếp';
            if ($i == 4) $fullName = 'Trần Chạy Bàn';
            $employees[] = [
                'id'            => $i, 
                'employee_code' => 'NV00' . $i,
                'full_name'     => 'Nhân viên Test ' . $i,
                'type'          => ($i <= 2) ? 'Full-time' : 'Part-time', 
                'role'          => 'employee_staff',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        DB::table('employees')->insert($employees);

        // ==========================================
        // 2. TẠO ĐĂNG KÝ CA CÓ CHỦ ĐÍCH & NGẪU NHIÊN
        // ==========================================
        $registrations = [];
        $usedCombinations = []; // Mảng "sổ đen" để chống lặp dữ liệu

        // 👉 ĐIỂM NHẤN 1: CA FULL (5/5) - Thứ 2, Ca Sáng (ID: 1)
        for ($i = 3; $i <= 7; $i++) {
            $key = "$i-2026-03-09-1"; // Ghi vào sổ đen
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 1, 'request_date' => '2026-03-09', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 ĐIỂM NHẤN 2: CA FULL (5/5) - Thứ 4, Ca Gãy (ID: 5)
        for ($i = 6; $i <= 10; $i++) {
            $key = "$i-2026-03-11-5"; 
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 5, 'request_date' => '2026-03-11', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 ĐIỂM NHẤN 3: CA SẮP FULL (4/5) - Thứ 6, Ca Chiều (ID: 3)
        // Chỉ lấy 4 nhân viên (từ ID 3 đến 6)
        for ($i = 3; $i <= 6; $i++) {
            $key = "$i-2026-03-13-3"; 
            $usedCombinations[$key] = true; 
            $registrations[] = ['employee_id' => $i, 'shift_id' => 3, 'request_date' => '2026-03-13', 'created_at' => $now, 'updated_at' => $now];
        }

        // 👉 TẠO CÁC CA NGẪU NHIÊN CHO TỰ NHIÊN (Tránh các ca đã tạo ở trên)
        $days = ['2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12', '2026-03-13', '2026-03-14', '2026-03-15'];
        
        foreach ($days as $date) {
            $numberOfRegs = rand(2, 4); // Mỗi ngày rải thêm 2-4 lượt đăng ký rác
            $count = 0;
            $attempts = 0; 

            while ($count < $numberOfRegs && $attempts < 50) {
                $empId = rand(3, 10);
                $shiftId = rand(1, 5);
                $key = "$empId-$date-$shiftId";

                // Nếu combo này CHƯA AI ĐĂNG KÝ thì mới thêm vào
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

        // CHỈ INSERT 1 LẦN DUY NHẤT Ở ĐÂY
        DB::table('shift_registrations')->insert($registrations);

        // Bật lại khóa ngoại an toàn
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}