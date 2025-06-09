<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EmployeeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'NIK',
        'last_education',
        'place_birth',
        'date_birth',
        'role',
        'branch',
        'contract_type',
        'bank',
        'bank_account_number',
        'bank_account_name',
        'position',
        'gender',
        'SP',
        'address',
        'shift_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'shift_count' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Tambahkan field yang ingin disembunyikan jika ada
    ];

    /**
     * TAMBAHAN: Bootstrap model events untuk handle soft delete
     */
    protected static function boot()
    {
        parent::boot();

        // Ketika employee di-soft delete (resign)
        static::deleting(function ($employee) {
            if ($employee->user && $employee->user->company) {
                $employee->user->company->decrementEmployeeCount();
            }

            // Soft delete user account juga
            if ($employee->user) {
                $employee->user->delete();
            }
        });
    }

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(UsersModel::class, 'user_id', 'id');
    }

    /**
     * Get company through user relationship
     */
    public function company()
    {
        return $this->hasOneThrough(
            CompanyModel::class,
            UsersModel::class,
            'id',         // Foreign key on users table
            'id',         // Foreign key on companies table  
            'user_id',    // Local key on employees table
            'company_id'  // Local key on users table
        );
    }

    // ===========================================
    // ACCESSOR & MUTATOR
    // ===========================================

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get formatted NIK
     */
    public function getFormattedNikAttribute()
    {
        if (!$this->NIK || strlen($this->NIK) < 6) {
            return $this->NIK;
        }

        // Format NIK dengan pemisah jika diperlukan
        return substr($this->NIK, 0, 2) . '.' .
            substr($this->NIK, 2, 2) . '.' .
            substr($this->NIK, 4);
    }

    /**
     * Set NIK dengan uppercase
     */
    public function setNikAttribute($value)
    {
        $this->attributes['NIK'] = $value ? strtoupper($value) : null;
    }

    /**
     * Set first name dengan proper case
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = $value ? ucwords(strtolower($value)) : null;
    }

    /**
     * Set last name dengan proper case
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = $value ? ucwords(strtolower($value)) : null;
    }

    // ===========================================
    // SCOPES
    // ===========================================

    /**
     * Scope untuk filter berdasarkan kontrak
     */
    public function scopeByContractType($query, $contractType)
    {
        return $query->where('contract_type', $contractType);
    }

    /**
     * Scope untuk filter berdasarkan branch
     */
    public function scopeByBranch($query, $branch)
    {
        return $query->where('branch', $branch);
    }

    /**
     * Scope untuk filter berdasarkan gender
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope untuk employee aktif (tidak soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope untuk search berdasarkan nama atau ID employee
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('NIK', 'like', "%{$search}%");
        });
    }

    // ===========================================
    // METHODS
    // ===========================================

    /**
     * Get age from birth date
     */
    public function getAge()
    {
        return $this->date_birth ? $this->date_birth->age : null;
    }

    /**
     * Check if employee is permanent
     */
    public function isPermanent()
    {
        return $this->contract_type === 'permanen';
    }

    /**
     * Check if employee is on probation
     */
    public function isOnProbation()
    {
        return $this->contract_type === 'percobaan';
    }

    /**
     * Check if employee is intern
     */
    public function isIntern()
    {
        return $this->contract_type === 'magang';
    }

    /**
     * Check if employee is contract
     */
    public function isContract()
    {
        return $this->contract_type === 'kontrak';
    }

    /**
     * Get education level order for sorting
     */
    public function getEducationLevelOrder()
    {
        $levels = [
            'SD' => 1,
            'SMP' => 2,
            'SMA/SMK' => 3,
            'D2' => 4,
            'D3' => 5,
            'D4' => 6,
            'S1' => 7,
            'S2' => 8,
        ];

        return $levels[$this->last_education] ?? 0;
    }

    /**
     * Get SP level as integer
     */
    public function getSpLevelInt()
    {
        return (int) $this->SP;
    }

    /**
     * Check if employee has warning (SP)
     */
    public function hasWarning()
    {
        return !empty($this->SP);
    }

    // FUNGSI UNTUK EMPLOYEE MANAGEMENT

    /**
     * Get employee number from user's company_username
     */
    public function getEmployeeNumberAttribute()
    {
        if (!$this->user || !$this->user->company_username) {
            return null;
        }

        // Extract number from company_username format: cmp<number><name><increment>
        $username = $this->user->company_username;
        if (preg_match('/^cmp(\d+)/', $username, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get status display (Active/Inactive based on soft delete)
     */
    public function getStatusDisplayAttribute()
    {
        return $this->deleted_at ? 'Inactive' : 'Active';
    }

    /**
     * Get contract type display
     */
    public function getContractTypeDisplayAttribute()
    {
        $types = [
            'permanen' => 'Permanent',
            'percobaan' => 'Probation',
            'magang' => 'Intern',
            'kontrak' => 'Contract'
        ];

        return $types[$this->contract_type] ?? $this->contract_type;
    }

    /**
     * Get gender display
     */
    public function getGenderDisplayAttribute()
    {
        return $this->gender === 'Laki-laki' ? 'Male' : 'Female';
    }

    /**
     * Scope for filtering by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->whereHas('user', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }

    /**
     * Scope for filtering by period (month/year)
     */
    public function scopeByPeriod($query, Carbon $date)
    {
        return $query->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month);
    }

    /**
     * Scope for new hires in specific period
     */
    public function scopeNewHiresInPeriod($query, Carbon $date)
    {
        return $query->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->whereNull('deleted_at');
    }

    /**
     * Get mobile number from user
     */
    public function getMobileNumberAttribute()
    {
        return $this->user ? $this->user->mobile_number : null;
    }

    /**
     * Get company username from user  
     */
    public function getCompanyUsernameAttribute()
    {
        return $this->user ? $this->user->company_username : null;
    }

    /**
     * Check if employee can be activated/deactivated
     */
    public function canToggleStatus()
    {
        return true; // Bisa disesuaikan dengan business logic
    }

    /**
     * Soft delete employee and update company count
     */
    public function resign()
    {
        $this->delete(); // Soft delete

        // Update company employee count sudah dihandle di boot() method

        return true;
    }

    /**
     * TAMBAHAN: Scope untuk filter berdasarkan status (active/inactive)
     */
    public function scopeByStatus($query, $status)
    {
        if ($status === 'active') {
            return $query->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            return $query->whereNotNull('deleted_at')->withTrashed();
        }

        return $query;
    }

    public function getTableDataAttribute()
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'name' => $this->full_name,
            'gender' => $this->gender,
            'mobile_number' => $this->mobile_number,
            'branch' => $this->branch ?? '-',
            'role' => $this->role ?? '-',
            'status' => $this->status_display,
            'is_active' => is_null($this->deleted_at)
        ];
    }
}
