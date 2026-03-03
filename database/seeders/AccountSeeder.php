<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Employee;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy 4 nhân viên gốc
        $admin = Employee::where('employee_code', 'ADMIN01')->first();
        $manager = Employee::where('employee_code', 'NV001')->first();
        $chef = Employee::where('employee_code', 'NV002')->first();
        $staff = Employee::where('employee_code', 'NV003')->first();

        // Mật khẩu chung
        $password = Hash::make('123');

        // Tạo đúng 4 tài khoản yêu cầu
        Account::create(['employee_id' => $admin->id, 'username' => 'admin', 'password' => $password, 'role' => 'admin']);
        Account::create(['employee_id' => $manager->id, 'username' => 'manager', 'password' => $password, 'role' => 'manager']);
        Account::create(['employee_id' => $chef->id, 'username' => 'nhanvienbep', 'password' => $password, 'role' => 'employee_chef']);
        Account::create(['employee_id' => $staff->id, 'username' => 'nhanvienban', 'password' => $password, 'role' => 'employee_staff']);
    }
}