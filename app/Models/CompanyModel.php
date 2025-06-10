<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class CompanyModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'company_code',
        'bank_name',
        'bank_number',
        'subscription_days',
        'employee_count',
        'max_employee_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subscription_days' => 'integer',
        'employee_count' => 'integer',
        'max_employee_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the users for the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(UsersModel::class, 'company_id', 'id');
    }

    /**
     * Get the employees for the company through users.
     */
    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(
            EmployeeModel::class,
            UsersModel::class,
            'company_id', // Foreign key on users table
            'user_id',    // Foreign key on employees table
            'id',         // Local key on companies table
            'id'          // Local key on users table
        );
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope a query to only include companies with active subscriptions.
     */
    public function scopeSubscribed($query)
    {
        return $query->where('subscription_days', '>', 0);
    }

    /**
     * Scope a query to only include companies with expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('subscription_days', '<=', 0);
    }

    /**
     * Scope a query to only include companies that haven't reached max employee limit.
     */
    public function scopeCanAddEmployees($query)
    {
        return $query->whereColumn('employee_count', '<', 'max_employee_count');
    }

    /**
     * Get subscription end date based on creation date and subscription days.
     */
    public function getSubscriptionEndDateAttribute(): Carbon
    {
        return $this->created_at->addDays($this->subscription_days);
    }

    /**
     * Get remaining subscription days.
     */
    public function getRemainingSubscriptionDaysAttribute(): int
    {
        $endDate = $this->subscription_end_date;
        $now = Carbon::now();

        if ($now->greaterThan($endDate)) {
            return 0;
        }

        return $now->diffInDays($endDate);
    }

    /**
     * Check if subscription is active.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->remaining_subscription_days > 0;
    }

    /**
     * Check if subscription is expired.
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->remaining_subscription_days <= 0;
    }

    /**
     * Check if subscription will expire soon (within 7 days).
     */
    public function isSubscriptionExpiringSoon(): bool
    {
        return $this->remaining_subscription_days <= 7 && $this->remaining_subscription_days > 0;
    }

    /**
     * Get available employee slots.
     */
    public function getAvailableEmployeeSlotsAttribute(): int
    {
        return max(0, $this->max_employee_count - $this->employee_count);
    }

    /**
     * Check if company can add more employees.
     */
    public function canAddEmployees(): bool
    {
        return $this->employee_count < $this->max_employee_count;
    }

    /**
     * Check if company has reached maximum employee limit.
     */
    public function hasReachedEmployeeLimit(): bool
    {
        return $this->employee_count >= $this->max_employee_count;
    }

    /**
     * Get employee usage percentage.
     */
    public function getEmployeeUsagePercentageAttribute(): float
    {
        if ($this->max_employee_count == 0) {
            return 0;
        }

        return round(($this->employee_count / $this->max_employee_count) * 100, 2);
    }

    /**
     * Extend subscription by adding days.
     */
    public function extendSubscription(int $days): bool
    {
        return $this->update([
            'subscription_days' => $this->subscription_days + $days
        ]);
    }

    /**
     * Increase employee count when adding new employee.
     */
    public function incrementEmployeeCount(): bool
    {
        if (!$this->canAddEmployees()) {
            return false;
        }

        return $this->increment('employee_count');
    }

    /**
     * Decrease employee count when removing employee.
     */
    public function decrementEmployeeCount(): bool
    {
        if ($this->employee_count <= 0) {
            return false;
        }

        return $this->decrement('employee_count');
    }

    /**
     * Update max employee count.
     */
    public function updateMaxEmployeeCount(int $count): bool
    {
        return $this->update([
            'max_employee_count' => $count
        ]);
    }

    /**
     * Get company status based on subscription and employee count.
     */
    public function getStatusAttribute(): string
    {
        if ($this->isSubscriptionExpired()) {
            return 'Expired';
        }

        if ($this->isSubscriptionExpiringSoon()) {
            return 'Expiring Soon';
        }

        if ($this->hasReachedEmployeeLimit()) {
            return 'Employee Limit Reached';
        }

        return 'Active';
    }

    /**
     * Get formatted subscription info.
     */
    public function getSubscriptionInfoAttribute(): string
    {
        $remaining = $this->remaining_subscription_days;

        if ($remaining <= 0) {
            return 'Subscription expired';
        }

        return $remaining . ' day' . ($remaining > 1 ? 's' : '') . ' remaining';
    }

    /**
     * Get formatted employee info.
     */
    public function getEmployeeInfoAttribute(): string
    {
        return $this->employee_count . '/' . $this->max_employee_count . ' employees';
    }

    /**
     */
    public function getTotalEmployeesInMonth(Carbon $date): int
    {
        return $this->employees()
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->withTrashed() // Include soft deleted
            ->count();
    }

    /**
     */
    public function getTotalNewHiresInMonth(Carbon $date): int
    {
        return $this->employees()
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->count(); // Only active employees
    }

    /**
     */
    public function getActiveEmployeesCount(): int
    {
        return $this->employees()->count();
    }

    /**
     */
    public function getEmployeeStatisticsForPeriod(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();

        return [
            'periode' => $date->format('F, Y'),
            'total_employee' => $this->getTotalEmployeesInMonth($date),
            'total_new_hire' => $this->getTotalNewHiresInMonth($date), 
            'active_employee' => $this->getActiveEmployeesCount(),
        ];
    }

    /**
     * Get employee statistics for dashboard
     */
    public function getEmployeeStatistics(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();

        return [
            'total_employee' => $this->getTotalEmployeesInMonth($date),
            'total_new_hire' => $this->getTotalNewHiresInMonth($date),
            'active_employee' => $this->getActiveEmployeesCount(),
            'period' => $date->format('F, Y')
        ];
    }

    /**
     * Untuk memastikan konsistensi data
     */
    public function syncEmployeeCount(): bool
    {
        $actualCount = $this->getActiveEmployeesCount();
        return $this->update(['employee_count' => $actualCount]);
    }
}