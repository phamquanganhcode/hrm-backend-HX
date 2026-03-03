<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftDefinition;

class ShiftDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        ShiftDefinition::truncate();

        // 1. Ca Sáng (Ca đơn)
        ShiftDefinition::create([
            'name' => 'Sáng',
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'coefficient' => 1.0,
            'color' => '#10b981' // Màu xanh lá
        ]);

        // 2. Ca Chiều (Ca đơn)
        ShiftDefinition::create([
            'name' => 'Chiều',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'coefficient' => 1.0,
            'color' => '#f59e0b' // Màu cam
        ]);

        // 3. Ca Gãy (Có giờ nghỉ)
        ShiftDefinition::create([
            'name' => 'Gãy',
            'start_time' => '10:00:00',
            'break_start' => '14:00:00', // Nghỉ từ 14h
            'break_end' => '17:00:00',   // Làm lại lúc 17h
            'end_time' => '21:00:00',
            'coefficient' => 1.2, // Ca gãy mệt hơn nên hệ số 1.2
            'color' => '#6366f1' // Màu chàm
        ]);
    }
}