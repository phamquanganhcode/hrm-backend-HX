<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Thêm dòng này

class Branch extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    // Chi nhánh có 1 Quản lý (là một Nhân viên)
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    // Chi nhánh có nhiều nhân viên
    public function employees()
    {
        return $this->hasMany(Employee::class, 'branch_id');
    }

    // Chi nhánh có nhiều bảng lương của các tháng
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'branch_id');
    }
}