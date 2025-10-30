<?php

namespace App\Exports;

use App\Models\Station;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StationsSheet implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $stations = Station::query()
            ->when(isset($this->filters['commune']), fn($q) => $q->where('commune', $this->filters['commune']))
            ->when(isset($this->filters['status']), fn($q) => $q->where('status', $this->filters['status']))
            ->with('lastStatus')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $stations->map(fn($station) => [
            'Nom de la station' => $station->name,
            'Commune' => $station->commune,
            'Quartier' => $station->quartier,
            'Type carburant' => $station->type,
            'Statut' => $station->lastStatus?->status ?? 'inconnu',
            'Dernière mise à jour' => $station->lastStatus?->created_at?->format('d/m/Y H:i') ?? $station->updated_at->format('d/m/Y H:i'),
        ]);
    }

    public function headings(): array
    {
        return ['Nom de la station', 'Commune', 'Quartier', 'Type carburant', 'Statut', 'Dernière mise à jour'];
    }
}
