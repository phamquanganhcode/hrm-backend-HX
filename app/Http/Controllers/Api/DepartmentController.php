<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentController extends Controller
{
    /**
     * Tương đương: @app.get("/api/departments")
     */
    public function index()
    {
        $depts = DB::table('departments')->select('id', 'name')->get();

        // Logic giống main.py: Nếu DB chưa có, tự động tạo danh sách mặc định
        if ($depts->isEmpty()) {
            $defaultDepts = ["Quản lý", "Bếp", "Bàn", "Nướng", "Bia", "Tạp vụ", "Bảo vệ"];
            $insertData = [];
            foreach ($defaultDepts as $d) {
                // Tạo ID bằng cách viết hoa và thay dấu cách bằng gạch dưới
                $id = "DEPT_" . strtoupper(str_replace(' ', '_', $d));
                $insertData[] = [
                    'id' => $id,
                    'name' => $d,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            DB::table('departments')->insert($insertData);
            $depts = DB::table('departments')->select('id', 'name')->get();
        }

        return response()->json($depts, 200);
    }

    /**
     * Tương đương: @app.post("/api/departments")
     */
    public function store(Request $request)
    {
        // Validation cơ bản (Tương đương Pydantic Schema)
        $request->validate([
            'id' => 'required|string',
            'name' => 'required|string'
        ]);

        DB::table('departments')->insert([
            'id' => $request->id,
            'name' => $request->name,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Thêm tổ thành công'
        ], 200);
    }

    /**
     * Tương đương: @app.delete("/api/departments/{dept_id}")
     */
    public function destroy($id)
    {
        DB::table('departments')->where('id', $id)->delete();
        return response()->json(['success' => true], 200);
    }
}