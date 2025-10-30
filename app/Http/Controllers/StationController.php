<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Models\Station;

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
}
