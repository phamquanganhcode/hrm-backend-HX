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
            // 1. Gọi Service (Service của bạn đang trả về mảng có 'token' và 'account')
            $data = $this->authService->login($request->username, $request->password);
            
            // Lấy thông tin tài khoản ra
            $account = $data['account'];

            // 2. KỸ THUẬT "PHIÊN DỊCH": Chuyển role chữ sang SỐ cho FE hiểu
            $roleNumber = 3; // Mặc định là nhân viên (3)
            $roleStr = is_object($account) ? $account->role : $account['role'];

            if ($roleStr === 'admin') {
                $roleNumber = 1;
            } elseif ($roleStr === 'manager') {
                $roleNumber = 2;
            }
            
            // 3. XÂY DỰNG LẠI DỮ LIỆU CHUẨN (Đổi 'account' thành 'user')
            $formattedData = [
                'token' => $data['token'],
                'user'  => [ // BẮT BUỘC PHẢI LÀ 'user'
                    'id'       => is_object($account) ? $account->id : $account['id'],
                    'username' => is_object($account) ? $account->username : $account['username'],
                    'role'     => $roleNumber, // Đã chuyển thành số
                ]
            ];
            
            // 4. Trả về cho FE
            return response()->json($formattedData, 200);
            
        } catch (Exception $e) {
            // Bắt lỗi từ Service và trả về
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function me(Request $request)
    {
        // 1. DÙNG EAGER LOADING "LẤY TRỌN GÓI"
        $account = $request->user()->load([
            'employee.branch', 
            'employee.jobHistories.branch', 
            'employee.jobHistories.position'
        ]);
        
        $employee = $account->employee;

        if (!$employee) {
            return $this->errorResponse('Không tìm thấy hồ sơ nhân viên này!', 404);
        }

        // 2. Phiên dịch Role hiển thị cho Giao diện (Tiếng Việt)
        $roleNameVN = 'Nhân viên';
        if ($account->role === 'admin') {
            $roleNameVN = 'Quản trị viên hệ thống';
        } elseif ($account->role === 'manager') {
            $roleNameVN = 'Quản lý cơ sở';
        } elseif ($account->role === 'employee_chef') {
            $roleNameVN = 'Nhân viên Bếp';
        } elseif ($account->role === 'employee_staff') {
            $roleNameVN = 'Nhân viên chạy bàn';
        }

        // 👉 3. LOGIC XỬ LÝ PHÂN LOẠI FULL-TIME / PART-TIME CHO ĐĂNG KÝ CA
        // Lấy giá trị trong DB, chuẩn hóa thành chữ thường để so sánh cho an toàn
        $dbType = strtolower($employee->type ?? 'part');
        // Nếu trong DB có chứa chữ 'full' (vd: full-time, full, Full-Time) thì gán là 'full', ngược lại là 'part'
        $employmentType = str_contains($dbType, 'full') ? 'full' : 'part';

        // 4. XỬ LÝ MẢNG LỊCH SỬ CÔNG TÁC
        $formattedJobHistory = $employee->jobHistories->map(function($history) {
            return [
                'id'            => $history->id,
                'start_date'    => $history->start_date,
                'end_date'      => $history->end_date, 
                'branch_name'   => $history->branch ? $history->branch->name : 'Chi nhánh chưa xác định',
                'position_name' => $history->position ? $history->position->name : 'Vị trí chưa xác định',
            ];
        })->toArray();

        // 5. Xây dựng Object Dữ liệu Tổng
        $formattedEmployee = [
            'full_name'     => $employee->full_name ?? 'Chưa cập nhật tên',
            'employee_code' => $employee->employee_code ?? 'Chưa có mã',
            'status'        => $account->is_active ? 'active' : 'inactive',
            'role'          => $roleNameVN,
            'branch_name'   => $employee->branch ? $employee->branch->name : 'Chưa cập nhật cơ sở', 
            'type'          => $employmentType, // Lưu đúng chữ 'full' hoặc 'part'
            'base_salary'   => $employee->base_salary ?? 35000, 
            'phonenumber'   => $employee->phonenumber ?? 'Chưa cập nhật SĐT', 
            'email'         => $employee->email ?? 'Chưa cập nhật Email',
            'avatar_url'    => $employee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=random',
            'job_history'   => $formattedJobHistory 
        ];

        // 6. Trả về cho FE
        return $this->successResponse(
            [
                'role'     => $employmentType, // 👉 FE SẼ LẤY BIẾN NÀY ĐỂ QUYẾT ĐỊNH KHÓA/MỞ NÚT ĐĂNG KÝ
                'employee' => $formattedEmployee
            ], 
            'Lấy thông tin hồ sơ thành công'
        );
    }
}