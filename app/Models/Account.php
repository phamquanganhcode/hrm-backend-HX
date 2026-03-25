<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable; // Dùng để đăng nhập Sanctum/JWT
use Laravel\Sanctum\HasApiTokens; // 🟢 1. Khai báo thư viện tạo Token ở đây

class Account extends Authenticatable // Kế thừa từ Authenticatable thay vì Model thường
{
    use HasApiTokens, SoftDeletes; // 🟢 2. Kích hoạt HasApiTokens ở bên trong class
    protected $guarded = [];

    // 🛡️ BẢO MẬT: Giấu tuyệt đối cột password khi in dữ liệu ra JSON
    protected $hidden = [
        'password',
    ];
}