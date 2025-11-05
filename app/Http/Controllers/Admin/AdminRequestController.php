<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\StationStatus;
use App\Models\FuelType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\StationVisit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Helpers\StationHelper;

class AdminRequestController extends Controller
{
    use StationHelper;
    public function index(Request $request)
    {
        $cacheKey = 'stations_list_' . md5(json_encode($request->all()));

        $stations = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Station::with(['statuses.fuelType']) // <-- relation vers la table pivot + fuelType
                // filtre base déjà présent
                ->when($request->has('commune'), fn($q) => $q->where('commune', $request->commune))
                ->when($request->has('status'), function ($q) use ($request) {
                    // accepte status=approved ou status[]=pending&status[]=rejected
                    $statuses = $request->status;
                    if (is_array($statuses)) {
                        $q->whereIn('status', $statuses);
                    } else {
                        $q->where('status', $statuses);
                    }
                })
                // tri de base (gardé)
                ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));

            // --- FILTRES AVANCÉS ---

            // Recherche libre (name OR quartier)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('quartier', 'like', "%{$search}%");
                });
            }

            // Filtre exact par quartier (si besoin en plus de commune)
            if ($request->filled('quartier')) {
                $query->where('quartier', $request->quartier);
            }

            // Filtre par plage de visites (requiert withCount('visits') après)
            if ($request->filled('visits_min') || $request->filled('visits_max')) {
                $min = $request->get('visits_min');
                $max = $request->get('visits_max');

                $query->withCount('visits');

                if ($min !== null) {
                    $query->having('visits_count', '>=', (int)$min);
                }
                if ($max !== null) {
                    $query->having('visits_count', '<=', (int)$max);
                }
            }

            // Filtre par type de carburant disponible (fuel peut être name ou id)
            if ($request->filled('fuel') && $request->filled('status_filter')) {
                $fuel = $request->fuel; // ex: "Essence" ou id
                $statusFilter = $request->status_filter; // ex: 'disponible'
                $query->whereHas('statuses', function ($q) use ($fuel, $statusFilter) {
                    if (is_numeric($fuel)) {
                        $q->where('fuel_type_id', $fuel)
                        ->where('status', $statusFilter);
                    } else {
                        $q->whereHas('fuelType', function ($f) use ($fuel) {
                            $f->where('name', $fuel);
                        })->where('status', $statusFilter);
                    }
                });
            }

            // Filtre par date de dernière mise à jour (updated_at)
            if ($request->filled('updated_from')) {
                $query->where('updated_at', '>=', $request->updated_from);
            }
            if ($request->filled('updated_to')) {
                $query->where('updated_at', '<=', $request->updated_to);
            }

            // --- FIN FILTRES AVANCÉS ---

            $stations = $query->get();

            return $stations->map(function ($station) {
                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'commune' => $station->commune,
                    'latitude' => $station->latitude,
                    'longitude' => $station->longitude,
                    'is_active' => $station->status === 'approved', // affichage seulement
                    'updated_at' => $station->updated_at,
                    // Tous les statuts par carburant
                    'fuel_statuses' => $station->statuses->map(function ($s) {
                        return [
                            'fuel_type' => $s->fuelType->name,
                            'status' => $s->status,
                            'updated_at' => $s->created_at,
                        ];
                    }),
                ];
            });
        });

        return response()->json($stations);
    }

    public function stats()
    {
        $cacheKey = 'stations_stats';

        $stats = Cache::remember($cacheKey, 300, function () {
            return [
                // Stats de base
                'total' => Station::count(),
                'approved' => Station::where('status', 'approved')->count(),
                'rejected' => Station::where('status', 'rejected')->count(),
                'pending' => Station::where('status', 'pending')->count(),
                'last_update' => Station::max('updated_at'),
                
                // Tendances temporelles
                'new_this_week' => Station::where('created_at', '>=', Carbon::now()->subWeek())->count(),
                'new_this_month' => Station::where('created_at', '>=', Carbon::now()->subMonth())->count(),
                'approved_this_week' => Station::where('status', 'approved')
                    ->where('updated_at', '>=', Carbon::now()->subWeek())
                    ->count(),
                
                // Taux d'approbation
                'approval_rate' => Station::whereIn('status', ['approved', 'rejected'])->count() > 0
                    ? round((Station::where('status', 'approved')->count() / 
                        Station::whereIn('status', ['approved', 'rejected'])->count()) * 100, 1)
                    : 0,
                
                // Répartition géographique (top 5 communes)
                'top_communes' => Station::selectRaw('commune, COUNT(*) as total')
                    ->where('status', 'approved')
                    ->groupBy('commune')
                    ->orderByDesc('total')
                    ->take(5)
                    ->get(),
                
                // Interactions utilisateurs
                'total_visits' => StationVisit::count(),
                'visits_today' => StationVisit::whereDate('visited_at', Carbon::today())->count(),
                'visits_this_week' => StationVisit::where('visited_at', '>=', Carbon::now()->subWeek())->count(),
            ];
        });

        return response()->json($stats);
    }

    public function fuelStats()
    {
        $cacheKey = 'fuel_stats';

        $stats = Cache::remember($cacheKey, 300, function () {
            return [
                // Stats globales sur les statuts de carburant
                'total_fuel_points' => StationStatus::count(),
                'available' => StationStatus::where('status', 'disponible')->count(),
                'out_of_stock' => StationStatus::where('status', 'rupture')->count(),
                'limited' => StationStatus::where('status', 'peu')->count(),
                
                // Par type de carburant
                'by_fuel_type' => FuelType::withCount([
                    'statuses as available_count' => function ($q) {
                        $q->where('status', 'disponible');
                    },
                    'statuses as out_of_stock_count' => function ($q) {
                        $q->where('status', 'rupture');
                    },
                    'statuses as limited_count' => function ($q) {
                        $q->where('status', 'peu');
                    },
                    'statuses as total_count'
                ])->get()->map(function ($fuel) {
                    return [
                        'id' => $fuel->id,
                        'name' => $fuel->name,
                        'available' => $fuel->available_count,
                        'out_of_stock' => $fuel->out_of_stock_count,
                        'limited' => $fuel->limited_count ?? 0,
                        'total' => $fuel->total_count,
                        'availability_rate' => $fuel->total_count > 0 
                            ? round(($fuel->available_count / $fuel->total_count) * 100, 1)
                            : 0,
                    ];
                }),
                
                // Dernières mises à jour de statuts
                'recent_updates' => StationStatus::with(['station:id,name,commune', 'fuelType:id,name'])
                    ->orderByDesc('updated_at')
                    ->take(10)
                    ->get()
                    ->map(function ($status) {
                        return [
                            'station' => $status->station->name,
                            'commune' => $status->station->commune,
                            'fuel' => $status->fuelType->name,
                            'status' => $status->status,
                            'updated_at' => $status->updated_at->diffForHumans(),
                        ];
                    }),
            ];
        });

        return response()->json($stats);
    }

    public function analytics(Request $request)
    {
        $cacheKey = 'stations_analytics';

        $stats = Cache::remember($cacheKey, 300, function () {
            return [
                'most_viewed' => StationVisit::selectRaw('station_id, COUNT(*) as total')
                    ->where('visited_at', '>=', Carbon::now()->subDays(7))
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
        });

        return response()->json($stats);
    }

    public function history($id)
    {
        $cacheKey = "station_history_{$id}";

        $data = Cache::remember($cacheKey, 600, function () use ($id) {
            $station = Station::with('statuses')
                ->findOrFail($id);

            return [
                'station' => $station->only(['id', 'name', 'commune']),
                'history' => $station->statuses()
                    ->orderByDesc('created_at')
                    ->get(),
            ];
        });

        return response()->json($data);
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

        $this->bustStationCaches($station->id);
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

        $this->bustStationCaches($station->id);
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

        $this->bustStationCaches($station->id);
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

        $this->bustStationCaches($station->id);
        return response()->json(['message' => 'Station refusée avec succès.']);
    }
}
