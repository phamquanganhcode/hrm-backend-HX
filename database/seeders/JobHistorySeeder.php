<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobHistory;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;

class JobHistorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. TẠO DỮ LIỆU CỐ ĐỊNH (Cho NV002 và NV003)
        $staff = Employee::where('employee_code', 'NV003')->first();
        $chef = Employee::where('employee_code', 'NV002')->first();

        $branchCG = Branch::where('name', 'Chi nhánh Cầu Giấy')->first();
        $branchTL = Branch::where('name', 'Chi nhánh Thủy Lợi')->first();
        $branchHM = Branch::where('name', 'Chi nhánh Hoàng Mai')->first();

        $posStaff = Position::where('code', 'STAFF')->first();
        $posChef = Position::where('code', 'CHEF')->first();

        if ($staff && $branchCG && $branchTL) {
            JobHistory::create([
                'employee_id' => $staff->id, 'branch_id' => $branchCG->id, 'position_id' => $posStaff->id,
                'start_date' => '2023-06-01', 'end_date' => '2023-12-31'
            ]);
            JobHistory::create([
                'employee_id' => $staff->id, 'branch_id' => $branchTL->id, 'position_id' => $posStaff->id,
                'start_date' => '2024-01-01', 'end_date' => null
            ]);
        }

        if ($chef && $branchHM && $branchTL) {
            JobHistory::create([
                'employee_id' => $chef->id, 'branch_id' => $branchHM->id, 'position_id' => $posChef->id,
                'start_date' => '2023-01-01', 'end_date' => '2023-12-31'
            ]);
            JobHistory::create([
                'employee_id' => $chef->id, 'branch_id' => $branchTL->id, 'position_id' => $posChef->id,
                'start_date' => '2024-01-01', 'end_date' => null
            ]);
        }

        // 2. TẠO 50 DỮ LIỆU NGẪU NHIÊN BẰNG FACTORY
        // Lệnh này sẽ tự động gọi JobHistoryFactory để sinh 50 bản ghi
        JobHistory::factory(50)->create();
    }
}