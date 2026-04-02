<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /**
     * Lấy danh sách Ca làm việc
     * Tương đương: @app.get("/api/shifts")
     */
    public function index()
    {
        // Đọc dữ liệu từ bảng shift_definitions, bỏ qua các ca đã xóa mềm
        $shifts = DB::table('shift_definitions')
            ->whereNull('deleted_at')
            ->get();

        $formatted = $shifts->map(function ($s) {
            // Định dạng lại giờ (cắt bỏ phần giây: "08:00:00" -> "08:00")
            // để thẻ <input type="time"> trên React hiển thị được.
            $start = substr($s->start_time, 0, 5);
            $end = substr($s->end_time, 0, 5);

            // Chuyển đổi logic DB (break_start, break_end) sang mảng periods của Frontend
            $periods = [];
            if ($s->break_start && $s->break_end) {
                // Đây là Ca Gãy (Có giờ nghỉ ở giữa)
                $bStart = substr($s->break_start, 0, 5);
                $bEnd = substr($s->break_end, 0, 5);
                $periods = [
                    ['startTime' => $start, 'endTime' => $bStart], // Lần 1
                    ['startTime' => $bEnd, 'endTime' => $end],     // Lần 2
                ];
            } else {
                // Ca thường (Làm một mạch)
                $periods = [
                    ['startTime' => $start, 'endTime' => $end],
                ];
            }

            return [
                'id' => (string) $s->id, // Ép kiểu chuỗi cho React
                'name' => $s->name,
                'periods' => $periods
            ];
        });

        return response()->json(['shiftCategories' => $formatted], 200);
    }

    /**
     * Lưu danh sách cấu hình ca (Thêm mới, Cập nhật, Xóa)
     * Tương đương: @app.post("/api/shifts")
     */
    public function store(Request $request)
    {
        $shiftCategories = $request->input('shiftCategories', []);
        $incomingIds = []; // Mảng chứa các ID hợp lệ được gửi lên

        DB::beginTransaction();
        try {
            foreach ($shiftCategories as $cat) {
                // 1. Phân tích mảng periods từ Frontend sang các cột trong DB
                $periods = $cat['periods'] ?? [];
                $startTime = $periods[0]['startTime'] ?? '00:00';
                $endTime = $periods[0]['endTime'] ?? '00:00';
                $breakStart = null;
                $breakEnd = null;

                // Nếu periods có 2 phần tử -> Đích thị là ca gãy
                if (count($periods) > 1) {
                    $breakStart = $periods[0]['endTime'] ?? null; // Kết thúc ca sáng
                    $breakEnd = $periods[1]['startTime'] ?? null; // Bắt đầu ca chiều
                    $endTime = $periods[1]['endTime'] ?? '00:00'; // Kết thúc ca chiều
                }

                // Xử lý ca qua đêm (Giờ kết thúc nhỏ hơn giờ bắt đầu)
                $isOvernight = ($endTime < $startTime) ? 1 : 0;

                // 2. Xử lý ID: React gửi lên ID số (Đã có trong DB) hay ID chữ (CA_123 - Vừa tạo mới)
                if (is_numeric($cat['id'])) {
                    // LÀ CA ĐÃ TỒN TẠI -> CẬP NHẬT
                    DB::table('shift_definitions')->where('id', $cat['id'])->update([
                        'name' => $cat['name'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd,
                        'is_overnight' => $isOvernight,
                        'updated_at' => now(),
                        'deleted_at' => null // Nếu ca này từng bị xóa mềm thì khôi phục lại
                    ]);
                    $incomingIds[] = $cat['id'];
                } else {
                    // LÀ CA THÊM MỚI TỪ GIAO DIỆN -> THÊM VÀO DB ĐỂ LẤY ID SỐ
                    $newId = DB::table('shift_definitions')->insertGetId([
                        'name' => $cat['name'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd,
                        'is_overnight' => $isOvernight,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $incomingIds[] = $newId;
                }
            }

            // 3. XỬ LÝ XÓA: 
            // Nếu trong DB có những ID không xuất hiện trong mảng Frontend gửi lên -> Người dùng đã bấm nút xóa ca đó
            // Ta dùng SoftDeletes thay vì Delete cứng để không làm hỏng bảng Xếp lịch và Chấm công
            if (!empty($incomingIds)) {
                DB::table('shift_definitions')
                    ->whereNotIn('id', $incomingIds)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => now()]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lưu ca làm việc thành công'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi lưu ca: ' . $e->getMessage()], 500);
        }
    }
}