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

        $stations = Cache::remember($cacheKey, 3600, function () use ($sort, $order) {
            return Station::with(['statuses.fuelType'])
                ->where('status', 'approved')
                ->withCount('visits')
                ->orderBy($sort === 'visits' ? 'visits_count' : $sort, $order)
                ->get()
                ->map(function ($station) {
                    return [
                        'id' => $station->id,
                        'name' => $station->name,
                        'commune' => $station->commune,
                        'latitude' => $station->latitude,
                        'longitude' => $station->longitude,
                        'visits_count' => $station->visits_count,
                        // tous les statuts disponibles
                        'fuel_statuses' => $station->fuelTypes->map(function ($fuelType) use ($station) {
                            $status = $station->statuses->firstWhere('fuel_type_id', $fuelType->id);

                            return [
                                'fuel_type' => $fuelType->name,
                                'status' => $status?->status ?? 'inconnu',
                                'updated_at' => $status?->created_at ?? null,
                            ];
                        }),
                    ];
                });
        });
        
        $this->bustStationCaches(); // pour tout vider

        return response()->json($stations);
    }


    public function show($id)
    {
        $cacheKey = "station.$id";

        $station = Cache::remember($cacheKey, 3600, function () use ($id) {
            return Station::with(['fuelTypes', 'statuses.fuelType'])
                ->findOrFail($id);
        });

        // 🔥 On reformate la sortie pour avoir les statuts par carburant
        $formattedStation = [
            'id' => $station->id,
            'name' => $station->name,
            'commune' => $station->commune,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'status' => $station->status,
            'fuel_statuses' => $station->fuelTypes->map(function ($fuelType) use ($station) {
                $status = $station->statuses->firstWhere('fuel_type_id', $fuelType->id);

                return [
                    'fuel_type' => $fuelType->name,
                    'status' => $status?->status ?? 'inconnu',
                    'updated_at' => $status?->created_at ?? null,
                ];
            }),
            'created_at' => $station->created_at,
            'updated_at' => $station->updated_at,
        ];

        // Enregistre la visite (si elle n’existe pas déjà aujourd’hui)
        StationVisit::firstOrCreate([
            'station_id' => $station->id,
            'ip_address' => request()->ip(),
        ], [
            'device' => request()->header('User-Agent'),
            'commune' => $station->commune,
        ]);

        // On invalide juste le cache analytics pas la station elle-même
        Cache::forget('stations.analytics');

        return response()->json([
            'message' => 'Détails de la station récupérés avec succès.',
            'data' => $formattedStation,
        ]);
    }

}
