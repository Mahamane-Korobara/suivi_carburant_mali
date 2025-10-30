<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StationRequestController extends Controller
{
    public function index(Request $request)
    {
        $stations = Station::with('lastStatus')
            ->when($request->has('commune'), fn($q) => $q->where('commune', $request->commune))
            ->when($request->has('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'))
            ->get()
            ->map(function ($station) {
                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'commune' => $station->commune,
                    'status' => $station->lastStatus?->status ?? 'inconnu',
                    'updated_at' => $station->lastStatus?->created_at ?? $station->updated_at,
                    'is_active' => $station->status === 'approved',
                ];
            });

        return response()->json($stations);
    }

    public function history($id)
    {
        $station = Station::with('statuses')->findOrFail($id);
        return response()->json([
            'station' => $station->only(['id', 'name', 'commune']),
            'history' => $station->statuses()->orderByDesc('created_at')->get(),
        ]);
    }

    public function disable($id)
    {
        $station = Station::findOrFail($id);

        if ($station->status !== 'approved') {
            return response()->json(['message' => 'Seules les stations approuvées peuvent être désactivées.'], 400);
        }

        // On rend le mot de passe inutilisable
        $station->update([
            'status' => 'rejected',
            'rejection_reason' => 'Station désactivée par administrateur',
            'password' => null,
        ]);

        // Envoi de mail de désactivation
        Mail::raw("Votre compte a été désactivé par l'administrateur.\nVous ne pouvez plus accéder à la plateforme pour le moment.", function ($message) use ($station) {
            $message->to($station->email)
                    ->subject('Votre compte a été désactivé');
        });

        return response()->json(['message' => 'Station désactivée avec succès.']);
    }

    public function reactivate($id)
    {
        $station = Station::findOrFail($id);

        if ($station->status !== 'rejected') {
            return response()->json(['message' => 'Seules les stations désactivées peuvent être réactivées.'], 400);
        }

        // Nouveau mot de passe
        $newPassword = 'station' . rand(1000, 9999);

        $station->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'password' => Hash::make($newPassword),
        ]);

        // Envoi du mail de réactivation
        Mail::raw("Votre compte a été réactivé.\nVoici vos nouveaux identifiants :\nEmail : {$station->email}\nMot de passe : {$newPassword}", function ($message) use ($station) {
            $message->to($station->email)
                    ->subject('Réactivation de votre compte station');
        });

        return response()->json(['message' => 'Station réactivée avec succès.']);
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
