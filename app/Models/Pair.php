<?php

namespace App\Models;

use App\Traits\HasExchangeRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pair extends Model
{
    use HasFactory, HasExchangeRate;

    protected $fillable = [
        'is_active',
        'is_default',
        'default_amount',
        'min_amount',
        'name',
        'api_class',
        'observation',
        'base_id',
        'quote_id',
        'offset_by',
        'offset',
        'offset_to_corps',
        'offset_to_imports',
        'min_pip_value',
        'show_inverse',
        'max_tier_1',
        'max_tier_2',
        'more_rate',
        'is_more_enabled',
        'decimals'
    ];

    protected $with = ['base', 'quote'];

    public function base()
    {
        return $this->belongsTo(Currency::class, 'base_id');
    }

    public function quote()
    {
        return $this->belongsTo(Currency::class, 'quote_id');
    }

    public function priorities()
    {
        return $this->belongsToMany(Priority::class);
    }

    public function getRateAttribute()
    {
        $rate = Rate::where('pair_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        return $rate;
    }
}
