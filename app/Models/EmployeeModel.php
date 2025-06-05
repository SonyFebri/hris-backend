<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'id_employee',
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Tambahkan field yang ingin disembunyikan jika ada
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke shift schedules (jika ada)
     */
    public function shiftSchedules()
    {
        return $this->hasMany(EmployeeShiftScheduleModel::class, 'user_id', 'user_id');
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
        $this->attributes['NIK'] = strtoupper($value);
    }

    /**
     * Set first name dengan proper case
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords(strtolower($value));
    }

    /**
     * Set last name dengan proper case
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords(strtolower($value));
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
                ->orWhere('id_employee', 'like', "%{$search}%")
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
        return $this->date_birth->age ?? null;
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
}