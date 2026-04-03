<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Lấy danh sách nhân viên (Tương đương: @app.get("/api/employees"))
     */
    public function index()
    {
        $user = auth()->user(); // Lấy Account đang đăng nhập

        // Khởi tạo query sử dụng Model Employee
        $query = \App\Models\Employee::with('branch');

        // Kiểm tra quyền: Chỉ Admin (C3) mới xem được hết
        if ($user && in_array($user->role, ['C1', 'C2'])) {
            // Lấy thông tin nhân viên của tài khoản này
            $manager = $user->employee; 
            
            if ($manager) {
                // Lọc nhân viên: Chỉ lấy những người thuộc cùng chi nhánh với Quản lý
                $query->where('branch_id', $manager->branch_id);
            }
        }

        $employees = $query->get();

        // Format dữ liệu trả về cho Frontend
        $formatted = $employees->map(function ($e) {
            return [
                "id" => $e->employee_code,
                "personalInfo" => [
                    "fullName" => $e->full_name,
                    "phone" => $e->phonenumber,
                    "avatarUrl" => $e->avatar_url
                ],
                "employment" => [
                    "department" => $e->branch->name ?? "Chưa phân chi nhánh",
                    "role" => $e->role,
                    "status" => $e->status,
                    "fixedOffDays" => []
                ],
                "systemConfigs" => [
                    "isLeader" => in_array($e->role, ['C2', 'C3']),
                    "faceIdRegistered" => !empty($e->fingerprint_id)
                ]
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Cập nhật thông tin nhân viên (Tương đương: @app.put("/api/employees/{emp_id}"))
     */
    public function update(Request $request, $id)
    {
        // 1. Tìm nhân viên theo mã (VD: EMP_01)
        $emp = DB::table('employees')->where('employee_code', $id)->whereNull('deleted_at')->first();
        
        if (!$emp) {
            return response()->json(["success" => false, "message" => "Không tìm thấy nhân viên"], 404);
        }

        // 2. KIỂM TRA QUYỀN TRƯỚC KHI SỬA
        $user = auth()->user();
        if ($user && in_array($user->role, ['C1', 'C2'])) {
            $currentEmp = DB::table('employees')->where('id', $user->employee_id)->first();
            // Nếu quản lý cố tình sửa nhân viên của chi nhánh khác -> Báo lỗi 403
            if ($currentEmp && $currentEmp->branch_id != $emp->branch_id) {
                return response()->json(["success" => false, "message" => "Bạn không có quyền sửa thông tin nhân sự của chi nhánh khác!"], 403);
            }
        }

        $updateData = [];

        // 3. Trích xuất dữ liệu từ request (Hỗ trợ cả object lồng nhau hoặc flat params)
        $fullName = $request->input('personalInfo.fullName', $request->fullName);
        if ($fullName) $updateData['full_name'] = $fullName;

        $phone = $request->input('personalInfo.phone', $request->phone);
        if ($phone) $updateData['phonenumber'] = $phone;

        $role = $request->input('employment.role', $request->role);
        if ($role) $updateData['role'] = $role;

        $level = $request->input('employment.level', $request->level);
        if ($level) $updateData['type'] = $level;

        $timekeeperId = $request->input('systemConfigs.timekeeperId', $request->timekeeperId);
        if ($timekeeperId !== null) $updateData['fingerprint_id'] = $timekeeperId;
        
        // 4. Xử lý logic chuyển đổi tên phòng ban (String) thành branch_id (Integer)
        $department = $request->input('employment.department', $request->department);
        if ($department) {
            $branch = DB::table('branches')->where('name', $department)->first();
            if ($branch) {
                $updateData['branch_id'] = $branch->id;
            }
        }

        // 5. Cập nhật vào Database
        if (!empty($updateData)) {
            $updateData['updated_at'] = now();
            DB::table('employees')->where('id', $emp->id)->update($updateData);
        }

        return response()->json(["success" => true, "message" => "Cập nhật thông tin thành công"], 200);
    }

    /**
     * Chuyển đổi nhanh chi nhánh (Tương đương: @app.put("/api/employees/{emp_id}/department"))
     */
    public function updateDepartment(Request $request, $id)
    {
        // Kiểm tra quyền (Admin mới được chuyển công tác giữa các chi nhánh)
        $user = auth()->user();
        if ($user && !in_array($user->role, ['C3'])) {
            return response()->json(["success" => false, "message" => "Chỉ Giám đốc/Admin mới có quyền điều chuyển chi nhánh!"], 403);
        }

        $branch = DB::table('branches')->where('name', $request->department)->first();
        
        if ($branch) {
            DB::table('employees')->where('employee_code', $id)->update([
                'branch_id' => $branch->id,
                'updated_at' => now()
            ]);
            return response()->json(["success" => true, "message" => "Cập nhật chi nhánh thành công"], 200);
        }
        
        return response()->json(["success" => false, "message" => "Không tìm thấy chi nhánh/tổ này"], 400);
    }
}