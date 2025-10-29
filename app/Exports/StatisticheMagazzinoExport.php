<?php

namespace App\Exports;

use App\Models\Articolo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StatisticheMagazzinoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $filtri;
    protected $statistiche;
    
    public function __construct($filtri = [], $statistiche = [])
    {
        $this->filtri = $filtri;
        $this->statistiche = $statistiche;
    }

    public function collection()
    {
        $query = Articolo::with([
            'giacenza',
            'categoriaMerceologica',
            'sede',
            'fatturaDettaglio.fattura.fornitore'
        ])
        ->whereHas('giacenza', function ($q) {
            if ($this->filtri['soloGiacenti'] ?? true) {
                $q->where('quantita_residua', '>', 0);
            }
        });

        if (!empty($this->filtri['sedeId'])) {
            $query->whereHas('giacenza', function ($q) {
                $q->where('sede_id', $this->filtri['sedeId']);
            });
        }

        if (!empty($this->filtri['categoriaId'])) {
            $query->where('categoria_merceologica_id', $this->filtri['categoriaId']);
        }

        if (!empty($this->filtri['fornitoreId'])) {
            $query->whereHas('fatturaDettaglio.fattura', function ($q) {
                $q->where('fornitore_id', $this->filtri['fornitoreId']);
            });
        }

        if (!empty($this->filtri['search'])) {
            $query->where(function ($q) {
                $q->where('codice', 'like', '%' . $this->filtri['search'] . '%')
                  ->orWhere('descrizione', 'like', '%' . $this->filtri['search'] . '%');
            });
        }

        if ($this->filtri['soloSenzaCosto'] ?? false) {
            $query->where(function ($q) {
                $q->whereNull('prezzo_acquisto')
                  ->orWhere('prezzo_acquisto', 0);
            });
        }

        return $query->orderBy('codice')->get();
    }

    public function headings(): array
    {
        return [
            'Codice',
            'Descrizione',
            'Sede',
            'Categoria',
            'Fornitore',
            'Quantità',
            'Costo Unit.',
            'Valore Totale',
            'Data Carico',
        ];
    }

    public function map($articolo): array
    {
        $qta = $articolo->giacenza->quantita_residua ?? 0;
        $costo = $articolo->prezzo_acquisto ?? 0;
        $valore = $qta * $costo;
        $fornitore = $articolo->fatturaDettaglio->first()?->fattura?->fornitore;

        return [
            $articolo->codice,
            $articolo->descrizione,
            $articolo->giacenza->sede->nome ?? 'N/A',
            $articolo->categoriaMerceologica->nome ?? 'N/A',
            $fornitore->ragione_sociale ?? 'N/A',
            $qta,
            $costo > 0 ? number_format($costo, 2, ',', '.') : '-',
            $valore > 0 ? number_format($valore, 2, ',', '.') : '-',
            $articolo->data_carico ? $articolo->data_carico->format('d/m/Y') : '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Codice
            'B' => 40,  // Descrizione
            'C' => 15,  // Sede
            'D' => 20,  // Categoria
            'E' => 30,  // Fornitore
            'F' => 10,  // Quantità
            'G' => 12,  // Costo Unit.
            'H' => 15,  // Valore Totale
            'I' => 12,  // Data Carico
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
