<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\DailyAttendance;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $today = Carbon::now()->toDateString(); // Lấy đúng ngày hôm nay để FE gọi lên có data

        // Lấy tất cả nhân viên của Chi nhánh 1 (Nơi Trần Kế Toán đang làm)
        $employees = Employee::where('branch_id', 1)->get();

        foreach ($employees as $emp) {
            // Giả lập 10% nhân viên bị lỗi Ngoại lệ (Đi muộn)
            $isException = $faker->boolean(10); 
            $lateMinutes = $isException ? $faker->randomElement([15, 30, 45]) : 0;
            $overtime = $faker->randomElement([0, 0, 0, 1.5, 2]);

            DailyAttendance::create([
                'employee_id'          => $emp->id,
                'date'                 => $today,
                'actual_branch_id'     => 1,
                'work_schedule_id'     => null,
                'total_work_hours'     => 8.0 - ($lateMinutes / 60) + $overtime,
                'late_minutes'         => $lateMinutes,
                'early_minutes'        => 0,
                'overtime_hours'       => $overtime,
                'status'               => $isException ? 'Ngoại lệ' : 'Chờ duyệt',
                'is_holiday'           => false,
                'is_manually_adjusted' => false,
            ]);
        }
    }
}