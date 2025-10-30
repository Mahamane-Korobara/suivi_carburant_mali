<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\StationVisit;
use Carbon\Carbon;

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
                    'type' => $station->type,
                    'status' => $station->lastStatus?->status ?? 'inconnu',
                    'updated_at' => $station->lastStatus?->created_at ?? $station->updated_at,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'is_active' => $station->status === 'approved',
                ];
            });

        return response()->json($stations);
    }

    public function stats()
    {
        $total = Station::count();
        $approved = Station::where('status', 'approved')->count();
        $rejected = Station::where('status', 'rejected')->count();
        $pending = Station::where('status', 'pending')->count();

        return response()->json([
            'total' => $total,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'last_update' => Station::max('updated_at'),
        ]);
    }

    public function analytics(Request $request)
    {
        $start = Carbon::now()->subDays(7); // 7 derniers jours

        $stats = [
            'most_viewed' => StationVisit::selectRaw('station_id, COUNT(*) as total')
                ->where('visited_at', '>=', $start)
                ->groupBy('station_id')
                ->orderByDesc('total')
                ->with('station:id,name,commune,quartier')
                ->take(5)
                ->get(),

            'visits_per_hour' => StationVisit::selectRaw('HOUR(visited_at) as hour, COUNT(*) as total')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),

            'visits_per_commune' => StationVisit::selectRaw('commune, COUNT(*) as total')
                ->groupBy('commune')
                ->orderByDesc('total')
                ->get(),

            'visits_per_quartier' => StationVisit::selectRaw('quartier, COUNT(*) as total')
                ->groupBy('quartier')
                ->orderByDesc('total')
                ->get(),
        ];

        return response()->json($stats);
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
