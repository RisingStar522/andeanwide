<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_id',
        'address',
        'address_ext',
        'state',
        'city',
        'cod',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsVerifiedAttribute()
    {
        return !is_null($this->verified_at);
    }

    public function getIsRejectedAttribute()
    {
        return !is_null($this->rejected_at);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
