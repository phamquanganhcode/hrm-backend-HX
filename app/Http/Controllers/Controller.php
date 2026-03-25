<?php

namespace App\Http\Controllers;
use App\Traits\ApiResponse;

abstract class Controller
{
    use ApiResponse; // Từ nay mọi Controller con đều gọi được hàm successResponse!
    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message = null, $code)
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => null
        ], $code);
    }
}