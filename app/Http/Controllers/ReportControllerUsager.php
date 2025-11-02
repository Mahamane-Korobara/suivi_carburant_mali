<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportControllerUsager extends Controller
{
    /**
     * Envoyer un signalement pour une station
     */
    public function store(Request $request, $stationId)
    {
        // Validation simple
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:incident,erreur,autre',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifie que la station existe
        $station = Station::find($stationId);
        if (!$station) {
            return response()->json(['message' => 'Station introuvable.'], 404);
        }

        // Crée le rapport
        $report = Report::create([
            'station_id' => $station->id,
            'type' => $request->type,
            'message' => $request->message,
        ]);

        return response()->json([
            'message' => 'Signalement envoyé avec succès. Merci pour votre contribution 🙏',
            'data' => $report,
        ], 201);
    }
}
