<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Employee;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy tài khoản nhân viên bàn (NV003) để test lịch làm việc cho trực quan
        $staff = Employee::where('employee_code', 'NV003')->first();
        
        if (!$staff) return;

        // Giả lập lịch làm việc cho tháng hiện tại (Tháng 3/2026)
        $year = 2026;
        $month = 3;
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            // Xác suất: 80% đi làm, 20% nghỉ
            $isWorkingDay = rand(1, 100) <= 80;

            if ($isWorkingDay) {
                // Định dạng ngày chuẩn YYYY-MM-DD cho FE
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

                // Random loại ca làm việc
                $shiftType = rand(1, 3); 
                
                if ($shiftType === 1) { // Ca Sáng (Ca đơn)
                    $this->createSchedule($staff->id, $dateStr, 'Ca Sáng', '08:00:00', '12:00:00');
                } elseif ($shiftType === 2) { // Ca Chiều (Ca đơn)
                    $this->createSchedule($staff->id, $dateStr, 'Ca Chiều', '13:00:00', '17:00:00');
                } else { // Ca Gãy
                    $this->createSchedule($staff->id, $dateStr, 'Ca Gãy', '08:00:00', '12:00:00', '13:30:00', '17:30:00');
                }
            }
            // Nếu $isWorkingDay = false -> Bỏ qua không insert DB -> FE sẽ tự hiểu là "Nghỉ"
        }
    }

    private function createSchedule($empId, $date, $shiftName, $s1, $e1, $s2 = null, $e2 = null)
    {
        Schedule::create([
            'employee_id' => $empId,
            'date' => $date, // YYYY-MM-DD chuẩn
            'shift_name' => $shiftName,
            'start_time_1' => $s1,
            'end_time_1' => $e1,
            'start_time_2' => $s2,
            'end_time_2' => $e2,
            'location' => 'Khu vực chính',
        ]);
    }
}