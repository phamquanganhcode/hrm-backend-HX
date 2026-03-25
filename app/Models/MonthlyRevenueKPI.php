<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyRevenueKPI extends Model
{
    /** @use HasFactory<\Database\Factories\MonthlyRevenueKPIFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = [];
}
