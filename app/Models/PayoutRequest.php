<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'external_id',
        'document_id',
        'document_type',
        'beneficiary_name',
        'beneficiary_lastname',
        'country',
        'bank_code',
        'bank_name',
        'bank_account',
        'account_type',
        'amount',
        'address',
        'currency',
        'email',
        'phone',
        'purpose',
        'remitter_fullname',
        'remitter_document',
        'remitter_address',
        'remitter_city',
        'remitter_country',
        'notification_url',
        'request_url',
    ];
}
