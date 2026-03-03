<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Position;
use App\Models\Employee;
use App\Models\Account;
use App\Models\LaborContract;
use App\Models\JobHistory;
use App\Models\Schedule; // Nhớ use thêm Model này

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear dữ liệu cũ (Tùy chọn, vì migrate:fresh đã xóa sạch bảng rồi, nhưng để cho chắc cũng được)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Branch::truncate(); 
        Position::truncate(); 
        Employee::truncate();
        Account::truncate(); 
        LaborContract::truncate(); 
        JobHistory::truncate();
        Schedule::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. GỌI CÁC SEEDER MỚI VÀO ĐÂY (Đây là bước quyết định)
        $this->call([
            BranchSeeder::class,
            PositionSeeder::class,
            EmployeeSeeder::class, // File này chứa logic tạo Admin + 3 NV cứng + 50 NV ảo
            AccountSeeder::class,  // File này tạo 4 tài khoản
            ScheduleSeeder::class, // File này sinh lịch làm việc cho NV003
            ShiftDefinitionSeeder::class, // TẠO CA LÀM TRƯỚC
            WorkScheduleSeeder::class,    // TẠO PHÂN CA SAU
        ]);
    }
}