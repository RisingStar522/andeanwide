<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remitter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fullname',
        'document_type',
        'dni',
        'issuance_date',
        'expiration_date',
        'dob',
        'address',
        'city',
        'state',
        'country_id',
        'issuance_country_id',
        'phone',
        'email',
        'document_url',
        'reverse_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function issuance_country()
    {
        return $this->belongsTo(Country::class, 'issuance_country_id');
    }
}
