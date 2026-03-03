<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Chuẩn ở đây!

class Branch extends Model
{
    use HasFactory; // Model cầm "chìa khóa" này thì mới mở được Factory
    protected $fillable = ['name', 'address', 'manager_id'];
}