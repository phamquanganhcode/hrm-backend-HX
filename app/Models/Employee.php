<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
    'employee_code', 'full_name', 'email', 'phonenumber', 
    'avatar_url', 'fingerprint_id', 'role', 'branch_id', 
    'type', 'base_salary', 'status'
];
}