<?php

namespace App\Traits;

trait ApiResponse
{
    // Hàm trả về khi Thành công (200, 201)
    protected function successResponse($data, $message = 'Thành công', $code = 200)
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    // Hàm trả về khi Lỗi (400, 401, 403, 404, 500)
    protected function errorResponse($message, $code, $errors = null)
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        // Nếu có chi tiết lỗi (ví dụ: lỗi validate form) thì nhét thêm vào
        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}