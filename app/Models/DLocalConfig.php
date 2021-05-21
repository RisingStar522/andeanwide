<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DLocalConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'client_id',
        'client_secret',
        'grant_type',
        'access_token'
    ];
}
