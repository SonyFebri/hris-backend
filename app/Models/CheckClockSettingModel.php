<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettingModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_clock_settings';

    public $incrementing = false;
    protected $keyType = 'string'; // UUID

    protected $fillable = [
        'name',
        'type',

    ];

    // Jika ingin menonaktifkan timestamps default Laravel
    // public $timestamps = false;

    /**
     * (Opsional) Relasi ke employee jika ck_settings_id di employees mengarah ke sini
     */
    public function employees()
    {
        return $this->hasMany(EmployeeModel::class, 'ck_settings_id');
    }

    /**
     * (Opsional) Relasi ke check_clock_setting_times jika ada
     */
    public function settingTimes()
    {
        return $this->hasMany(CheckClockSettingTimeModel::class, 'ck_settings_id');
    }
}