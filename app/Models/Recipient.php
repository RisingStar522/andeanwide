<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_id',
        'bank_id',
        'name',
        'lastname',
        'dni',
        'phone',
        'email',
        'bank_name',
        'bank_account',
        'account_type',
        'bank_code',
        'address',
        'document_type'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
