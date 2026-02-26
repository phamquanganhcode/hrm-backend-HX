<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Exception;

class AuthService
{
    public function login($username, $password)
    {
        // 1. Tìm tài khoản theo username
        $account = Account::where('username', $username)->first();

        // 2. Kiểm tra tài khoản có tồn tại, có bị khóa không và check mật khẩu
        if (!$account || !Hash::check($password, $account->password)) {
            throw new Exception('Tài khoản hoặc mật khẩu không chính xác!', 401);
        }

        if (!$account->is_active) {
            throw new Exception('Tài khoản của bạn đã bị khóa!', 403);
        }

        // 3. Tạo Token Sanctum
        $token = $account->createToken('HRM-API-Token')->plainTextToken;

        // 4. Trả về data
        return [
            'token' => $token,
            'account' => $account->load('employee') // Tự động JOIN sang bảng employees lấy họ tên
        ];
    }
}