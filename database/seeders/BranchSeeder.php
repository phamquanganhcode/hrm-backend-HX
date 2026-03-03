<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create(['name' => 'Chi nhánh Thủy Lợi', 'address' => '175 Tây Sơn, Đống Đa']);
        Branch::create(['name' => 'Chi nhánh Cầu Giấy', 'address' => '123 Xuân Thủy, Cầu Giấy']);
        Branch::create(['name' => 'Chi nhánh Hoàng Mai', 'address' => '456 Thúy Lĩnh, Hoàng Mai']);
        // Tạo thêm 5 chi nhánh ảo
        // \App\Models\Branch::factory(5)->create();
    }
}