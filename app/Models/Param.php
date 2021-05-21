<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Param extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'value',
        'value_type',
        'default_value',
    ];
}
