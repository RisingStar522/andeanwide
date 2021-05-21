<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_type_id',
        'user_id',
        'account_id',
        'transaction_number',
        'transaction_date',
        'payment_amount',
        'payment_code',
        'observation',
        'image_url',
        'verified_at',
        'rejected_at',
    ];

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
