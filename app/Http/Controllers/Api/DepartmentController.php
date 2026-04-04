<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Lấy danh sách Tổ / Chi nhánh
     * Tương đương: @app.get("/api/departments")
     */
    public function index()
    {
        $user = auth()->user(); 
        $query = DB::table('employees')->select('department as name')->whereNotNull('department')->distinct();

        // Lọc theo chi nhánh của Quản lý
        if ($user && in_array(strtoupper($user->role), ['C1', 'C2', '1', '2'])) {
            $manager = DB::table('employees')->where('id', $user->employee_id)->first();
            if ($manager) {
                $query->where('branch_id', $manager->branch_id);
            }
        }

        // Format cho Frontend
        $departments = $query->get()->map(function($item) {
            return ['id' => $item->name, 'name' => $item->name];
        });

        return response()->json($departments, 200);
    }

    /**
     * Thêm Tổ / Chi nhánh mới
     * Tương đương: @app.post("/api/departments")
     */
    public function store(Request $request)
    {
        $name = $request->input('name');

        if (empty($name)) {
            return response()->json(["success" => false, "message" => "Tên tổ không được để trống"], 400);
        }

        // Kiểm tra xem tên chi nhánh đã tồn tại chưa
        $exists = DB::table('branches')->where('name', $name)->whereNull('deleted_at')->exists();
        if ($exists) {
            return response()->json(["success" => false, "message" => "Tên tổ/chi nhánh này đã tồn tại"], 400);
        }

        // Lưu ý: Frontend React có thể gửi kèm 'id' (dạng DEPT_123), 
        // nhưng ta bỏ qua và để Database tự động tăng id (auto-increment)
        DB::table('branches')->insert([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json(["success" => true, "message" => "Thêm tổ thành công"], 200);
    }

    /**
     * Xóa Tổ / Chi nhánh
     * Tương đương: @app.delete("/api/departments/{dept_id}")
     */
    public function destroy($id)
    {
        // Bọc trong Try-Catch để đề phòng lỗi Khóa ngoại (Ví dụ: Chi nhánh đang có nhân viên)
        try {
            // Kiểm tra xem chi nhánh có tồn tại không
            $branch = DB::table('branches')->where('id', $id)->first();
            if (!$branch) {
                return response()->json(["success" => false, "message" => "Không tìm thấy tổ/chi nhánh"], 404);
            }

            // Thực hiện xóa (Hoặc xóa mềm nếu bảng có softDeletes)
            // Tạm thời dùng xóa cứng để demo
            DB::table('branches')->where('id', $id)->delete();
            
            return response()->json(["success" => true, "message" => "Xóa tổ thành công"], 200);

        } catch (\Exception $e) {
            // Nếu MySQL báo lỗi khóa ngoại Constraint Fails
            return response()->json([
                "success" => false, 
                "message" => "Không thể xóa: Tổ này đang có nhân viên làm việc!"
            ], 500);
        }
    }
}