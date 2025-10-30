<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;

class ReportController extends Controller
{
    // Liste des rapports
    public function index()
    {
        // Logique pour récupérer et afficher les rapports
        $reports = Report::with('station:id,name')->get();

        return view('admin.reports.index', compact('reports'));
    }
}
