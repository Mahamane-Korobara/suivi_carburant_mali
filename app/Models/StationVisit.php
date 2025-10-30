<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StationVisit extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'ip_address', 'device', 'commune', 'visited_at'];

    public $timestamps = false;

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
