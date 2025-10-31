<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

trait StationHelper
{
    private function bustStationCaches($stationId = null): void
    {
        Cache::forget('stations.index'); // Liste des stations
        Cache::forget('stations.stats'); // Tableau de bord admin
        Cache::forget('stations.analytics'); // Analytics (si tu en as)
        
        if ($stationId !== null) {
            Cache::forget("station.$stationId");
            Cache::forget("station.$stationId.history");
        }
    }
}
