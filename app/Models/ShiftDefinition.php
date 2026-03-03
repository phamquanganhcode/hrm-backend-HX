<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ShiftDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    
    // Tự động đính kèm fe_time_format mỗi khi gọi API
    protected $appends = ['fe_time_format'];

    // Liên kết: 1 Ca làm có thể được xếp nhiều lần trong bảng Phân ca
    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class, 'shift_id');
    }

    // Accessor: Xử lý định dạng giờ cho Frontend
    public function getFeTimeFormatAttribute()
    {
        if (!$this->start_time || !$this->end_time) return null;

        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');

        // Nếu CÓ thời gian nghỉ (Là Ca Gãy) -> Nối 2 khoảng thời gian
        if ($this->break_start && $this->break_end) {
            $breakStart = Carbon::parse($this->break_start)->format('H:i');
            $breakEnd = Carbon::parse($this->break_end)->format('H:i');
            
            return "$start - $breakStart & $breakEnd - $end";
        }

        // Nếu KHÔNG CÓ thời gian nghỉ (Ca Đơn bình thường)
        return "$start - $end";
    }
}