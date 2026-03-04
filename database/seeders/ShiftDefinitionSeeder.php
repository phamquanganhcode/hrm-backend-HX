<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftDefinition;
use Illuminate\Support\Facades\DB;

class ShiftDefinitionSeeder extends Seeder
{
    public function run()
    {
        // 1. Xóa sạch dữ liệu cũ để không bị trùng lặp khi chạy nhiều lần
        // Dùng DB::statement để bỏ qua check khóa ngoại (nếu có)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ShiftDefinition::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Định nghĩa đúng 5 ca y hệt MockData của Frontend
        $shifts = [
            [
                'name' => 'Sáng',
                'start_time' => '06:00:00',
                'end_time' => '10:00:00',
                'fe_time_format' => '6:00-10:00', // Cột này dùng để hiển thị trên UI
                'is_active' => true,
            ],
            [
                'name' => 'Trưa',
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
                'fe_time_format' => '10:00-14:00',
                'is_active' => true,
            ],
            [
                'name' => 'Chiều',
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'fe_time_format' => '14:00-18:00',
                'is_active' => true,
            ],
            [
                'name' => 'Tối',
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
                'fe_time_format' => '18:00-22:00',
                'is_active' => true,
            ],
            [
                'name' => 'Gãy',
                'start_time' => '10:15:00',
                'end_time' => '21:00:00',
                'fe_time_format' => '10:15-21:00',
                'is_active' => true,
            ]
        ];

        // 3. Insert vào Database
        foreach ($shifts as $shift) {
            ShiftDefinition::create($shift);
        }
    }
}