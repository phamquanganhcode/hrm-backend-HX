<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// CHÚ Ý: Phải có chữ implements ShouldBroadcastNow
class AttendanceRealtimeEvent implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendanceData;

    // Khi sự kiện được gọi, ta nhét cục data muốn gửi đi vào đây
    public function __construct($attendanceData)
    {
        $this->attendanceData = $attendanceData;
    }

    // Xác định "Tần số đài FM" để phát đi
    public function broadcastOn()
    {
        return new Channel('attendance-channel');
    }

    // Tên của bản tin
    public function broadcastAs()
    {
        return 'new-scan';
    }
}