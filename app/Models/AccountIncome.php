<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Description:
 * Account income model store all payments to banks accounts
 */
class AccountIncome extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'transaction_id',
        'origin',
        'transaction_number',
        'transaction_date',
        'amount'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
