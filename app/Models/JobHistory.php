<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobHistory extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function position() { return $this->belongsTo(Position::class); }
    public function payGrade() { return $this->belongsTo(PayGrade::class); }
}