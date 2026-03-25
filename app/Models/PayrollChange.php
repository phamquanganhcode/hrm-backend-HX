<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollChange extends Model
{
    /** @use HasFactory<\Database\Factories\PayrollChangeFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = [];
}
