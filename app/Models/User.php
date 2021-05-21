<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'balance_credit_limit',
        'balance_currency_id',
        'account_type'
    ];

    /**
     * Valores por defecto de atributos del modelo.
     *
     * @var array
     */
    protected $attributes = [
        'balance' => 0,
        'balance_credit_limit' => 0,
    ];

    /*
     * Para devolver el disponible, tomamos el balance actual mas el límite de crédito
     * Por ejemplo, el balance de 5,000 y si tiene de limite llegar a 10,000 bajo cero, el disponible son 15,000
    */
    public function getAvailableAmountAttribute()
    {
        return $this->balance + $this->balance_credit_limit;
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $with = ['currency'];

    public function identity()
    {
        return $this->hasOne(Identity::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function recipients()
    {
        return $this->hasMany(Recipient::class);
    }

    public function remitters()
    {
        return $this->hasMany(Remitter::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'balance_currency_id');
    }

    public function getStatusAttribute()
    {
        if (is_null($this->identity) && is_null($this->address)) {
            // Usuario por cargar identidad
            return 'SINFO';
        } else if (isset($this->identity) && isset($this->identity->rejected_at)) {
            // Identidad Rechazada
            return 'REJID';
        } else if (isset($this->address) && isset($this->address->rejected_at)) {
            // Direccion rechazada
            return 'REJAD';
        } else if (isset($this->address) && is_null($this->address->verified_at) && is_null($this->address->rejected_at)) {
            // Pendiente por validar direccion
            return 'PENAD';
        } else if (isset($this->address) && isset($this->address->verified_at) && is_null($this->address->rejected_at)) {
            // Direccion validada
            return 'VALAD';
        } else if (isset($this->identity) && is_null($this->identity->verified_at) && is_null($this->identity->rejected_at)) {
            // Pendiente por validar identidad
            return 'PENID';
        } else if (isset($this->identity) && isset($this->identity->verified_at) && is_null($this->identity->rejected_at)) {
            // Identidad Validada
            return 'VALID';
        }
        return null;
    }

    public function getIsAgentAttribute()
    {
        return $this->hasRole('Agent');
    }
}
