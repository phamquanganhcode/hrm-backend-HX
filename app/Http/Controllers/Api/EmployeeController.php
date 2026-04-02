<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Tương đương: @app.get("/api/employees")
     */
    public function index()
    {
        // Lấy nhân viên và nối (Left Join) với bảng branches để lấy tên chi nhánh (phòng ban)
        $employees = DB::table('employees')
            ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id')
            ->select('employees.*', 'branches.name as branch_name')
            ->whereNull('employees.deleted_at') // Bỏ qua những người đã bị xóa (softDeletes)
            ->get();

        $formatted = $employees->map(function ($e) {
            // Trả về cấu trúc JSON y hệt Frontend đang kỳ vọng
            return [
                "id" => $e->employee_code ?? (string)$e->id, // Lấy mã NV làm ID cho Frontend
                "personalInfo" => [
                    "fullName" => $e->full_name ?? "",
                    "phone" => $e->phonenumber ?? "",
                    "dob" => "", // Không có trong DB, trả về rỗng
                    "gender" => "", // Không có trong DB, trả về rỗng
                    "avatarUrl" => $e->avatar_url ?? ""
                ],
                "employment" => [
                    "department" => $e->branch_name ?? "Chưa phân chi nhánh",
                    "role" => $e->role ?? "",
                    "level" => $e->type ?? "", // Ánh xạ cột type sang level
                    "joinDate" => $e->hire_date ?? "",
                    "status" => $e->status ?? "Active",
                    "fixedOffDays" => [] // Không có trong DB, trả về mảng rỗng để tránh lỗi JS
                ],
                "systemConfigs" => [
                    "isLeader" => false, 
                    "faceIdRegistered" => !empty($e->fingerprint_id),
                    "timekeeperId" => $e->fingerprint_id ?? null
                ]
            ];
        });

        return response()->json($formatted, 200);
    }

    /**
     * Tương đương: @app.put("/api/employees/{emp_id}")
     */
    public function update(Request $request, $id)
    {
        // Frontend gửi lên ID là employee_code (vd: EMP_01)
        $emp = DB::table('employees')->where('employee_code', $id)->first();
        
        if (!$emp) {
            return response()->json(["success" => false, "message" => "Không tìm thấy nhân viên"], 404);
        }

        $updateData = [];

        // Ánh xạ các trường cập nhật
        if ($request->has('fullName')) $updateData['full_name'] = $request->fullName;
        if ($request->has('phone')) $updateData['phonenumber'] = $request->phone;
        if ($request->has('role')) $updateData['role'] = $request->role;
        if ($request->has('level')) $updateData['type'] = $request->level;
        if ($request->has('timekeeperId')) $updateData['fingerprint_id'] = $request->timekeeperId;
        
        // Cập nhật department (branch_id)
        // Vì Frontend gửi lên String (tên tổ), ta phải tìm ID của branch đó
        if ($request->has('department')) {
            $branch = DB::table('branches')->where('name', $request->department)->first();
            if ($branch) {
                $updateData['branch_id'] = $branch->id;
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = now();
            DB::table('employees')->where('employee_code', $id)->update($updateData);
        }

        return response()->json(["success" => true, "message" => "Cập nhật thông tin thành công"], 200);
    }

    /**
     * Tương đương: @app.put("/api/employees/{emp_id}/department")
     */
    public function updateDepartment(Request $request, $id)
    {
        // Frontend gửi tên chi nhánh (department) lên, ta tìm id của nó
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