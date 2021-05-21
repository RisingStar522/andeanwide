<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'id_number',
        'activity',
        'country_id',
        'has_politician_history',
        'politician_history_charge',
        'politician_history_country_id',
        'politician_history_from',
        'politician_history_to',
        'activities',
        'anual_revenues',
        'company_size',
        'funds_origins',
        'verified_at',
        'rejected_at',
        'rejection_reasons',
        'address',
        'fund_origins'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function politician_history_country()
    {
        return $this->belongsTo(Country::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getIsVerifiedAttribute()
    {
        return !is_null($this->verified_at);
    }

    public function getIsRejectedAttribute()
    {
        return !is_null($this->rejected_at);
    }
}
