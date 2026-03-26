<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyAttendance extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    // 🟢 THÊM HÀM NÀY ĐỂ KẾT NỐI VỚI BẢNG NHÂN VIÊN
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}