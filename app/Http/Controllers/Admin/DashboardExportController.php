<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\DashboardExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class DashboardExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['commune', 'status']);
            
            // Test des sheets individuellement
            // Log::info('Début export');
            
            // $stationsSheet = new \App\Exports\StationsSheet($filters);
            // Log::info('StationsSheet OK - ' . $stationsSheet->collection()->count() . ' lignes');
            
            // $visitsSheet = new \App\Exports\VisitsSheet();
            // Log::info('VisitsSheet OK - ' . $visitsSheet->collection()->count() . ' lignes');
            
            // $reportsSheet = new \App\Exports\ReportsSheet();
            // Log::info('ReportsSheet OK - ' . $reportsSheet->collection()->count() . ' lignes');
            
            return Excel::download(new DashboardExport($filters), 'stations_export.xlsx');
            
        } catch (\Exception $e) {
            Log::error('Erreur export: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}