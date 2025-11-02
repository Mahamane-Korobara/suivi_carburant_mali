<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationNotification extends Model
{
    protected $fillable = ['station_id', 'title', 'message', 'read'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
