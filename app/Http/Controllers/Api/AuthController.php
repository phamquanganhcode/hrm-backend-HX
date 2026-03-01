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
        // 1. DÙNG EAGER LOADING "LẤY TRỌN GÓI" (Tương đương lệnh JOIN của bạn)
        // Lấy Nhân viên -> Lấy Chi nhánh hiện tại
        // Đồng thời lấy luôn mảng Lịch sử công tác -> Kèm theo Chi nhánh và Vị trí của từng lịch sử
        $account = $request->user()->load([
            'employee.branch', 
            'employee.jobHistories.branch', 
            'employee.jobHistories.position'
        ]);
        
        $employee = $account->employee;

        if (!$employee) {
            return $this->errorResponse('Không tìm thấy hồ sơ nhân viên này!', 404);
        }

        // 2. Phiên dịch Role
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

        // 3. XỬ LÝ MẢNG LỊCH SỬ CÔNG TÁC (Map dữ liệu chuẩn JSON FE yêu cầu)
        $formattedJobHistory = $employee->jobHistories->map(function($history) {
            return [
                'id'            => $history->id,
                'start_date'    => $history->start_date,
                'end_date'      => $history->end_date, // Sẽ tự động là null nếu rỗng
                'branch_name'   => $history->branch ? $history->branch->name : 'Chi nhánh chưa xác định',
                'position_name' => $history->position ? $history->position->name : 'Vị trí chưa xác định',
            ];
        })->toArray();

        // 4. Xây dựng Object Dữ liệu Tổng
        $formattedEmployee = [
            'full_name'     => $employee->full_name ?? 'Chưa cập nhật tên',
            'employee_code' => $employee->employee_code ?? 'Chưa có mã',
            'status'        => $account->is_active ? 'active' : 'inactive',
            'role'          => $roleNameVN,
            'branch_name'   => $employee->branch ? $employee->branch->name : 'Chưa cập nhật cơ sở', 
            'type'          => $employee->type ?? 'part', 
            'base_salary'   => $employee->base_salary ?? 35000, 
            'phonenumber'   => $employee->phonenumber ?? 'Chưa cập nhật SĐT', 
            'email'         => $employee->email ?? 'Chưa cập nhật Email',
            'avatar_url'    => $employee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=random',
            
            // CHÈN MẢNG LỊCH SỬ CÔNG TÁC VÀO ĐÂY
            'job_history'   => $formattedJobHistory 
        ];

        return $this->successResponse(
            ['employee' => $formattedEmployee], 
            'Lấy thông tin hồ sơ thành công'
        );
    }
}