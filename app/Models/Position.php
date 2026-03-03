<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Chuẩn ở đây!
class Position extends Model
{
    use HasFactory; // Model cầm "chìa khóa" này thì mới mở được Factory
}