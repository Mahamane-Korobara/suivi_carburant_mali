<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Station;
use App\Models\StationNotification;
use Carbon\Carbon;

class SendStationReminders extends Command
{
    protected $signature = 'stations:send-reminders';
    protected $description = 'Envoyer des notifications aux stations qui n’ont pas mis à jour leur statut depuis 48h';

    public function handle()
    {
        $now = Carbon::now();

        $stations = Station::with('statuses', 'notifications')->get();

        foreach ($stations as $station) {
            // Dernière mise à jour des statuts
            $lastUpdate = $station->statuses->max('created_at');

            // On vérifie si la station a déjà reçu ce type de rappel dans les dernières 48h
            $alreadyNotified = $station->notifications()
                ->where('title', 'Rappel de mise à jour')
                ->where('created_at', '>=', $now->subHours(48))
                ->exists();

            if ((!$lastUpdate || $now->diffInHours($lastUpdate) >= 48) && !$alreadyNotified) {
                StationNotification::create([
                    'station_id' => $station->id,
                    'title' => 'Rappel de mise à jour',
                    'message' => 'Vous n’avez pas mis à jour vos statuts de carburant depuis plus de 48 heures.',
                ]);

                $this->info("Notification envoyée à la station ID {$station->id}");
            }
        }

        $this->info('Rappels vérifiés et envoyés avec succès.');
    }
}
