<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // Đổi extends
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = ['employee_id', 'username', 'password', 'role', 'is_active'];
    
    // Ẩn password khỏi API trả về
    protected $hidden = ['password'];
    
    // Thêm đoạn này vào dưới cùng của class Account
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}