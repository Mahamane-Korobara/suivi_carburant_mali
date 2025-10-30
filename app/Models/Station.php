<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;


class Station extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'address',
        'quartier',
        'commune',
        'gerant_name',
        'phone',
        'email',
        'type',
        'status',
        'rejection_reason',
        'is_active',
        'password',
    ];

    protected $hidden = ['password'];

     public function statuses()
    {
        return $this->hasMany(StationStatus::class);
    }

    public function lastStatus()
    {
        return $this->hasOne(StationStatus::class)->latestOfMany();
    }
}
