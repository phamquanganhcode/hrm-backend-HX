<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Employee;

class AuthController extends Controller
{
    protected $authService;

    // Inject AuthService vào Controller
    public function __construct(AuthService $authService = null)
    {
        $this->authService = $authService;
    }

    // ==========================================
    // 1.1. Lấy Token Đăng nhập
    // ==========================================
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $account = \App\Models\Account::where('username', $request->username)->first();

            if (!$account) {
                return response()->json(['message' => 'Tài khoản không tồn tại!'], 401);
            }

            if (!\Illuminate\Support\Facades\Hash::check($request->password, $account->password)) {
                return response()->json(['message' => 'Sai mật khẩu!'], 401);
            }

            $token = $account->createToken('auth_token')->plainTextToken;

            // 🟢 LÕI CỦA VẤN ĐỀ Ở ĐÂY: DỊCH C3, C2, C1 SANG 3, 2, 1 CHO FE
            $roleNumber = 0; // Mặc định cho nhân viên thường
            if ($account->role === 'C3') {
                $roleNumber = 3; // Admin / Kế toán tổng
            } elseif ($account->role === 'C2') {
                $roleNumber = 2; // Quản lý cơ sở (Manager)
            } elseif ($account->role === 'C1') {
                $roleNumber = 1; // Kế toán chi nhánh (Accounting)
            }
            
            // TRẢ VỀ THEO ĐÚNG FORMAT MÀ FRONTEND ĐANG CHỜ
            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'       => $account->employee_id, 
                    'username' => $account->username,
                    'role'     => $roleNumber, // Đã được map chuẩn 1, 2, 3
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 1.2. Lấy thông tin chi tiết (Me)
    // ==========================================
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
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy hồ sơ!'], 404);
            }

            // Dịch Role code sang Tiếng Việt
            $roleNameVN = match($employee->role) {
                'C3' => 'Giám đốc / Chuyên gia',
                'C2' => 'Quản lý cơ sở',
                'C1' => 'Thu ngân / Kế toán',
                'C0' => 'Nhân viên Bàn/Bếp',
                default => 'Nhân viên'
            };

            // Format Type
            $dbType = strtolower($employee->type ?? 'part');
            $employmentType = str_contains($dbType, 'full') ? 'full' : 'part';

            // Format Lịch sử công tác
            $jobHistoryFormatted = $employee->jobHistories->map(function($history) {
                return [
                    'id'            => $history->id,
                    'start_date'    => $history->start_date,
                    'end_date'      => $history->end_date,
                    'branch_name'   => $history->branch ? $history->branch->name : null,
                    'position_name' => $history->position ? $history->position->name : null,
                ];
            })->toArray();

            $responseData = [
                'id'            => $employee->id,
                'full_name'     => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'status'        => strtolower($employee->status ?? 'active'),
                'role'          => $roleNameVN,
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
                'avatar_url'    => $employee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=random',
                'job_history'   => $jobHistoryFormatted
            ];

            return response()->json([
                'status' => 'success',
                'data'   => ['employee' => $responseData]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 1.3. Đăng xuất (Logout)
    // ==========================================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => 'Đăng xuất thành công', 'data' => null], 200);
    }

    // ==========================================
    // 1.4. Đổi mật khẩu (Change Password)
    // ==========================================
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:3|confirmed', 
        ]);

        $account = $request->user();

        if (!Hash::check($request->current_password, $account->password)) {
            return response()->json(['status' => 'error', 'message' => 'Mật khẩu cũ không chính xác!'], 400);
        }

        $account->password = Hash::make($request->new_password);
        $account->save();

        return response()->json(['status' => 'success', 'message' => 'Đổi mật khẩu thành công', 'data' => null], 200);
    }
}