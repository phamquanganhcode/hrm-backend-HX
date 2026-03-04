<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;

class EmployeeScheduleSeeder extends Seeder
{
    public function run()
    {
        // 1. Dữ liệu mẫu: Lịch CỐ ĐỊNH (FIXED) cho Full-time (Làm ca Sáng Thứ 2)
        EmployeeSchedule::create([
            'employee_id' => 'EMP_FULL_01',
            'day_of_week' => 1, // Thứ 2
            'shift_id'    => 1, // Giả sử ID 1 là ca Sáng
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => null, // Áp dụng mãi mãi
            'status'      => 'Active',
            'type'        => 'FIXED',
            'approver_id' => 'ADMIN_01'
        ]);
        EmployeeSchedule::create([
            'employee_id' => 'EMP_FULL_01',
            'day_of_week' => 2, // Thứ 3
            'shift_id'    => 2, // Giả sử ID 2 là ca trua
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => null, // Áp dụng mãi mãi
            'status'      => 'Active',
            'type'        => 'FIXED',
            'approver_id' => 'ADMIN_01'
        ]);
        EmployeeSchedule::create([
            'employee_id' => 'EMP_FULL_01',
            'day_of_week' => 4, // Thứ 5
            'shift_id'    => 4, // Giả sử ID 4 là ca toi
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => null, // Áp dụng mãi mãi
            'status'      => 'Active',
            'type'        => 'FIXED',
            'approver_id' => 'ADMIN_01'
        ]);
        EmployeeSchedule::create([
            'employee_id' => 'EMP_FULL_01',
            'day_of_week' => 4, // Thứ 5
            'shift_id'    => 5, // Giả sử ID 4 là ca toi
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => null, // Áp dụng mãi mãi
            'status'      => 'Active',
            'type'        => 'FIXED',
            'approver_id' => 'ADMIN_01'
        ]);


        // 2. Dữ liệu mẫu: Lịch BẬN (BUSY) cho Part-time (Bận đi học ca Chiều Thứ 4)
        EmployeeSchedule::create([
            'employee_id' => 'EMP_PART_01',
            'day_of_week' => 3, // Thứ 4
            'shift_id'    => 2, // Giả sử ID 2 là ca Chiều
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => Carbon::now()->addMonths(3), // Bận trong 3 tháng học kỳ
            'reason'      => 'Lịch học quân sự',
            'status'      => 'Active',
            'type'        => 'BUSY',
            'approver_id' => 'ADMIN_01'
        ]);
        EmployeeSchedule::create([
            'employee_id' => 'EMP_PART_01',
            'day_of_week' => 2, // Thứ 3
            'shift_id'    => 3, // Giả sử ID 3 là ca chiều
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => Carbon::now()->addMonths(3), // Bận trong 3 tháng học kỳ
            'reason'      => 'Lịch học quân sự',
            'status'      => 'Active',
            'type'        => 'BUSY',
            'approver_id' => 'ADMIN_01'
        ]);
        EmployeeSchedule::create([
            'employee_id' => 'EMP_PART_01',
            'day_of_week' => 2, // Thứ 3
            'shift_id'    => 4, // Giả sử ID 3 là ca chiều
            'start_date'  => Carbon::now()->startOfWeek(),
            'end_date'    => Carbon::now()->addMonths(3), // Bận trong 3 tháng học kỳ
            'reason'      => 'Lịch học quân sự',
            'status'      => 'Active',
            'type'        => 'BUSY',
            'approver_id' => 'ADMIN_01'
        ]);
    }
}