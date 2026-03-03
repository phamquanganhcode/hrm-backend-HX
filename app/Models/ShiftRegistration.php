<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftRegistration extends Model
{
    use HasFactory, SoftDeletes; // Kích hoạt tính năng SoftDeletes theo hình ảnh

    protected $guarded = [];
    protected $fillable = [
        'employee_id', 
        'request_date', 
        'shift_id', 
        'position_id', 
        'priority', 
        'is_assigned', 
        'weekly_plan_id'
    ];

    // Liên kết với bảng Nhân viên
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Liên kết với bảng Ca làm việc (Đảm bảo Class name khớp với dự án của bạn)
    public function shift()
    {
        return $this->belongsTo(ShiftDefinition::class, 'shift_id'); 
    }

    // Liên kết với bảng Vị trí (Position)
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}