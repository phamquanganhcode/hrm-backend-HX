<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceSyncLog extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceSyncLogFactory> */
    use HasFactory, SoftDeletes;
    protected $guarded = [];
}
