<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayGrade extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    // Bậc lương thuộc về 1 Vị trí cụ thể
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    // Nhiều nhân viên có thể đang hưởng cùng 1 Bậc lương
    public function employees()
    {
        return $this->hasMany(Employee::class, 'pay_grade_id');
    }
}