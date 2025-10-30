<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StationAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $station = Station::where('email', $credentials['email'])->first();

        if (!$station || !Hash::check($credentials['password'], $station->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        if ($station->status !== 'approved') {
            return response()->json(['message' => 'Compte non approuvé par l’administrateur'], 403);
        }

        $token = $station->createToken('station_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'station' => $station,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}

