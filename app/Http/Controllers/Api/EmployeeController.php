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
        $employees = DB::table('employees')->get();

        // Map lại dữ liệu để trả về cấu trúc JSON lồng nhau giống Pydantic Schema
        $formatted = $employees->map(function ($e) {
            
            // Xử lý mảng fixed_off_days (vì lưu trong DB dạng chuỗi JSON)
            $fixedOffDays = [];
            if ($e->fixed_off_days) {
                $fixedOffDays = json_decode($e->fixed_off_days, true) ?? [];
            }

            return [
                'id' => $e->id,
                'personalInfo' => [
                    'fullName' => $e->full_name,
                    'phone' => $e->phone,
                    'dob' => $e->dob,
                    'gender' => $e->gender,
                    'avatarUrl' => $e->avatar_url ?? 'bg-indigo-500' // Giá trị mặc định nếu rỗng
                ],
                'employment' => [
                    // Trong CSDL Seeder đặt tên cột là department_id, ở main.py là department. 
                    // Ta kiểm tra cả 2 để lấy đúng tên Tổ.
                    'department' => $e->department_id ?? $e->department ?? 'Chưa phân tổ',
                    'role' => $e->role,
                    'level' => $e->level,
                    'joinDate' => $e->join_date,
                    'status' => $e->status ?? 'ACTIVE',
                    'fixedOffDays' => $fixedOffDays
                ],
                'systemConfigs' => [
                    'isLeader' => (bool) $e->is_leader,
                    'faceIdRegistered' => (bool) $e->face_id_registered,
                    'timekeeperId' => $e->timekeeper_id
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
        // Kiểm tra xem có nhân viên này không
        $employee = DB::table('employees')->where('id', $id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên'], 404);
        }

        // Tạo mảng dữ liệu cập nhật linh hoạt (Chỉ cập nhật trường nào được gửi lên)
        $updateData = [];

        if ($request->has('fullName')) $updateData['full_name'] = $request->fullName;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('dob')) $updateData['dob'] = $request->dob;
        if ($request->has('role')) $updateData['role'] = $request->role;
        if ($request->has('level')) $updateData['level'] = $request->level;
        
        // Map payload 'department' vào cột CSDL
        if ($request->has('department')) {
            $updateData['department_id'] = $request->department;
            $updateData['department'] = $request->department; // Lưu cả 2 phòng trường hợp CSDL của bạn có tên cột khác
        }
        
        if ($request->has('timekeeperId')) $updateData['timekeeper_id'] = $request->timekeeperId;
        
        if ($request->has('fixedOffDays')) {
            $updateData['fixed_off_days'] = json_encode($request->fixedOffDays);
        }

        // Tiến hành cập nhật
        if (!empty($updateData)) {
            DB::table('employees')->where('id', $id)->update($updateData);
        }

        return response()->json(['success' => true, 'message' => 'Cập nhật thông tin thành công'], 200);
    }

    /**
     * Tương đương: @app.put("/api/employees/{emp_id}/department")
     */
    public function updateDepartment(Request $request, $id)
    {
        $updated = DB::table('employees')->where('id', $id)->update([
            'department_id' => $request->department,
            'department' => $request->department
        ]);

        if ($updated) {
            return response()->json(['success' => true, 'message' => 'Cập nhật tổ thành công'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên'], 404);
    }
}