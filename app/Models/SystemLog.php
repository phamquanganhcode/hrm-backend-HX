<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs'; // Tên bảng trong DB (có thể có 's' hoặc không tùy bạn thiết kế)

    // Tắt updated_at vì log chỉ có created_at (thêm mới)
    public const UPDATED_AT = null; 

    protected $fillable = [
        'actor_id',
        'action',
        'target_table',
        'target_id',
        'old_value',
        'new_value',
        'created_at'
    ];
}