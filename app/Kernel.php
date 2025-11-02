<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Les commandes Artisan fournies par l’application.
     *
     * @var array<int, class-string|string>
     */
    protected $commands = [
        // Ici tu listes tes commandes personnalisées
        \App\Console\Commands\SendStationReminders::class,
    ];

    /**
     * Définir le planning des commandes.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Exécute la commande de rappel tous les jours à 8h
        $schedule->command('stations:send-reminders')->dailyAt('08:00');
    }

    /**
     * Enregistrer les commandes Artisan.
     */
    protected function commands(): void
    {
        // Charge toutes les commandes dans le dossier app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
