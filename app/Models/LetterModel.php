<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LetterModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'letters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'letter_name',
        'status',
        'path_content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_WAITING = 'Waiting Approval';
    const STATUS_APPROVED = 'Approve';
    const STATUS_REJECTED = 'Reject';

    /**
     * Get the employee that owns this letter.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'user_id');
    }

    /**
     * Scope a query to only include pending letters.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * Scope a query to only include approved letters.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include rejected letters.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to only include today's letters.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope a query to only include this week's letters.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to only include this month's letters.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope a query to only include letters within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query for specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('user_id', $employeeId);
    }

    /**
     * Get formatted creation date.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i:s') : '';
    }

    /**
     * Get formatted date only.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y') : '';
    }

    /**
     * Get full file path for the letter content.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->path_content);
    }

    /**
     * Get file URL for the letter content.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->path_content ? asset('storage/' . $this->path_content) : null;
    }

    /**
     * Get file extension from path.
     */
    public function getFileExtensionAttribute(): ?string
    {
        return $this->path_content ? pathinfo($this->path_content, PATHINFO_EXTENSION) : null;
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeAttribute(): ?string
    {
        $fullPath = $this->full_path;

        if (!file_exists($fullPath)) {
            return null;
        }

        $bytes = filesize($fullPath);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get status icon for UI.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => 'clock',
            self::STATUS_APPROVED => 'check-circle',
            self::STATUS_REJECTED => 'x-circle',
            default => 'help-circle'
        };
    }

    /**
     * Check if letter is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Check if letter is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if letter is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the letter.
     */
    public function approve(): bool
    {
        return $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Reject the letter.
     */
    public function reject(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Reset status to pending.
     */
    public function resetToPending(): bool
    {
        return $this->update(['status' => self::STATUS_WAITING]);
    }

    /**
     * Check if file exists.
     */
    public function fileExists(): bool
    {
        return $this->path_content && file_exists($this->full_path);
    }

    /**
     * Get file contents.
     */
    public function getFileContents(): ?string
    {
        if (!$this->fileExists()) {
            return null;
        }

        return file_get_contents($this->full_path);
    }

    /**
     * Delete file when model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($letter) {
            if ($letter->fileExists()) {
                unlink($letter->full_path);
            }
        });
    }

    /**
     * Get days since submission.
     */
    public function getDaysSinceSubmissionAttribute(): int
    {
        return $this->created_at ? $this->created_at->diffInDays(Carbon::now()) : 0;
    }

    /**
     * Check if letter was submitted recently (within 24 hours).
     */
    public function isRecentlySubmitted(): bool
    {
        return $this->created_at && $this->created_at->diffInHours(Carbon::now()) <= 24;
    }

    /**
     * Get processing time (for approved/rejected letters).
     */
    public function getProcessingTimeAttribute(): ?string
    {
        if ($this->isPending()) {
            return null;
        }

        $processingDays = $this->days_since_submission;

        if ($processingDays == 0) {
            return 'Same day';
        }

        return $processingDays . ' day' . ($processingDays > 1 ? 's' : '');
    }

    /**
     * Get letter summary for notifications.
     */
    public function getSummaryAttribute(): string
    {
        return "Surat '{$this->letter_name}' oleh {$this->employee->name} - {$this->status}";
    }
}