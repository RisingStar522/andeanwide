<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'is_active',
        'country_id',
        'currency_id',
        'bank_id',
        'label',
        'bank_name',
        'bank_account',
        'account_name',
        'description',
        'document_number',
        'account_type'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function incomes()
    {
        return $this->hasMany(AccountIncome::class);
    }
}
