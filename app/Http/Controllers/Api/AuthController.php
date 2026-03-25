<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Exception;
use Illuminate\Support\Facades\Auth;

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
        // Validate đầu vào
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // 1. TÌM TÀI KHOẢN TRONG DATABASE (Bỏ qua Auth::attempt)
            $account = \App\Models\Account::where('username', $request->username)->first();

            // Bắt lỗi: Không có tài khoản
            if (!$account) {
                return $this->errorResponse('Tài khoản không tồn tại!', 401);
            }

            // 2. SO SÁNH MẬT KHẨU (Dùng Hash::check)
            if (!\Illuminate\Support\Facades\Hash::check($request->password, $account->password)) {
                return $this->errorResponse('Sai mật khẩu!', 401);
            }

            // 3. TẠO TOKEN ĐĂNG NHẬP
            $token = $account->createToken('auth_token')->plainTextToken;

            // 4. PHIÊN DỊCH ROLE (Chuyển chữ sang số cho Frontend)
            $roleNumber = 3; // Mặc định là nhân viên (3)
            $roleStr = strtolower($account->role);
            if ($roleStr === 'admin') {
                $roleNumber = 1;
            } elseif ($roleStr === 'manager') {
                $roleNumber = 2;
            }
            
            // 5. TRẢ DỮ LIỆU VỀ
            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'       => $account->id,
                    'username' => $account->username,
                    'role'     => $roleNumber,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Lỗi máy chủ nội bộ: ' . $e->getMessage(), 500);
        }
    }

public function me(Request $request)
    {
        try {
            // 1. Lấy Account đang đăng nhập
            $account = $request->user();

            // 2. TÌM NHÂN VIÊN & EAGER LOADING TOÀN BỘ QUAN HỆ (Quan trọng nhất)
            $employee = \App\Models\Employee::with([
                'branch',                     // Nạp Chi nhánh hiện tại
                'payGrade',                   // Nạp Bậc lương
                'jobHistories.branch',        // Nạp Chi nhánh trong lịch sử
                'jobHistories.position'       // Nạp Chức vụ trong lịch sử
            ])->find($account->employee_id);

            if (!$employee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy hồ sơ nhân viên này!'
                ], 404);
            }

            // 3. XỬ LÝ DỮ LIỆU ĐỂ KHỚP VỚI FRONTEND YÊU CẦU

            // Dịch Role (C0, C1...) sang Tiếng Việt
            $roleNameVN = 'Nhân viên';
            $empRole = $employee->role;
            if ($empRole === 'C3') $roleNameVN = 'Giám đốc / Chuyên gia';
            elseif ($empRole === 'C2') $roleNameVN = 'Quản lý cơ sở';
            elseif ($empRole === 'C1') $roleNameVN = 'Thu ngân / Kế toán';
            elseif ($empRole === 'C0') $roleNameVN = 'Nhân viên Bàn/Bếp';

            // Xử lý Type (full/part)
            $dbType = strtolower($employee->type ?? 'part');
            $employmentType = str_contains($dbType, 'full') ? 'full' : 'part';

            // Định dạng mảng Lịch sử công tác (Lấy đúng branch_name và position_name)
            $jobHistoryFormatted = $employee->jobHistories->map(function($history) {
                return [
                    'id'            => $history->id,
                    'start_date'    => $history->start_date,
                    'end_date'      => $history->end_date,
                    'branch_name'   => $history->branch ? $history->branch->name : null,
                    'position_name' => $history->position ? $history->position->name : null,
                ];
            })->toArray();

            // 4. ĐÓNG GÓI JSON ĐÚNG 100% CẤU TRÚC YÊU CẦU
            $responseData = [
                'id'            => $employee->id,
                'full_name'     => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'status'        => strtolower($employee->status), // 'active'
                'role'          => $roleNameVN,
                'branch_id'     => $employee->branch_id,
                
                // Object branch lồng bên trong
                'branch'        => $employee->branch ? [
                    'id'      => $employee->branch->id,
                    'name'    => $employee->branch->name,
                    'address' => $employee->branch->address,
                ] : null,

                'type'          => $employmentType,
                // Ép kiểu lương về dạng số thực/số nguyên
                'base_salary'   => $employee->payGrade ? (float)$employee->payGrade->base_salary : 0, 
                
                'phonenumber'   => $employee->phonenumber,
                'email'         => $employee->email,
                'avatar_url'    => $employee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=random',
                
                // Array lịch sử công tác
                'job_history'   => $jobHistoryFormatted
            ];

            // 5. TRẢ VỀ FRONTEND VỚI FORMAT { status: 'success', data: { employee: {...} } }
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'employee' => $responseData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi máy chủ nội bộ: ' . $e->getMessage()
            ], 500);
        }
    }
}