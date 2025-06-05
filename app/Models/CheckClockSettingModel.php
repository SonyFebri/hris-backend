<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettingModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_clock_settings';


    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'shift_count',
        'shift_number',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'shift_number' => 'integer',
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
     * Relasi ke employee shift schedules
     */
    public function employeeShiftSchedules()
    {
        return $this->hasMany(EmployeeShiftScheduleModel::class, 'ck_settings_id');
    }

    /**
     * Relasi ke check clock details (jika ada tabel detail waktu)
     */
    public function checkClockDetails()
    {
        return $this->hasMany(CheckClockSettingTimeModel::class, 'ck_settings_id');
    }

    // ===========================================
    // ACCESSOR & MUTATOR
    // ===========================================

    /**
     * Get formatted name with shift info
     */
    public function getFormattedNameAttribute()
    {
        return $this->name . ' (Shift ' . $this->shift_number . '/' . $this->shift_count . ')';
    }

    /**
     * Set name dengan title case
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /**
     * Get shift info as string
     */
    public function getShiftInfoAttribute()
    {
        return "Shift {$this->shift_number} dari {$this->shift_count} shift";
    }

    // ===========================================
    // SCOPES
    // ===========================================

    /**
     * Scope untuk filter berdasarkan jumlah shift
     */
    public function scopeByShiftCount($query, $shiftCount)
    {
        return $query->where('shift_count', $shiftCount);
    }

    /**
     * Scope untuk filter berdasarkan nomor shift
     */
    public function scopeByShiftNumber($query, $shiftNumber)
    {
        return $query->where('shift_number', $shiftNumber);
    }

    /**
     * Scope untuk setting aktif (tidak soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope untuk search berdasarkan nama
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Scope untuk single shift (shift_count = 1)
     */
    public function scopeSingleShift($query)
    {
        return $query->where('shift_count', '1');
    }

    /**
     * Scope untuk multiple shift (shift_count > 1)
     */
    public function scopeMultipleShift($query)
    {
        return $query->where('shift_count', '>', '1');
    }

    /**
     * Scope untuk shift pagi (shift_number = 1)
     */
    public function scopeMorningShift($query)
    {
        return $query->where('shift_number', 1);
    }

    /**
     * Scope untuk shift siang (shift_number = 2)
     */
    public function scopeAfternoonShift($query)
    {
        return $query->where('shift_number', 2);
    }

    /**
     * Scope untuk shift malam (shift_number = 3)
     */
    public function scopeNightShift($query)
    {
        return $query->where('shift_number', 3);
    }

    /**
     * Scope untuk order berdasarkan shift number
     */
    public function scopeOrderByShift($query, $direction = 'asc')
    {
        return $query->orderBy('shift_number', $direction);
    }

    // ===========================================
    // METHODS
    // ===========================================

    /**
     * Check if this is a single shift setting
     */
    public function isSingleShift()
    {
        return $this->shift_count == '1';
    }

    /**
     * Check if this is a multiple shift setting
     */
    public function isMultipleShift()
    {
        return $this->shift_count > '1';
    }

    /**
     * Check if this is morning shift
     */
    public function isMorningShift()
    {
        return $this->shift_number == 1;
    }

    /**
     * Check if this is afternoon shift
     */
    public function isAfternoonShift()
    {
        return $this->shift_number == 2;
    }

    /**
     * Check if this is night shift
     */
    public function isNightShift()
    {
        return $this->shift_number == 3;
    }

    /**
     * Get shift name based on shift number
     */
    public function getShiftName()
    {
        $shiftNames = [
            1 => 'Pagi',
            2 => 'Siang',
            3 => 'Malam'
        ];

        return $shiftNames[$this->shift_number] ?? 'Unknown';
    }

    /**
     * Get total shift count as integer
     */
    public function getTotalShiftCount()
    {
        return (int) $this->shift_count;
    }

    /**
     * Get shift number as integer
     */
    public function getShiftNumberInt()
    {
        return (int) $this->shift_number;
    }

    /**
     * Check if shift number is valid for shift count
     */
    public function isValidShiftNumber()
    {
        return $this->shift_number <= $this->getTotalShiftCount();
    }

    /**
     * Get next shift number (circular)
     */
    public function getNextShiftNumber()
    {
        $nextShift = $this->shift_number + 1;
        return $nextShift > $this->getTotalShiftCount() ? 1 : $nextShift;
    }

    /**
     * Get previous shift number (circular)
     */
    public function getPreviousShiftNumber()
    {
        $prevShift = $this->shift_number - 1;
        return $prevShift < 1 ? $this->getTotalShiftCount() : $prevShift;
    }

    /**
     * Get all possible shift numbers for this setting
     */
    public function getAvailableShiftNumbers()
    {
        return range(1, $this->getTotalShiftCount());
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    /**
     * Get all shift names
     */
    public static function getAllShiftNames()
    {
        return [
            1 => 'Pagi',
            2 => 'Siang',
            3 => 'Malam'
        ];
    }

    /**
     * Get available shift counts
     */
    public static function getAvailableShiftCounts()
    {
        return ['1', '2', '3'];
    }

    /**
     * Create default shift settings
     */
    public static function createDefaultSettings()
    {
        $defaults = [
            ['name' => 'Shift Tunggal', 'shift_count' => '1', 'shift_number' => 1],
            ['name' => 'Shift Pagi', 'shift_count' => '2', 'shift_number' => 1],
            ['name' => 'Shift Siang', 'shift_count' => '2', 'shift_number' => 2],
            ['name' => 'Shift Pagi (3 Shift)', 'shift_count' => '3', 'shift_number' => 1],
            ['name' => 'Shift Siang (3 Shift)', 'shift_count' => '3', 'shift_number' => 2],
            ['name' => 'Shift Malam (3 Shift)', 'shift_count' => '3', 'shift_number' => 3],
        ];

        foreach ($defaults as $default) {
            self::firstOrCreate(
                ['name' => $default['name']],
                $default
            );
        }
    }
}