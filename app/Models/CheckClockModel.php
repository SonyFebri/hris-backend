<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CheckClockModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'check_clocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'check_clock_type',
        'check_clock_time',
        'status',
        'Approval',
        'image',
        'latitude',
        'longitude',
        'address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_clock_time' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Check clock type constants
     */
    const TYPE_CLOCK_IN = 'clock_in';
    const TYPE_CLOCK_OUT = 'clock_out';
    const TYPE_ABSENT = 'absent';
    const TYPE_SICK_LEAVE = 'sick_leave';
    const TYPE_ANNUAL_LEAVE = 'annual_leave';

    /**
     * Status constants
     */
    const STATUS_LATE = 'late';
    const STATUS_ON_TIME = 'on_time';

    /**
     * Approval constants
     */
    const APPROVAL_WAITING = 'Waiting Approval';
    const APPROVAL_APPROVE = 'Approve';
    const APPROVAL_REJECT = 'Reject';

    /**
     * Get the employee that owns this check clock record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'user_id');
    }

    /**
     * Scope a query to only include clock in records.
     */
    public function scopeClockIn($query)
    {
        return $query->where('check_clock_type', self::TYPE_CLOCK_IN);
    }

    /**
     * Scope a query to only include clock out records.
     */
    public function scopeClockOut($query)
    {
        return $query->where('check_clock_type', self::TYPE_CLOCK_OUT);
    }

    /**
     * Scope a query to only include approved records.
     */
    public function scopeApproved($query)
    {
        return $query->where('Approval', self::APPROVAL_APPROVE);
    }

    /**
     * Scope a query to only include pending approval records.
     */
    public function scopePending($query)
    {
        return $query->where('Approval', self::APPROVAL_WAITING);
    }

    /**
     * Scope a query to only include late records.
     */
    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    /**
     * Scope a query to only include on time records.
     */
    public function scopeOnTime($query)
    {
        return $query->where('status', self::STATUS_ON_TIME);
    }

    /**
     * Scope a query for today's records.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('check_clock_time', Carbon::today());
    }

    /**
     * Scope a query for records within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_clock_time', [$startDate, $endDate]);
    }

    /**
     * Get formatted check clock time.
     */
    public function getFormattedCheckClockTimeAttribute(): string
    {
        return $this->check_clock_time ? $this->check_clock_time->format('d/m/Y H:i:s') : '';
    }

    /**
     * Get formatted date only.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->check_clock_time ? $this->check_clock_time->format('d/m/Y') : '';
    }

    /**
     * Get formatted time only.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->check_clock_time ? $this->check_clock_time->format('H:i:s') : '';
    }

    /**
     * Get full image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Check if the record is approved.
     */
    public function isApproved(): bool
    {
        return $this->Approval === self::APPROVAL_APPROVE;
    }

    /**
     * Check if the record is pending approval.
     */
    public function isPending(): bool
    {
        return $this->Approval === self::APPROVAL_WAITING;
    }

    /**
     * Check if the record is rejected.
     */
    public function isRejected(): bool
    {
        return $this->Approval === self::APPROVAL_REJECT;
    }

    /**
     * Check if the employee was late.
     */
    public function isLate(): bool
    {
        return $this->status === self::STATUS_LATE;
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude
            ];
        }
        return null;
    }

    /**
     * Get distance between two coordinates (in kilometers).
     */
    public function getDistanceFrom($latitude, $longitude): ?float
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}