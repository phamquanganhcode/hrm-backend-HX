<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeLog extends Model
{
    /** @use HasFactory<\Database\Factories\TimeLogFactory> */
    use HasFactory;
    use SoftDeletes; protected $guarded = [];
}
