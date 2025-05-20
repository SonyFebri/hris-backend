<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'letters';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'letter_format_id',
        'user_id',
        'name',
    ];


    /**
     * Relasi dengan model LetterFormat
     */
    public function letterFormat()
    {
        return $this->belongsTo(LetterFormatModel::class, 'letter_format_id');
    }

    /**
     * Relasi dengan model User
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}