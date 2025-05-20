<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LetterFormatModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'letter_formats';

    public $incrementing = false;
    protected $keyType = 'string'; // UUID

    protected $fillable = [
        'name',
        'content',
        'status',

    ];
}