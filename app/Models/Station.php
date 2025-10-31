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
        'status',
        'latitude',
        'longitude',
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

    public function visits()
    {
        return $this->hasMany(StationVisit::class);
    }

        public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function fuelTypes()
    {
        return $this->belongsToMany(FuelType::class, 'fuel_type_pivot_station');
    }
}
