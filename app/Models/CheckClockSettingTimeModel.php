<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockSettingTimeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_clock_setting_times';

    public $incrementing = false;
    protected $keyType = 'string'; // UUID

    protected $fillable = [
        'ck_settings_id',
        'day',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',

    ];

    /**
     * Relasi: masing-masing waktu milik satu pengaturan
     */
    public function checkClockSetting()
    {
        return $this->belongsTo(CheckClockSettingModel::class, 'ck_settings_id');
    }
}