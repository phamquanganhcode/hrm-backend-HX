<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ShiftDefinition extends Model {
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $fillable = ['name', 'start_time', 'break_start', 'break_end', 'end_time', 'coefficient', 'is_active', 'is_overnight', 'color'];
}