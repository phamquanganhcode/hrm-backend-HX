<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Position;
use App\Models\Employee;
use App\Models\Account;
// Nhớ tạo thêm Model LaborContract và JobHistory nhé
use App\Models\LaborContract;
use App\Models\JobHistory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa sạch dữ liệu cũ để tránh lỗi trùng lặp khi chạy lại
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Branch::truncate(); Position::truncate(); Employee::truncate();
        Account::truncate(); LaborContract::truncate(); JobHistory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. TẠO CHI NHÁNH
        $branchTL = Branch::create(['name' => 'Chi nhánh Thủy Lợi', 'address' => '175 Tây Sơn, Đống Đa']);
        $branchCG = Branch::create(['name' => 'Chi nhánh Cầu Giấy', 'address' => '123 Xuân Thủy, Cầu Giấy']);
        $branchHM = Branch::create(['name' => 'Chi nhánh Hoàng Mai', 'address' => '456 Thúy Lĩnh, Hoàng Mai']);

        // 2. TẠO VỊ TRÍ (CHỨC VỤ)
        $posManager = Position::create(['name' => 'Quản lý cơ sở', 'code' => 'MNG', 'base_salary_default' => 15000000, 'is_manager' => true]);
        $posChef = Position::create(['name' => 'Bếp trưởng', 'code' => 'CHEF', 'base_salary_default' => 12000000, 'is_manager' => false]);
        $posStaff = Position::create(['name' => 'Nhân viên phục vụ', 'code' => 'STAFF', 'hourly_rate' => 25000, 'is_manager' => false]);

        // 3. TẠO NHÂN VIÊN
        // NV1: Quản lý
        $manager = Employee::create([
            'employee_code' => 'NV001', 'full_name' => 'Trần Quản Lý',
            'email' => 'quanly@haixom.com', 'phonenumber' => '0911111111',
            'role' => 'manager', 'branch_id' => $branchTL->id,
            'avatar_url'=>'https://i.pravatar.cc/150/quanly',
            'type' => 'full', 'base_salary' => 15000000, 'status' => 'active',
        ]);

        // NV2: Đầu Bếp
        $chef = Employee::create([
            'employee_code' => 'NV002', 'full_name' => 'Nguyễn Đầu Bếp',
            'email' => 'bep@haixom.com', 'phonenumber' => '0922222222',
            'role' => 'employee_chef', 'branch_id' => $branchHM->id,
            'avatar_url'=>'https://i.pravatar.cc/150/bep',
            'type' => 'full', 'base_salary' => 12000000, 'status' => 'active',
        ]);

        // NV3: Nhân viên chạy bàn (Bạn đang dùng account này test)
        $staff = Employee::create([
            'employee_code' => 'NV003', 'full_name' => 'Nguyễn Văn Bàn',
            'email' => 'ban@haixom.com', 'phonenumber' => '0987654321',
            'role' => 'employee_staff', 'branch_id' => $branchTL->id,
            'avatar_url'=>'https://i.pravatar.cc/150/ban',
            'type' => 'part', 'base_salary' => 35000, 'status' => 'active',
        ]);

        // 4. TẠO TÀI KHOẢN
        Account::create(['employee_id' => $manager->id, 'username' => 'manager', 'password' => Hash::make('123'), 'role' => 'manager']);
        Account::create(['employee_id' => $chef->id, 'username' => 'nhanvienbep', 'password' => Hash::make('123'), 'role' => 'employee_chef']);
        Account::create(['employee_id' => $staff->id, 'username' => 'nhanvienban', 'password' => Hash::make('123'), 'role' => 'employee_staff']);

        // 5. TẠO HỢP ĐỒNG
        LaborContract::create(['employee_id' => $chef->id, 'start_date' => '2023-01-01', 'end_date' => '2024-01-01']);
        LaborContract::create(['employee_id' => $staff->id, 'start_date' => '2023-06-01', 'end_date' => null]); // Không thời hạn

        // 6. TẠO LỊCH SỬ CÔNG TÁC
        // Giả lập ông Bàn từng làm ở Cầu Giấy, sau đó chuyển về Thủy Lợi
        JobHistory::create([
            'employee_id' => $staff->id, 'branch_id' => $branchCG->id, 'position_id' => $posStaff->id,
            'start_date' => '2023-06-01', 'end_date' => '2023-12-31'
        ]);
        JobHistory::create([
            'employee_id' => $staff->id, 'branch_id' => $branchTL->id, 'position_id' => $posStaff->id,
            'start_date' => '2024-01-01', 'end_date' => null
        ]);

        // Giả lập ông Bếp từng làm ở Hoàng Mai, sau đó chuyển về Thủy Lợi
        JobHistory::create([
            'employee_id' => $chef->id, 'branch_id' => $branchHM->id, 'position_id' => $posChef->id,
            'start_date' => '2023-01-01', 'end_date' => '2023-12-31'
        ]);
        JobHistory::create([
            'employee_id' => $chef->id, 'branch_id' => $branchTL->id, 'position_id' => $posChef->id,
            'start_date' => '2024-01-01', 'end_date' => null
        ]); 
    }
}