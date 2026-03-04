<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'day_of_week',
        'shift_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'type',
        'approver_id'
    ];

    // Mối quan hệ với bảng ShiftDefinition (nếu bạn đã có)
    public function shift()
    {
        return $this->belongsTo(ShiftDefinition::class, 'shift_id');
    }
}