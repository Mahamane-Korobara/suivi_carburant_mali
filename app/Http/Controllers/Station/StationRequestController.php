<?php

namespace App\Http\Controllers\Station;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\StationHelper;
use App\Models\FuelType;
use App\Models\StationStatus;
use App\Models\StationStatusHistory;
use Carbon\Carbon;

class StationRequestController extends Controller
{
    use StationHelper;

    /**
     * Récupère les types de carburant avec leurs statuts actuels
     */
    public function getFuelStatuses()
    {
        $station = auth('station')->user();
        if (!$station) {
            return response()->json(['message' => 'Station non authentifiée'], 401);
        }

        $fuelTypes = $station->fuelTypes;

        $statuses = $fuelTypes->map(function($fuelType) use ($station) {
            $current = StationStatus::where([
                'station_id' => $station->id,
                'fuel_type_id' => $fuelType->id
            ])->first();

            return [
                'id' => $fuelType->id,
                'type' => $fuelType->name,
                'status' => $current ? $current->status : null,
                'updated_at' => $current ? $current->updated_at : null
            ];
        });

        return response()->json([
            'message' => 'Statuts récupérés avec succès',
            'data' => $statuses
        ]);
    }

    /**
     * Met à jour le statut d'un type de carburant pour la station authentifiée
     */
    public function updateFuelStatus(Request $request)
    {
        $request->validate([
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'status' => 'required|in:disponible,peu,rupture',
        ]);

        $station = auth('station')->user();
        if (!$station) {
            return response()->json(['message' => 'Station non authentifiée'], 401);
        }

        // Récupérer l'ancien statut actuel
        $oldStatus = StationStatus::where([
            'station_id' => $station->id,
            'fuel_type_id' => $request->fuel_type_id
        ])->first();

        // Mettre à jour ou créer le statut actuel
        $currentStatus = StationStatus::updateOrCreate(
            [
                'station_id' => $station->id,
                'fuel_type_id' => $request->fuel_type_id
            ],
            ['status' => $request->status]
        );

        // Créer un enregistrement dans l'historique
        StationStatusHistory::create([
            'station_id' => $station->id,
            'fuel_type_id' => $request->fuel_type_id,
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => [
                'fuel_type_id' => $request->fuel_type_id,
                'old_status' => $oldStatus ? $oldStatus->status : null,
                'new_status' => $currentStatus->status,
                'updated_at' => $currentStatus->updated_at->format('d/m/Y à H:i')
            ]
        ]);
    }

    /**
     * Récupère l'historique des changements de statut de la dernière semaine
     */
    public function getFuelHistory()
{
    $station = auth('station')->user();
    if (!$station) return response()->json(['message' => 'Station non authentifiée'], 401);

    $history = StationStatusHistory::with('fuelType')
        ->where('station_id', $station->id)
        ->where('created_at', '>=', Carbon::now()->subWeek())
        ->orderByDesc('created_at')
        ->get();

    // Calcul de l'ancien statut pour chaque élément
    $historyData = $history->map(function($h) use ($station) {
        $previousStatus = StationStatusHistory::where('station_id', $station->id)
            ->where('fuel_type_id', $h->fuel_type_id)
            ->where('created_at', '<', $h->created_at)
            ->orderByDesc('created_at')
            ->value('status'); // null si aucun

        return [
            'id' => $h->id,
            'fuel_type' => $h->fuelType->name,
            'old_status' => $previousStatus,
            'status' => $h->status,
            'created_at' => $h->created_at->format('d/m/Y à H:i')
        ];
    });

    return response()->json([
        'message' => 'Historique récupéré avec succès',
        'data' => $historyData
    ]);
}


    /**
     * Récupère le précédent statut d'un carburant pour la station
     */
    private function getPreviousStatus($currentStatus)
    {
        return $currentStatus->station->statuses()
            ->where('fuel_type_id', $currentStatus->fuel_type_id)
            ->where('created_at', '<', $currentStatus->created_at)
            ->orderByDesc('created_at')
            ->value('status'); // Retourne null si aucun
    }
}
