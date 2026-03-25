<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Cast JSON để Laravel tự động parse thành mảng/object khi gọi ra
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function actor() { return $this->belongsTo(Account::class, 'actor_id'); }
}