<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function stations()
    {
        return $this->belongsToMany(Station::class, 'fuel_type_pivot_station');
    }

    public function statuses()
    {
        return $this->hasMany(StationStatus::class);
    }
}

