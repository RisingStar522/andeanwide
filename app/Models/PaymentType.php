<?php

namespace App\Models;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'class_name',
        'is_active'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
