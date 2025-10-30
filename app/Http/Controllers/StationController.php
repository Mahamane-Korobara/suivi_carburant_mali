<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'quartier' => 'required|string|max:255',
            'commune' => 'required|string|max:255',
            'gerant_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:stations,email',
            'type' => 'required|string|max:255',
        ]);

        $validated['status'] = 'pending';
        $station = Station::create($validated);

        return response()->json([
            'message' => 'Demande envoyée avec succès. En attente de validation.',
            'data' => $station,
        ], 201);
    }
}
