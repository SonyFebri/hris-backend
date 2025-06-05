<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckClockSettingTimeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_clock_setting_times';

    protected $fillable = [
        'ck_settings_id',
        'clock_in',
        'clock_out',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clock_in' => 'datetime:H:i:s',
        'clock_out' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the check clock setting that owns this time setting.
     */
    public function checkClockSetting(): BelongsTo
    {
        return $this->belongsTo(CheckClockSettingModel::class, 'ck_settings_id');
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Get formatted clock in time.
     */
    public function getFormattedClockInAttribute(): string
    {
        return $this->clock_in ? $this->clock_in->format('H:i') : '';
    }

    /**
     * Get formatted clock out time.
     */
    public function getFormattedClockOutAttribute(): string
    {
        return $this->clock_out ? $this->clock_out->format('H:i') : '';
    }

    /**
     * Check if the current time is within the allowed clock in/out range.
     */
    public function isWithinTimeRange($currentTime = null): bool
    {
        $currentTime = $currentTime ?: now()->format('H:i:s');

        return $currentTime >= $this->clock_in && $currentTime <= $this->clock_out;
    }
}