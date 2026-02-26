<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Exception;

class AuthController extends Controller
{
    protected $authService;

    // Inject AuthService vào Controller
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        // Validate đầu vào nhanh
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Gọi Service xử lý logic
            $data = $this->authService->login($request->username, $request->password);
            
            // Dùng trait trả về chuẩn Format
            return $this->successResponse($data, 'Đăng nhập thành công!');
            
        } catch (Exception $e) {
            // Bắt lỗi từ Service và trả về
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    public function me(Request $request)
    {
        // $request->user() sẽ lấy ra cái Account đang cầm Token hợp lệ
        // Dùng hàm load('employee') để tự động JOIN sang bảng employees lấy họ tên
        $account = $request->user()->load('employee');

        // Trả về data cho Frontend
        return $this->successResponse($account, 'Lấy thông tin thành công');
    }
}