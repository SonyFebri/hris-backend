<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeShiftScheduleModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employee_shift_schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ck_settings_id',
        'work_date',
        'shift_count',
        'shift_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'work_date' => 'date',
        'shift_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Shift count constants
     */
    const SHIFT_COUNT_SINGLE = '1';
    const SHIFT_COUNT_DOUBLE = '2';
    const SHIFT_COUNT_TRIPLE = '3';

    /**
     * Get the employee that owns this shift schedule.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'user_id');
    }

    /**
     * Get the check clock setting that this schedule belongs to.
     */
    public function checkClockSetting(): BelongsTo
    {
        return $this->belongsTo(CheckClockSettingModel::class, 'ck_settings_id');
    }

    /**
     * Scope a query to only include today's schedules.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('work_date', Carbon::today());
    }

    /**
     * Scope a query to only include schedules for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('work_date', $date);
    }

    /**
     * Scope a query to only include schedules within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include this week's schedules.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('work_date', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to only include this month's schedules.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('work_date', Carbon::now()->month)
            ->whereYear('work_date', Carbon::now()->year);
    }

    /**
     * Scope a query to only include single shift schedules.
     */
    public function scopeSingleShift($query)
    {
        return $query->where('shift_count', self::SHIFT_COUNT_SINGLE);
    }

    /**
     * Scope a query to only include double shift schedules.
     */
    public function scopeDoubleShift($query)
    {
        return $query->where('shift_count', self::SHIFT_COUNT_DOUBLE);
    }

    /**
     * Scope a query to only include triple shift schedules.
     */
    public function scopeTripleShift($query)
    {
        return $query->where('shift_count', self::SHIFT_COUNT_TRIPLE);
    }

    /**
     * Scope a query to only include schedules for specific shift number.
     */
    public function scopeShiftNumber($query, $shiftNumber)
    {
        return $query->where('shift_number', $shiftNumber);
    }

    /**
     * Scope a query to only include upcoming schedules.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('work_date', '>=', Carbon::today());
    }

    /**
     * Scope a query to only include past schedules.
     */
    public function scopePast($query)
    {
        return $query->where('work_date', '<', Carbon::today());
    }

    /**
     * Get formatted work date.
     */
    public function getFormattedWorkDateAttribute(): string
    {
        return $this->work_date ? $this->work_date->format('d/m/Y') : '';
    }

    /**
     * Get formatted work date with day name.
     */
    public function getFormattedWorkDateWithDayAttribute(): string
    {
        return $this->work_date ? $this->work_date->format('l, d/m/Y') : '';
    }

    /**
     * Get shift count as integer.
     */
    public function getShiftCountIntAttribute(): int
    {
        return (int) $this->shift_count;
    }

    /**
     * Get shift description.
     */
    public function getShiftDescriptionAttribute(): string
    {
        $descriptions = [
            self::SHIFT_COUNT_SINGLE => 'Single Shift',
            self::SHIFT_COUNT_DOUBLE => 'Double Shift',
            self::SHIFT_COUNT_TRIPLE => 'Triple Shift',
        ];

        return $descriptions[$this->shift_count] ?? 'Unknown Shift';
    }

    /**
     * Get full shift info.
     */
    public function getShiftInfoAttribute(): string
    {
        return $this->shift_description . ' #' . $this->shift_number;
    }

    /**
     * Check if the schedule is for today.
     */
    public function isToday(): bool
    {
        return $this->work_date && $this->work_date->isToday();
    }

    /**
     * Check if the schedule is for tomorrow.
     */
    public function isTomorrow(): bool
    {
        return $this->work_date && $this->work_date->isTomorrow();
    }

    /**
     * Check if the schedule is for yesterday.
     */
    public function isYesterday(): bool
    {
        return $this->work_date && $this->work_date->isYesterday();
    }

    /**
     * Check if the schedule is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->work_date && $this->work_date->isFuture();
    }

    /**
     * Check if the schedule is past.
     */
    public function isPast(): bool
    {
        return $this->work_date && $this->work_date->isPast();
    }

    /**
     * Get days until work date.
     */
    public function getDaysUntilWorkAttribute(): int
    {
        if (!$this->work_date) {
            return 0;
        }

        return Carbon::today()->diffInDays($this->work_date, false);
    }

    /**
     * Check if this is a single shift.
     */
    public function isSingleShift(): bool
    {
        return $this->shift_count === self::SHIFT_COUNT_SINGLE;
    }

    /**
     * Check if this is a double shift.
     */
    public function isDoubleShift(): bool
    {
        return $this->shift_count === self::SHIFT_COUNT_DOUBLE;
    }

    /**
     * Check if this is a triple shift.
     */
    public function isTripleShift(): bool
    {
        return $this->shift_count === self::SHIFT_COUNT_TRIPLE;
    }

    /**
     * Get related check clocks for this schedule date.
     */
    public function getCheckClocksForDateAttribute()
    {
        return CheckClockModel::where('user_id', $this->user_id)
            ->whereDate('check_clock_time', $this->work_date)
            ->get();
    }

    /**
     * Check if employee has clocked in for this schedule.
     */
    public function hasClockedIn(): bool
    {
        return CheckClockModel::where('user_id', $this->user_id)
            ->whereDate('check_clock_time', $this->work_date)
            ->where('check_clock_type', CheckClockModel::TYPE_CLOCK_IN)
            ->exists();
    }

    /**
     * Check if employee has clocked out for this schedule.
     */
    public function hasClockedOut(): bool
    {
        return CheckClockModel::where('user_id', $this->user_id)
            ->whereDate('check_clock_time', $this->work_date)
            ->where('check_clock_type', CheckClockModel::TYPE_CLOCK_OUT)
            ->exists();
    }

    /**
     * Get attendance status for this schedule.
     */
    public function getAttendanceStatusAttribute(): string
    {
        if ($this->isPast()) {
            if ($this->hasClockedIn() && $this->hasClockedOut()) {
                return 'Completed';
            } elseif ($this->hasClockedIn()) {
                return 'Incomplete (No Clock Out)';
            } else {
                return 'Absent';
            }
        }

        if ($this->isToday()) {
            if ($this->hasClockedIn() && $this->hasClockedOut()) {
                return 'Completed';
            } elseif ($this->hasClockedIn()) {
                return 'In Progress';
            } else {
                return 'Not Started';
            }
        }

        return 'Scheduled';
    }
}