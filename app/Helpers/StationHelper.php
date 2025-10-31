<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

trait StationHelper
{
    private function bustStationCaches($stationId = null): void
    {
        // --- Supprimer toutes les variantes du cache stations.index ---
        if (config('cache.default') === 'redis' && class_exists(Redis::class)) {
            // Si Redis est disponible, on cherche toutes les clés correspondantes
            $keys = Redis::keys(config('cache.prefix') . ':stations.index.*');
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        } else {
            // Sinon, oublie les plus courantes
            $patterns = [
                'stations.index.created_at.desc',
                'stations.index.created_at.asc',
                'stations.index.visits.desc',
                'stations.index.visits.asc',
                'stations.index.name.asc',
                'stations.index.name.desc',
            ];
            foreach ($patterns as $key) {
                Cache::forget($key);
            }
        }

        // --- Supprimer les caches globaux ---
        Cache::forget('stations.stats');     // tableau de bord admin
        Cache::forget('stations.analytics'); // analytics publique

        // --- Supprimer le cache d'une station spécifique ---
        if ($stationId !== null) {
            Cache::forget("station.$stationId");
            Cache::forget("station.$stationId.history");
        }
    }
}
