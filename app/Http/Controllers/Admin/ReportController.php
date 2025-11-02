<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Liste des signalements reçus (avec filtres + recherche)
     */
    public function index(Request $request)
    {
        $query = Report::with('station:id,name,commune')->latest();


        // Recherche par message ou nom de station
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%$search%")
                  ->orWhereHas('station', fn($s) => $s->where('name', 'like', "%$search%"));
            });
        }

        $reports = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Liste des signalements récupérée avec succès',
            'data' => $reports
        ]);
    }

    /**
     * Détail d’un signalement
     */
    public function show($id)
    {
    
        $report = Report::with('station:id,name,commune')->find($id);
        
        
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Signalement introuvable'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Supprimer un signalement
     */
    public function destroy($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Signalement introuvable'
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signalement supprimé avec succès'
        ]);
    }
}
