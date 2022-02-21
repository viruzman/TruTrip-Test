<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'title',
        'origin',
        'destination',
        'start_date',
        'end_date',
        'type',
        'description'
    ];
}
