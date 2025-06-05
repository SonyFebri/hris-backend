<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salaries';

    public $incrementing = false;
    protected $keyType = 'string'; // UUID

    protected $fillable = [
        'user_id',
        'type',
        'rate',
        'effective_date',

    ];

    /**
     * Relasi: Gaji milik satu pengguna
     */
    public function user()
    {
        return $this->belongsTo(UsersModel::class);
    }
}