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
        $search = $request->get('search');
        $fuelFilter = $request->get('fuel');
        $statusFilter = $request->get('status');

        // Clé de cache dynamique
        $cacheKey = "stations.index.$sort.$order.$search.$fuelFilter.$statusFilter";

        $stations = Cache::remember($cacheKey, 3600, function () use ($sort, $order, $search, $fuelFilter, $statusFilter) {
            $query = Station::with(['statuses.fuelType', 'fuelTypes'])
                ->where('status', 'approved')
                ->withCount('visits');

            // Recherche nom/quartier
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('quartier', 'like', "%$search%");
                });
            }

            // Filtre carburant + statut
            if ($fuelFilter && $statusFilter) {
                $query->whereHas('statuses', function ($q) use ($fuelFilter, $statusFilter) {
                    $q->whereHas('fuelType', function ($f) use ($fuelFilter) {
                        $f->where('name', $fuelFilter);
                    })->where('status', $statusFilter);
                });
            }

            // Tri
            $stations = $query
                ->orderBy($sort === 'visits' ? 'visits_count' : $sort, $order)
                ->get();

            // Formatage final
            return $stations->map(function ($station) {
                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'quartier' => $station->quartier,
                    'commune' => $station->commune,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'visits_count' => $station->visits_count,
                    'fuel_statuses' => $station->fuelTypes->map(function ($fuelType) use ($station) {
                        $status = $station->statuses->firstWhere('fuel_type_id', $fuelType->id);
                        $color = match ($status?->status) {
                            'disponible' => 'green',
                            'peu' => 'orange',
                            'rupture' => 'red',
                            default => 'gray',
                        };

                        return [
                            'fuel_type' => $fuelType->name,
                            'status' => $status?->status ?? 'inconnu',
                            'color' => $color,
                            'updated_at' => $status?->created_at ?? null,
                        ];
                    }),
                ];
            });
        });

        return response()->json($stations);
    }

    public function show($id)
    {
        $cacheKey = "station.$id";

        $station = Cache::remember($cacheKey, 3600, function () use ($id) {
            return Station::with(['fuelTypes', 'statuses.fuelType'])
                ->findOrFail($id);
        });

        // On reformate la sortie pour avoir les statuts par carburant
        $formattedStation = [
            'id' => $station->id,
            'name' => $station->name,
            'commune' => $station->commune,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'status' => $station->status,
            'fuel_statuses' => $station->fuelTypes->map(function ($fuelType) use ($station) {
                $status = $station->statuses->firstWhere('fuel_type_id', $fuelType->id);
                $color = match ($status?->status) {
                    'disponible' => 'green',
                    'peu' => 'orange',
                    'rupture' => 'red',
                    default => 'gray',
                };

                return [
                    'fuel_type' => $fuelType->name,
                    'status' => $status?->status ?? 'inconnu',
                    'color' => $color,
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
