<?php

namespace App\Http\Controllers\Station;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\StationHelper;
class StationRequestController extends Controller
{
    use StationHelper;
    /**
     * Met à jour le statut d’un type de carburant pour la station authentifiée
     */
    public function updateFuelStatus(Request $request)
    {
        $request->validate([
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'status' => 'required|in:disponible,peu,rupture',
        ]);

        // Récupération de la station connectée via le guard "station"
        /** @var \App\Models\Station $station */
        $station = auth('station')->user();

        if (!$station) {
            return response()->json(['message' => 'Station non authentifiée'], 401);
        }

        // Mise à jour ou création du statut de carburant
        $status = $station->statuses()->updateOrCreate(
            ['fuel_type_id' => $request->fuel_type_id],
            ['status' => $request->status]
        );


        $this->bustStationCaches($station->id);

        return response()->json([
            'message' => 'Statut du carburant mis à jour avec succès.',
            'data' => $status,
        ]);
    }
}
