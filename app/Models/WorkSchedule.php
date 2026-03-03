<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = [];
    protected $casts = [
        'date' => 'date:Y-m-d', // Ép kiểu dữ liệu trả về luôn là YYYY-MM-DD
    ];
    // Nối với Nhân viên
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Nối với Ca làm (Lưu ý: Thay ShiftDefinition bằng tên Model thật của bạn)
    public function shift()
    {
        return $this->belongsTo(ShiftDefinition::class, 'shift_id'); 
    }

    // Nối với Vị trí
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    // Nối với Cơ sở
    public function workBranch()
    {
        return $this->belongsTo(Branch::class, 'work_branch_id');
    }
}