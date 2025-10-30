<?php

namespace App\Exports;

use App\Models\Station;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StationsExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $stations = Station::query()
            ->when(isset($this->filters['commune']), fn($q) => $q->where('commune', $this->filters['commune']))
            ->when(isset($this->filters['status']), fn($q) => $q->where('status', $this->filters['status']))
            ->with('lastStatus') // optionnel si tu veux récupérer la relation
            ->orderBy('updated_at', 'desc')
            ->get();

        // Transformation pour Excel
        return $stations->map(fn($station) => [
            'Nom de la station' => $station->name,
            'Commune' => $station->commune,
            'Type carburant' => $station->type,
            'Statut' => $station->lastStatus?->status ?? 'inconnu',
            'Dernière mise à jour' => $station->lastStatus?->created_at?->format('d/m/Y H:i') ?? $station->updated_at->format('d/m/Y H:i'),
        ]);
    }

    public function headings(): array
    {
        return [
            'Nom de la station',
            'Commune',
            'Type carburant',
            'Statut',
            'Dernière mise à jour',
        ];
    }
}
