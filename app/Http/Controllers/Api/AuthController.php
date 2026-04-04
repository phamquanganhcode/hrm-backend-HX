<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Employee;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $account = Account::where('username', $request->username)->first();

            if (!$account) {
                return response()->json(['message' => 'Tài khoản không tồn tại!'], 401);
            }

            if (!Hash::check($request->password, $account->password)) {
                return response()->json(['message' => 'Sai mật khẩu!'], 401);
            }

            $token = $account->createToken('auth_token')->plainTextToken;

            // 🟢 ĐÃ SỬA: Không còn roleNumber (1, 2, 3). 
            // Trả về trực tiếp mã Role (C1, C2, C3) để Frontend xử lý điều hướng.
            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'       => $account->employee_id, 
                    'username' => $account->username,
                    'role'     => strtoupper($account->role), // Trả về 'C1', 'C2', 'C3'...
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $account = $request->user();

            $employee = Employee::with([
                'branch',
                'payGrade',
                'jobHistories.branch',
                'jobHistories.position'
            ])->find($account->employee_id);

            if (!$employee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy hồ sơ nhân viên!'
                ], 404);
            }

            // Dịch tên hiển thị (Chỉ dùng để hiển thị trên UI)
            $roleNameVN = 'Nhân viên';
            $empRoleCode = strtoupper($employee->role);
            
            if ($empRoleCode === 'C3') $roleNameVN = 'Giám đốc / Chuyên gia';
            elseif ($empRoleCode === 'C2') $roleNameVN = 'Quản lý cơ sở';
            elseif ($empRoleCode === 'C1') $roleNameVN = 'Thu ngân / Kế toán';
            elseif ($empRoleCode === 'C0') $roleNameVN = 'Nhân viên Bàn/Bếp';

            $dbType = strtolower($employee->type ?? 'part');
            $employmentType = str_contains($dbType, 'full') ? 'full' : 'part';

            $jobHistoryFormatted = $employee->jobHistories->map(function($history) {
                return [
                    'id'            => $history->id,
                    'start_date'    => $history->start_date,
                    'end_date'      => $history->end_date,
                    'branch_name'   => $history->branch ? $history->branch->name : null,
                    'position_name' => $history->position ? $history->position->name : null,
                ];
            })->toArray();

            // 🟢 ĐÃ CẬP NHẬT CẤU TRÚC: Trả về mã Role thay vì tên tiếng Việt vào trường 'role'
            // để tránh lỗi điều hướng khi FE gọi API /me để cập nhật lại thông tin user.
            $responseData = [
                'id'            => $employee->id,
                'full_name'     => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'status'        => strtolower($employee->status),
                'role'          => $empRoleCode, // 'C1', 'C2', 'C3'
                'role_display'  => $roleNameVN, // Dùng trường này nếu FE muốn hiện chữ "Quản lý cơ sở"
                'branch_id'     => $employee->branch_id,
                'branch'        => $employee->branch ? [
                    'id'      => $employee->branch->id,
                    'name'    => $employee->branch->name,
                    'address' => $employee->branch->address,
                ] : null,
                'type'          => $employmentType,
                'base_salary'   => $employee->payGrade ? (float)$employee->payGrade->base_salary : 0, 
                'phonenumber'   => $employee->phonenumber,
                'email'         => $employee->email,
                'avatar_url'    => $employee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name),
                'job_history'   => $jobHistoryFormatted
            ];

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'employee' => $responseData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }
}