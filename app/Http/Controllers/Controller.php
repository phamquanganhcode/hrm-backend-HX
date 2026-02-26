<?php

namespace App\Http\Controllers;
use App\Traits\ApiResponse;

abstract class Controller
{
    use ApiResponse; // Từ nay mọi Controller con đều gọi được hàm successResponse!
}