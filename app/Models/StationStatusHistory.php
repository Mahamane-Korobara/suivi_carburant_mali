<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Station;
use App\Models\FuelType;

class StationStatusHistory extends Model
{
    protected $fillable = [
        'station_id',
        'fuel_type_id',
        'status',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function fuelType()
    {
        return $this->belongsTo(FuelType::class);
    }
}
