<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    // --- Thông tin cơ bản ---
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    // 🟢 THÊM HÀM NÀY VÀO ĐỂ KHÔNG BỊ LỖI LỊCH SỬ CÔNG TÁC
    public function jobHistories()
    {
        return $this->hasMany(JobHistory::class, 'employee_id', 'id');
    }

    public function payGrade()
    {
        return $this->belongsTo(PayGrade::class, 'pay_grade_id');
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'employee_id');
    }

    public function laborContracts()
    {
        return $this->hasMany(LaborContract::class, 'employee_id');
    }

    // --- Chấm công & Phân ca ---
    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class, 'employee_id');
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class, 'employee_id');
    }

    // --- Lương & KPI ---
    public function monthlyRevenueKpis()
    {
        return $this->hasMany(MonthlyRevenueKpi::class, 'employee_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    public function payrollChanges()
    {
        return $this->hasMany(PayrollChange::class, 'employee_id');
    }
    
    // Hàm Helper: Lấy hợp đồng đang active
    public function getActiveContractAttribute()
    {
        return $this->laborContracts()
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })->first();
    }
}