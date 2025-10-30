<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StationRequestController extends Controller
{
    public function index()
    {
        $stations = Station::orderByDesc('created_at')->get();
        return response()->json($stations);
    }

    public function approve($id)
    {
        $station = Station::findOrFail($id);

        if ($station->status !== 'pending') {
            return response()->json(['message' => 'Demande déjà traitée'], 400);
        }

        $password = 'station' . rand(1000, 9999);

        $station->update([
            'status' => 'approved',
            'password' => Hash::make($password),
        ]);

        // Envoi de l’email d’approbation
        Mail::raw("Votre compte a été approuvé.\nEmail : {$station->email}\nMot de passe : {$password}", function ($message) use ($station) {
            $message->to($station->email)
                    ->subject('Approbation de votre compte station');
        });

        return response()->json(['message' => 'Station approuvée avec succès.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $station = Station::findOrFail($id);

        $station->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        Mail::raw("Votre demande a été refusée.\nRaison : {$request->reason}", function ($message) use ($station) {
            $message->to($station->email)
                    ->subject('Refus de votre demande');
        });

        return response()->json(['message' => 'Station refusée avec succès.']);
    }
}
