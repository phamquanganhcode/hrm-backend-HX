<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    // Một vị trí (C0, C1, C2...) có nhiều bậc lương (từ 1 đến 12)
    public function payGrades()
    {
        return $this->hasMany(PayGrade::class, 'position_id');
    }
}