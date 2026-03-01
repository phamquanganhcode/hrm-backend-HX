<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // Lịch sử này thuộc về Chi nhánh nào?
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Lịch sử này làm Vị trí gì?
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}