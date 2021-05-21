<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency_id',
        'quote_currency_id',
        'pair_id',
        'pair_name',
        'quote',
        'api_timestamp'
    ];
}
