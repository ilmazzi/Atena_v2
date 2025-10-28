<?php

namespace App\Http\Livewire;

use App\Models\ArticoloStorico;
use App\Models\InventarioSessione;
use App\Models\Sede;
use App\Services\InventarioService;
use Livewire\Component;
use Livewire\WithPagination;

class StoricoArticoli extends Component
{
    use WithPagination;

    public $filtroSede = '';
    public $filtroMotivo = '';
    public $filtroDataInizio = '';
    public $filtroDataFine = '';
    public $filtroCodice = '';
    public $sedi = [];
    public $sessioni = [];
    public $articoloSelezionato = null;
    public $showModal = false;
    public $articoliSelezionati = [];
    public $selezionaTutti = false;
    public $showModalRipristinoMultiplo = false;

    public function mount()
    {
        $this->sedi = Sede::all();
        $this->sessioni = InventarioSessione::with('sede')->get();
    }

    public function selezionaArticolo($articoloId)
    {
        $this->articoloSelezionato = ArticoloStorico::with(['sessioneInventario', 'utente'])
            ->find($articoloId);
        $this->showModal = true;
    }

    public function chiudiModal()
    {
        $this->showModal = false;
        $this->articoloSelezionato = null;
    }

    public function toggleArticolo($articoloId)
    {
        if (in_array($articoloId, $this->articoliSelezionati)) {
            $this->articoliSelezionati = array_diff($this->articoliSelezionati, [$articoloId]);
        } else {
            $this->articoliSelezionati[] = $articoloId;
        }
        $this->updateSelezionaTuttiState();
    }

    public function toggleSelezionaTutti()
    {
        if ($this->selezionaTutti) {
            $this->articoliSelezionati = [];
        } else {
            $articoli = $this->getArticoliQuery()->get();
            $this->articoliSelezionati = $articoli->pluck('id')->toArray();
        }
        $this->selezionaTutti = !$this->selezionaTutti;
    }

    private function updateSelezionaTuttiState()
    {
        $articoli = $this->getArticoliQuery()->get();
        $this->selezionaTutti = count($this->articoliSelezionati) === $articoli->count() && $articoli->count() > 0;
    }

    public function apriModalRipristinoMultiplo()
    {
        if (empty($this->articoliSelezionati)) {
            session()->flash('error', 'Seleziona almeno un articolo da ripristinare.');
            return;
        }
        $this->showModalRipristinoMultiplo = true;
    }

    public function chiudiModalRipristinoMultiplo()
    {
        $this->showModalRipristinoMultiplo = false;
    }

    public function confermaRipristinoMultiplo()
    {
        if (empty($this->articoliSelezionati)) {
            session()->flash('error', 'Nessun articolo selezionato.');
            return;
        }

        // Limita il numero di articoli da ripristinare per evitare timeout
        $maxArticoli = 100;
        if (count($this->articoliSelezionati) > $maxArticoli) {
            session()->flash('error', "Troppi articoli selezionati. Massimo {$maxArticoli} alla volta. Hai selezionato " . count($this->articoliSelezionati) . " articoli.");
            return;
        }

        $inventarioService = app(InventarioService::class);
        $ripristinati = 0;
        $errori = 0;
        $erroriDettagli = [];

        foreach ($this->articoliSelezionati as $articoloId) {
            try {
                $inventarioService->ripristinaArticolo($articoloId);
                $ripristinati++;
            } catch (\Exception $e) {
                $errori++;
                $erroriDettagli[] = "ID {$articoloId}: " . $e->getMessage();
                \Log::error("Errore ripristino articolo {$articoloId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        if ($ripristinati > 0) {
            session()->flash('success', "✅ {$ripristinati} articoli ripristinati con successo!");
        }
        if ($errori > 0) {
            $messaggioErrore = "❌ {$errori} articoli non ripristinati.";
            if (count($erroriDettagli) <= 5) {
                $messaggioErrore .= " Errori: " . implode('; ', $erroriDettagli);
            } else {
                $messaggioErrore .= " Controlla i log per i dettagli completi.";
            }
            session()->flash('error', $messaggioErrore);
        }

        $this->articoliSelezionati = [];
        $this->selezionaTutti = false;
        $this->chiudiModalRipristinoMultiplo();
        $this->resetPage();
    }

    public function ripristinaArticolo($articoloId)
    {
        try {
            $inventarioService = app(InventarioService::class);
            $articolo = $inventarioService->ripristinaArticolo($articoloId);
            
            session()->flash('success', "Articolo '{$articolo->codice}' ripristinato con successo!");
            $this->chiudiModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il ripristino: ' . $e->getMessage());
        }
    }

    public function getArticoliQuery()
    {
        $query = ArticoloStorico::with(['sessioneInventario', 'utente']);

        if ($this->filtroSede) {
            $query->whereHas('sessioneInventario', function ($q) {
                $q->where('sede_id', $this->filtroSede);
            });
        }

        if ($this->filtroMotivo) {
            $query->where('motivo_eliminazione', $this->filtroMotivo);
        }

        if ($this->filtroDataInizio) {
            $query->where('data_eliminazione', '>=', $this->filtroDataInizio);
        }

        if ($this->filtroDataFine) {
            $query->where('data_eliminazione', '<=', $this->filtroDataFine);
        }

        if ($this->filtroCodice) {
            $query->where('codice', 'like', '%' . $this->filtroCodice . '%');
        }

        return $query->orderBy('data_eliminazione', 'desc');
    }

    public function getStatistiche()
    {
        $query = $this->getArticoliQuery();
        
        return [
            'totale_articoli' => $query->count(),
            'valore_totale' => $query->sum('dati_completi->prezzo_acquisto'),
            'per_motivo' => $query->selectRaw('motivo_eliminazione, COUNT(*) as count')
                ->groupBy('motivo_eliminazione')
                ->get()
                ->pluck('count', 'motivo_eliminazione'),
            'per_sede' => $query->join('inventario_sessioni', 'articoli_storico.sessione_inventario_id', '=', 'inventario_sessioni.id')
                ->join('sedi', 'inventario_sessioni.sede_id', '=', 'sedi.id')
                ->selectRaw('sedi.nome, COUNT(*) as count')
                ->groupBy('sedi.nome')
                ->get()
                ->pluck('count', 'nome')
        ];
    }

    public function render()
    {
        $articoli = $this->getArticoliQuery()->paginate(15);
        $statistiche = $this->getStatistiche();
        
        return view('livewire.storico-articoli', [
            'articoli' => $articoli,
            'statistiche' => $statistiche
        ])->layout('layouts.vertical');
    }
}