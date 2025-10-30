<?php

namespace App\Exports;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportsSheet implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Report::with('station:id,name')->get()->map(fn($report) => [
            'Station' => $report->station?->name,
            'Type' => $report->type,
            'Message' => $report->message,
            'Date / Heure' => $report->created_at->format('d/m/Y H:i'),
        ]);
    }

    public function headings(): array
    {
        return ['Station', 'Type', 'Message', 'Date / Heure'];
    }
}
