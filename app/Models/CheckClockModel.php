<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckClockModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_clocks';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'check_clock_type',
        'check_clock_time',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relasi dengan model User
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}