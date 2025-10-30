<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'type', 'message'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}

