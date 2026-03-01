<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
        protected $fillable = [
        'employee_code', 'full_name', 'email', 'phonenumber', 
        'avatar_url', 'fingerprint_id', 'role', 'branch_id', 
        'type', 'base_salary', 'status'
    ];
    public function branch()
    {
        // 1 Nhân viên thuộc về 1 Cơ sở (Branch)
        return $this->belongsTo(Branch::class, 'branch_id');
    }
        // 1 Nhân viên có nhiều Lịch sử công tác
    public function jobHistories()
    {
        return $this->hasMany(JobHistory::class, 'employee_id');
    }
}