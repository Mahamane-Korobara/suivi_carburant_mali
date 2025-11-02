<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Station;
use Illuminate\Http\Request;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Validator;

class ReportControllerUsager extends Controller
{
    /**
     * Envoyer un signalement pour une station
     */

    public function store(Request $request, $stationId)
    {
        // Validation et création du report (comme avant)...
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

        $station = Station::find($stationId);
        if (!$station) {
            return response()->json(['message' => 'Station introuvable.'], 404);
        }

        $report = Report::create([
            'station_id' => $station->id,
            'type' => $request->type,
            'message' => $request->message,
        ]);

        // Vérifier si >= 5 reports pour cette station
        $reportCount = Report::where('station_id', $stationId)->count();

        if ($reportCount === 5) { // exactement 5, pour ne pas envoyer plusieurs fois
            AdminNotification::create([
                'title' => 'Station signalée plusieurs fois',
                'message' => "La station '{$station->name}' (ID: {$station->id}) a été signalée {$reportCount} fois. Veuillez prendre des dispositions.",
            ]);
        }

        return response()->json([
            'message' => 'Signalement envoyé avec succès. Merci pour votre contribution 🙏',
            'data' => $report,
        ], 201);
    }

}
