<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'sublabel',
        'description',
        'cost_pct',
        'is_active'
    ];

    public function pairs()
    {
        return $this->belongsToMany(Pair::class);
    }
}
