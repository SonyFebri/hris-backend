<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsersModel extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $table = 'users'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'email',
        'company_username',
        'mobile_number',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'company_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id', 'id');
    }

    /**
     * Get the employee profile for this user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(EmployeeModel::class, 'user_id', 'id');
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope a query to only include non-admin users.
     */
    public function scopeNonAdmins($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is employee.
     */
    public function isEmployee(): bool
    {
        return !$this->is_admin;
    }

    //  FUNGSI  UNTUK EMPLOYEE MANAGEMENT

    /**
     * Generate unique company username
     */
    public static function generateCompanyUsername($employeeNumber, $firstName, $companyId)
    {
        $baseUsername = 'cmp' . $employeeNumber . strtolower($firstName);
        $increment = $employeeNumber;
        $username = $baseUsername . $increment;
        
        // Check if username exists in the same company
        while (self::where('company_id', $companyId)
                  ->where('company_username', $username)
                  ->exists()) {
            $increment++;
            $username = $baseUsername . $increment;
        }
        
        return $username;
    }

    /**
     * Generate default password (same as company_username)
     */
    public function generateDefaultPassword()
    {
        return $this->company_username;
    }

    /**
     * Get next employee number for company
     */
    public static function getNextEmployeeNumber($companyId)
    {
        $lastUser = self::where('company_id', $companyId)
                       ->whereNotNull('company_username')
                       ->orderBy('created_at', 'desc')
                       ->first();
        
        if (!$lastUser || !$lastUser->company_username) {
            return 1;
        }
        
        // Extract employee number from company_username
        if (preg_match('/^cmp(\d+)/', $lastUser->company_username, $matches)) {
            return (int)$matches[1] + 1;
        }
        
        return 1;
    }

    /**
     * Create employee user account
     */
    public static function createEmployeeAccount($companyId, $firstName, $mobileNumber = null, $email = null)
    {
        $employeeNumber = self::getNextEmployeeNumber($companyId);
        $companyUsername = self::generateCompanyUsername($employeeNumber, $firstName, $companyId);
        
        return self::create([
            'company_id' => $companyId,
            'email' => $email,
            'company_username' => $companyUsername,
            'mobile_number' => $mobileNumber,
            'password' => bcrypt($companyUsername), // Default password = company_username
            'is_admin' => false,
        ]);
    }

    /**
     * Get employee number from company_username
     */
    public function getEmployeeNumberAttribute()
    {
        if (!$this->company_username) {
            return null;
        }
        
        if (preg_match('/^cmp(\d+)/', $this->company_username, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Get display name (from employee if exists, otherwise email/username)
     */
    public function getDisplayNameAttribute()
    {
        if ($this->employee) {
            return $this->employee->full_name;
        }
        
        return $this->email ?? $this->company_username;
    }

    /**
     * Scope for employees only (non-admin)
     */
    public function scopeEmployees($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Scope for active users (not soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if user account is active
     */
    public function isActive()
    {
        return is_null($this->deleted_at);
    }

    /**
     * Deactivate user account (soft delete)
     */
    public function deactivate()
    {
        return $this->delete();
    }

    /**
     * Reset password to default (company_username)
     */
    public function resetToDefaultPassword()
    {
        if (!$this->company_username) {
            return false;
        }
        
        return $this->update([
            'password' => bcrypt($this->company_username)
        ]);
    }

    /**
     * Update company username and reset password
     */
    public function updateCompanyUsername($newUsername)
    {
        return $this->update([
            'company_username' => $newUsername,
            'password' => bcrypt($newUsername)
        ]);
    }

    /**
     */
    public static function createEmployeeAccountWithValidation($companyId, $firstName, $mobileNumber = null, $email = null)
    {
        // Validasi company exists dan belum exceed limit
        $company = CompanyModel::find($companyId);
        if (!$company || !$company->canAddEmployees()) {
            throw new \Exception('Company not found or employee limit exceeded');
        }
        
        $employeeNumber = self::getNextEmployeeNumber($companyId);
        $companyUsername = self::generateCompanyUsername($employeeNumber, $firstName, $companyId);
        
        $user = self::create([
            'company_id' => $companyId,
            'email' => $email,
            'company_username' => $companyUsername,
            'mobile_number' => $mobileNumber,
            'password' => bcrypt($companyUsername),
            'is_admin' => false,
        ]);
        
        // Update company employee count
        $company->incrementEmployeeCount();
        
        return $user;
    }

    /**
     */
    public function getFormattedDisplayNameAttribute()
    {
        if ($this->employee) {
            return $this->employee->full_name . ' (' . $this->company_username . ')';
        }
        
        return $this->company_username ?? $this->email ?? 'No Name';
    }

    /**
     */
    public function isUsingDefaultPassword()
    {
        if (!$this->company_username) {
            return false;
        }
        
        return password_verify($this->company_username, $this->password);
    }
}