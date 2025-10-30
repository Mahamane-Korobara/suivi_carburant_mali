<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Models\Station;
use App\Models\StationVisit;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function register(StoreStationRequest $request)
    {
        // Récupération automatique des données validées
        $validated = $request->validated();

        // Ajouter le statut par défaut
        $validated['status'] = 'pending';

        // Création de la station
        $station = Station::create($validated);

        return response()->json([
            'message' => 'Demande envoyée avec succès. En attente de validation.',
            'data' => $station,
        ], 201);
    }

    // Consultation des stations
   public function index(Request $request)
    {
        $sort = $request->get('sort', 'created_at'); // tri par défaut : date
        $order = $request->get('order', 'desc');     // ordre par défaut : décroissant

        $stations = Station::withCount('visits') // ajoute visits_count automatiquement
            ->where('status', 'approved')        // seules les stations validées
            ->orderBy($sort === 'visits' ? 'visits_count' : $sort, $order)
            ->get();

        return response()->json($stations);
    }


    public function show($id)
    {
        $station = Station::findOrFail($id);

        // Enregistre la visite
        StationVisit::create([
            'station_id' => $station->id,
            'ip_address' => request()->ip(),
            'device' => request()->header('User-Agent'),
            'commune' => $station->commune,
        ]);

        return response()->json($station);
    }
}
