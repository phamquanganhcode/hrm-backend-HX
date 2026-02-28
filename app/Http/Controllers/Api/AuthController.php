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
            // 1. Gọi Service xử lý logic
            $result = $this->authService->login($request->username, $request->password);
            
            // Lấy thông tin tài khoản từ kết quả trả về của Service
            // (Tùy vào cách bạn viết trong Service, nó có thể là $result['account'] hoặc $result['user'])
            $account = isset($result['account']) ? $result['account'] : auth()->user();
            
            // 2. ÉP KIỂU ROLE: Từ chữ sang SỐ 1, 2, 3 cho Frontend hiểu
            $roleNumber = 3; // Mặc định là nhân viên
            if ($account->role === 'admin') {
                $roleNumber = 1;
            } elseif ($account->role === 'manager') {
                $roleNumber = 2;
            }

            // 3. XÂY DỰNG MẢNG CHUẨN XÁC 100% THEO YÊU CẦU CỦA FE
            // FE cần response.token và response.user.role
            $formattedData = [
                'token' => $result['token'] ?? '', 
                'user'  => [             // Bắt buộc key này phải tên là 'user', không được để 'account'
                    'id'       => $account->id,
                    'username' => $account->username,
                    'role'     => $roleNumber, 
                    'name'     => $account->employee->full_name ?? 'Chưa cập nhật tên',
                ]
            ];
            
            // Trả về dữ liệu đã được Format
            return $this->successResponse($formattedData, 'Đăng nhập thành công!');
            
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    public function me(Request $request)
    {
        // 1. Lấy account đang đăng nhập kèm theo thông tin nhân viên
        $account = $request->user()->load('employee');
        $employee = $account->employee;

        // Nếu vì lý do nào đó tài khoản này chưa được liên kết với nhân viên
        if (!$employee) {
            return $this->errorResponse('Không tìm thấy hồ sơ nhân viên này!', 404);
        }

        // 2. "Phiên dịch" Role sang tiếng Việt cho hiển thị đẹp mắt
        $roleNameVN = 'Nhân viên';
        if ($account->role === 'admin') {
            $roleNameVN = 'Quản trị viên hệ thống';
        } elseif ($account->role === 'manager') {
            $roleNameVN = 'Quản lý cơ sở';
        } elseif ($account->role === 'employee_chef') {
            $roleNameVN = 'Nhân viên Bếp';
        } elseif ($account->role === 'employee_staff') {
            $roleNameVN = 'Nhân viên chạy bàn'; // Khớp với yêu cầu của bạn
        }

        // 3. Xây dựng Object Dữ liệu chuẩn xác 100% theo JSON mẫu
        $formattedEmployee = [
            'full_name'     => $employee->full_name ?? 'Chưa cập nhật tên',
            'employee_code' => $employee->employee_code ?? 'Chưa có mã',
            'status'        => $account->is_active ? 'active' : 'inactive',
            'role'          => $roleNameVN,
            
            // LƯU Ý: Nếu bạn có bảng Branches, hãy thay bằng $employee->branch->name
            // Tạm thời tôi để mock data khớp với yêu cầu
            'branch_name'   => 'Chi nhánh Hải Xồm - Thủy Lợi', 
            
            // Tương tự, nếu có cột type và base_salary trong DB thì gọi ra, không thì để cứng
            'type'          => $employee->type ?? 'part', 
            'base_salary'   => $employee->base_salary ?? 35000, 
            
            // Hãy check lại tên cột trong DB của bạn là phone hay phonenumber nhé
            'phonenumber'   => $employee->phone ?? 'Chưa cập nhật SĐT', 
            'email'         => $employee->email ?? 'Chưa cập nhật Email',
            
            // Dùng UI-Avatars để tự động tạo ảnh đại diện từ chữ cái đầu của tên nếu chưa có ảnh
            'avatar_url'    => $employee->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name) . '&background=random'
        ];

        // 4. Trả về thông qua Trait successResponse
        // Hàm successResponse của bạn thường sẽ tự động bọc biến truyền vào bằng key "data"
        // Nên ta chỉ cần truyền mảng ['employee' => $formattedEmployee] là xong!
        return $this->successResponse(
            ['employee' => $formattedEmployee], 
            'Lấy thông tin hồ sơ thành công'
        );
    }
}