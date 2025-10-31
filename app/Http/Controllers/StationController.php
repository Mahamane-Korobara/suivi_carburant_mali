<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Models\Station;
use App\Models\StationVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Helpers\StationHelper;

class StationController extends Controller
{
    use StationHelper;
    public function register(StoreStationRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = 'pending';

        // Créer la station
        $station = \App\Models\Station::create($validated);

        // Associer les types de carburant sélectionnés
        if (isset($validated['fuel_types'])) {
            $station->fuelTypes()->sync($validated['fuel_types']);
        }

        return response()->json([
            'message' => 'Demande envoyée avec succès. En attente de validation.',
            'data' => $station->load('fuelTypes'),
        ], 201);
    }

    // Consultation des stations
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $cacheKey = "stations.index.$sort.$order";

        // Cache 1 heure
        $stations = Cache::remember($cacheKey, 3600, function () use ($sort, $order) {
            return Station::withCount('visits')
                ->where('status', 'approved')
                ->orderBy($sort === 'visits' ? 'visits_count' : $sort, $order)
                ->get();
        });

        return response()->json($stations);
    }

   public function show($id)
    {
        $cacheKey = "station.$id";

        $station = Cache::remember($cacheKey, 3600, function () use ($id) {
            return Station::findOrFail($id);
        });

        // Enregistre la visite
        StationVisit::create([
            'station_id' => $station->id,
            'ip_address' => request()->ip(),
            'device' => request()->header('User-Agent'),
            'commune' => $station->commune,
        ]);

        // On invalide juste le cache analytics, pas la station elle-même
        Cache::forget('stations.analytics');

        return response()->json($station);
    }
}
