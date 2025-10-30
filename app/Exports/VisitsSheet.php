<?php

namespace App\Exports;

use App\Models\StationVisit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VisitsSheet implements FromCollection, WithHeadings
{
    public function collection()
    {
        $visits = StationVisit::with('station:id,name,commune,quartier')
            ->orderByDesc('visited_at')
            ->get()
            ->map(fn($visit) => [
                'Station' => $visit->station?->name,
                'Commune' => $visit->commune,
                'Quartier' => $visit->quartier,
                'Date / Heure' => $visit->visited_at instanceof \Carbon\Carbon 
                ? $visit->visited_at->format('d/m/Y H:i') 
                : $visit->visited_at,
                'IP' => $visit->ip_address,
                'Device' => $visit->device,
            ]);

        // Analytics
        $start = now()->subDays(7);

        $mostViewed = StationVisit::selectRaw('station_id, COUNT(*) as total')
            ->where('visited_at', '>=', $start)
            ->groupBy('station_id')
            ->with('station:id,name,commune,quartier')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($v) => [
                'Station' => $v->station?->name . ' (Most viewed)',
                'Commune' => $v->station?->commune,
                'Quartier' => $v->station?->quartier,
                'Date / Heure' => '',
                'IP' => $v->total,
                'Device' => '',
            ]);

        return $visits->concat($mostViewed);
    }


    public function headings(): array
    {
        return ['Station', 'Commune', 'Quartier', 'Date / Heure', 'IP', 'Device'];
    }
}
