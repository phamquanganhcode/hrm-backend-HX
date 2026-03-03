<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Tự động đính kèm thuộc tính 'fe_time_format' mỗi khi query Schedule
    protected $appends = ['fe_time_format']; 

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Accessor: Tạo ra chuỗi thời gian chuẩn xác cho FE tính toán
    public function getFeTimeFormatAttribute()
    {
        if (!$this->start_time_1 || !$this->end_time_1) {
            return null;
        }

        // Cắt bỏ phần giây, chỉ lấy HH:mm
        $start1 = Carbon::parse($this->start_time_1)->format('H:i');
        $end1 = Carbon::parse($this->end_time_1)->format('H:i');
        
        $timeString = "$start1 - $end1";

        // Nếu là ca gãy (có thời gian 2)
        if ($this->start_time_2 && $this->end_time_2) {
            $start2 = Carbon::parse($this->start_time_2)->format('H:i');
            $end2 = Carbon::parse($this->end_time_2)->format('H:i');
            $timeString .= " & $start2 - $end2";
        }

        return $timeString;
    }
}