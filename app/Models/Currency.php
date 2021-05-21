<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'active',
        'can_send',
        'can_receive',
        'country_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
