<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class, // Danh mục phải có trước
            EmployeeSeeder::class,   // Có danh mục rồi mới tạo Nhân viên được
            OperationSeeder::class,  // Có nhân viên, có ca làm việc rồi mới xếp ca được
        ]);
    }
}