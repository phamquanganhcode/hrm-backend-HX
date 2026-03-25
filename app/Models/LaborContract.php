<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Thêm dòng này

class LaborContract extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    // Các trường ngày tháng nên ép kiểu về Carbon
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}