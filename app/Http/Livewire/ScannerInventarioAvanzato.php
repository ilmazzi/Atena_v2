<?php

namespace App\Http\Livewire;

use App\Models\Articolo;
use App\Models\InventarioSessione;
use App\Models\InventarioScansione;
use App\Services\InventarioService;
use Livewire\Component;
use Livewire\WithPagination;

class ScannerInventarioAvanzato extends Component
{
    use WithPagination;

    public $sessioneId = null;
    public $sessione = null;
    public $codiceScansionato = '';
    public $articoloTrovato = null;
    public $quantitaTrovata = 1;
    public $quantitaSistema = 0;
    public $operazione = 'trovato'; // trovato, eliminato
    public $showQuantitaModal = false;
    public $scansioni = [];
    public $statistiche = [];
    public $showModal = false;
    public $messaggio = '';
    public $sessioniDisponibili = [];
    public $sessioneSelezionata = '';
    public $variantiTrovate = [];
    public $showVariantiModal = false;

    protected $listeners = ['codiceScansionato' => 'processaCodice'];

    public function mount($sessione = null)
    {
        if ($sessione) {
            $this->sessioneId = $sessione;
            $this->caricaSessione();
        } else {
            // Carica sessioni disponibili se non è specificata una sessione
            $this->caricaSessioniDisponibili();
        }
    }

    public function caricaSessione()
    {
        if ($this->sessioneId) {
            $this->sessione = InventarioSessione::with(['sede', 'utente'])
                ->find($this->sessioneId);
            
            if ($this->sessione) {
                $this->caricaScansioni();
                $this->calcolaStatistiche();
            }
        }
    }

    public function caricaSessioniDisponibili()
    {
        $this->sessioniDisponibili = InventarioSessione::where('stato', 'attiva')
            ->with('sede')
            ->get()
            ->map(function ($sessione) {
                return [
                    'id' => $sessione->id,
                    'nome' => $sessione->nome,
                    'sede' => $sessione->sede->nome,
                    'data_inizio' => $sessione->data_inizio->format('d/m/Y H:i')
                ];
            });
    }

    public function selezionaSessione()
    {
        if ($this->sessioneSelezionata) {
            $this->sessioneId = $this->sessioneSelezionata;
            $this->caricaSessione();
            $this->messaggio = "Sessione selezionata: {$this->sessione->nome}";
        }
    }

    public function selezionaVariante($articoloId)
    {
        $articolo = Articolo::find($articoloId);
        if ($articolo) {
            $this->articoloTrovato = $articolo;
            $this->quantitaSistema = $articolo->giacenze()
                ->where('sede_id', $this->sessione->sede_id)
                ->sum('quantita_residua');
            $this->quantitaTrovata = $this->quantitaSistema;
            $this->messaggio = "Articolo selezionato: {$articolo->descrizione} (Quantità sistema: {$this->quantitaSistema})";
            $this->showVariantiModal = false;
            $this->variantiTrovate = [];
        }
    }

    public function chiudiVariantiModal()
    {
        $this->showVariantiModal = false;
        $this->variantiTrovate = [];
        $this->messaggio = 'Selezione varianti annullata';
    }

    public function caricaScansioni()
    {
        $this->scansioni = InventarioScansione::where('sessione_id', $this->sessioneId)
            ->with('articolo')
            ->orderBy('data_scansione', 'desc')
            ->limit(20)
            ->get();
    }

    public function calcolaStatistiche()
    {
        $this->statistiche = [
            'scansioni_totali' => InventarioScansione::where('sessione_id', $this->sessioneId)->count(),
            'articoli_trovati' => InventarioScansione::where('sessione_id', $this->sessioneId)
                ->where('azione', 'trovato')->count(),
            'articoli_eliminati' => InventarioScansione::where('sessione_id', $this->sessioneId)
                ->where('azione', 'eliminato')->count(),
            'differenze' => InventarioScansione::where('sessione_id', $this->sessioneId)
                ->where('differenza', '!=', 0)->count()
        ];
    }

    public function processaCodice($codice)
    {
        $this->codiceScansionato = $codice;
        $this->cercaArticolo();
    }

    public function cercaArticolo()
    {
        if (empty($this->codiceScansionato)) {
            $this->messaggio = 'Inserisci un codice da scansionare';
            return;
        }

        if (!$this->sessione) {
            $this->messaggio = 'Nessuna sessione inventario attiva. Seleziona una sessione prima di scansionare.';
            return;
        }

        // Cerca prima l'articolo esatto
        $articolo = Articolo::where('codice', $this->codiceScansionato)
            ->whereHas('giacenze', function ($q) {
                $q->where('sede_id', $this->sessione->sede_id)
                  ->where('quantita_residua', '>', 0);
            })
            ->first();

        // Se non trovato, cerca varianti che iniziano con il codice base
        if (!$articolo) {
            $codiceBase = $this->codiceScansionato;
            $articoliVarianti = Articolo::where('codice', 'like', $codiceBase . '%')
                ->whereHas('giacenze', function ($q) {
                    $q->where('sede_id', $this->sessione->sede_id)
                      ->where('quantita_residua', '>', 0);
                })
                ->get();

            if ($articoliVarianti->count() > 0) {
                // Se ci sono più varianti, mostra un modal con tutte le opzioni
                $this->variantiTrovate = $articoliVarianti->map(function ($articolo) {
                    $quantita = $articolo->giacenze()
                        ->where('sede_id', $this->sessione->sede_id)
                        ->sum('quantita_residua');
                    return [
                        'id' => $articolo->id,
                        'codice' => $articolo->codice,
                        'descrizione' => $articolo->descrizione,
                        'quantita' => $quantita
                    ];
                })->toArray();
                
                $this->showVariantiModal = true;
                $this->messaggio = "Trovate {$articoliVarianti->count()} varianti. Seleziona quella che vuoi scansionare.";
                $this->articoloTrovato = null;
                return;
            }
        }

        if ($articolo) {
            $this->articoloTrovato = $articolo;
            $this->quantitaSistema = $articolo->giacenze()
                ->where('sede_id', $this->sessione->sede_id)
                ->sum('quantita_residua');
            $this->quantitaTrovata = $this->quantitaSistema; // Default: trova tutto
            $this->messaggio = "Articolo trovato: {$articolo->descrizione} (Quantità sistema: {$this->quantitaSistema})";
        } else {
            $this->articoloTrovato = null;
            $this->messaggio = 'Articolo non trovato o già eliminato';
        }
    }

    public function eseguiOperazione()
    {
        if (!$this->articoloTrovato) {
            $this->messaggio = 'Nessun articolo selezionato';
            return;
        }

        // Se quantità trovata > quantità sistema, chiedi conferma
        if ($this->quantitaTrovata > $this->quantitaSistema) {
            $this->messaggio = "ATTENZIONE: Hai trovato {$this->quantitaTrovata} pezzi ma il sistema ne registra solo {$this->quantitaSistema}. Verifica la quantità.";
            return;
        }

        try {
            $inventarioService = app(InventarioService::class);
            
            $scansione = $inventarioService->registraScansione(
                $this->sessioneId,
                $this->articoloTrovato->id,
                $this->operazione,
                $this->operazione === 'trovato' ? $this->quantitaTrovata : null
            );

            $this->messaggio = "Operazione completata: {$this->operazione} (Quantità: {$this->quantitaTrovata})";
            $this->reset(['codiceScansionato', 'articoloTrovato', 'quantitaTrovata', 'quantitaSistema']);
            $this->caricaScansioni();
            $this->calcolaStatistiche();
            
        } catch (\Exception $e) {
            $this->messaggio = 'Errore: ' . $e->getMessage();
        }
    }

    public function trovatoCompleto()
    {
        $this->operazione = 'trovato';
        $this->eseguiOperazione();
    }

    public function apriModalQuantita()
    {
        if (!$this->articoloTrovato) {
            $this->messaggio = 'Nessun articolo selezionato';
            return;
        }
        
        $this->operazione = 'trovato';
        $this->showQuantitaModal = true;
    }

    public function chiudiModalQuantita()
    {
        $this->showQuantitaModal = false;
    }

    public function confermaQuantita()
    {
        if ($this->quantitaTrovata <= 0) {
            $this->messaggio = 'La quantità deve essere maggiore di 0';
            return;
        }
        
        if ($this->quantitaTrovata > $this->quantitaSistema) {
            $this->messaggio = "La quantità trovata ({$this->quantitaTrovata}) non può essere maggiore di quella del sistema ({$this->quantitaSistema})";
            return;
        }
        
        $this->eseguiOperazione();
        $this->chiudiModalQuantita();
    }

    public function aggiornaQuantita()
    {
        // Metodo per aggiornare la quantità in tempo reale
        if ($this->quantitaTrovata > $this->quantitaSistema) {
            $this->quantitaTrovata = $this->quantitaSistema;
        }
    }

    public function eliminaArticolo($articoloId)
    {
        $this->operazione = 'eliminato';
        $this->articoloTrovato = Articolo::find($articoloId);
        $this->quantitaTrovata = $this->articoloTrovato->giacenze()
            ->where('sede_id', $this->sessione->sede_id)
            ->sum('quantita_residua');
        $this->showModal = true;
    }

    public function chiudiModal()
    {
        $this->showModal = false;
        $this->reset(['operazione', 'articoloTrovato', 'quantitaTrovata']);
    }

    public function confermaEliminazione()
    {
        if ($this->operazione === 'eliminato' && $this->articoloTrovato) {
            $this->eseguiOperazione();
            $this->chiudiModal();
        }
    }

    public function render()
    {
        return view('livewire.scanner-inventario-avanzato')
            ->layout('layouts.vertical');
    }
}