<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'abbr',
        'code',
        'is_active'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
