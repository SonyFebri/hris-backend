<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    public $incrementing = false;
    protected $keyType = 'string'; // karena UUID

    protected $fillable = [
        'user_id',
        'ck_settings_id',
        'first_name',
        'last_name',
        'gender',
        'address',
    ];

    /**
     * Relasi: Employee milik satu User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * (Opsional) Jika ck_settings_id berelasi dengan tabel check_clock_settings,
     * tambahkan relasi berikut:
     */
    public function checkClockSetting()
    {
        return $this->belongsTo(CheckClockSettingModel::class, 'ck_settings_id');
    }
}