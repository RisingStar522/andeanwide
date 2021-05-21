<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isNull;

class Identity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issuance_country_id',
        'nationality_country_id',
        'identity_number',
        'document_type',
        'firstname',
        'lastname',
        'dob',
        'issuance_date',
        'expiration_date',
        'gender',
        'profession',
        'activity',
        'position',
        'state',
    ];

    public function getIsVerifiedAttribute()
    {
        return !is_null($this->verified_at);
    }

    public function getIsRejectedAttribute()
    {
        return !is_null($this->rejected_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuanceCountry()
    {
        return $this->hasOne(Country::class, 'id', 'issuance_country_id');
    }

    public function nationalityCountry()
    {
        return $this->hasOne(Country::class, 'id', 'nationality_country_id');
    }
}
