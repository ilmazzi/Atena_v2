<?php

namespace App\Http\Livewire;

use App\Models\Articolo;
use App\Models\Stampante;
use App\Services\EtichettaService;
use Livewire\Component;

class ScannerInventario extends Component
{
    public $codiceScansionato = '';
    public $articoloTrovato = null;
    public $quantita = 1;
    public $operazione = 'scarico'; // 'scarico', 'inventario', 'stampa'
    public $stampanteSelezionata = '';
    public $stampantiDisponibili = [];
    public $showModal = false;
    public $messaggio = '';

    protected $rules = [
        'codiceScansionato' => 'required|string|min:3',
        'quantita' => 'required|integer|min:1',
        'operazione' => 'required|in:scarico,inventario,stampa',
        'stampanteSelezionata' => 'nullable|exists:stampanti,id'
    ];

    public function mount()
    {
        $this->loadStampantiDisponibili();
    }

    public function updatedCodiceScansionato($value)
    {
        if (strlen($value) >= 3) {
            $this->cercaArticolo($value);
        }
    }

    public function cercaArticolo($codice = null)
    {
        $codice = $codice ?? $this->codiceScansionato;
        
        if (empty($codice)) return;

        $this->articoloTrovato = Articolo::where('codice', $codice)
            ->whereHas('giacenze', function($query) {
                $query->where('quantita_residua', '>', 0);
            })
            ->with('giacenze')
            ->first();

        if ($this->articoloTrovato) {
            // Verifica permessi utente
            if (!auth()->user()->canAccessArticolo($this->articoloTrovato)) {
                $this->articoloTrovato = null;
                $this->messaggio = 'Non hai i permessi per accedere a questo articolo';
                return;
            }

            $quantitaResidua = $this->articoloTrovato->giacenze->sum('quantita_residua');
            $this->quantita = min($quantitaResidua, 1);
            $this->messaggio = '';
        } else {
            $this->messaggio = 'Articolo non trovato o giacenza zero';
        }
    }

    public function eseguiOperazione()
    {
        if (!$this->articoloTrovato) {
            $this->messaggio = 'Nessun articolo selezionato';
            return;
        }

        $this->validate();

        switch($this->operazione) {
            case 'scarico':
                $this->scaricaArticolo();
                break;
            case 'inventario':
                $this->aggiornaInventario();
                break;
            case 'stampa':
                $this->stampaEtichetta();
                break;
        }
    }

    public function scaricaArticolo()
    {
        $quantitaTotale = $this->articoloTrovato->giacenze->sum('quantita_residua');
        if ($this->quantita > $quantitaTotale) {
            $this->messaggio = 'Quantità non disponibile';
            return;
        }

        try {
            // Aggiorna la giacenza nella tabella giacenze
            $giacenza = $this->articoloTrovato->giacenze->first();
            if ($giacenza) {
                $giacenza->quantita_residua -= $this->quantita;
                $giacenza->save();
                
                if ($giacenza->quantita_residua <= 0) {
                    $this->articoloTrovato->stato_articolo = 'scaricato';
                    $this->articoloTrovato->save();
                }
            }

            $this->messaggio = "Scaricati {$this->quantita} pezzi di {$this->articoloTrovato->codice}";
            $this->reset(['codiceScansionato', 'articoloTrovato', 'quantita']);
            
        } catch (\Exception $e) {
            $this->messaggio = 'Errore durante lo scarico: ' . $e->getMessage();
        }
    }

    public function aggiornaInventario()
    {
        try {
            // Logica per aggiornamento inventario
            $this->articoloTrovato->save();
            
            $this->messaggio = "Inventario aggiornato per {$this->articoloTrovato->codice}";
            $this->reset(['codiceScansionato', 'articoloTrovato', 'quantita']);
            
        } catch (\Exception $e) {
            $this->messaggio = 'Errore durante l\'aggiornamento: ' . $e->getMessage();
        }
    }

    public function stampaEtichetta()
    {
        if (empty($this->stampantiDisponibili)) {
            $this->messaggio = 'Nessuna stampante disponibile';
            return;
        }

        if (!$this->stampanteSelezionata) {
            $this->stampanteSelezionata = $this->stampantiDisponibili->first()->id;
        }

        try {
            $etichettaService = app(EtichettaService::class);
            $success = $etichettaService->stampaEtichetta($this->articoloTrovato, $this->stampanteSelezionata);
            
            if ($success) {
                $this->messaggio = "Etichetta stampata per {$this->articoloTrovato->codice}";
                $this->reset(['codiceScansionato', 'articoloTrovato', 'quantita']);
            } else {
                $this->messaggio = 'Errore durante la stampa';
            }
            
        } catch (\Exception $e) {
            $this->messaggio = 'Errore stampa: ' . $e->getMessage();
        }
    }

    public function selezionaStampante($stampanteId)
    {
        $this->stampanteSelezionata = $stampanteId;
        $this->showModal = true;
    }

    public function updatedStampanteSelezionata($value)
    {
        $this->stampanteSelezionata = $value;
    }

    public function confermaStampa()
    {
        $this->stampaEtichetta();
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->reset(['codiceScansionato', 'articoloTrovato', 'quantita', 'messaggio']);
    }

    private function loadStampantiDisponibili()
    {
        $user = auth()->user();
        
        $this->stampantiDisponibili = Stampante::where('attiva', true)
            ->get()
            ->filter(function ($stampante) use ($user) {
                // Filtra per permessi utente se definiti
                if ($user->categorie_permesse && $user->sedi_permesse) {
                    return !empty(array_intersect($stampante->categorie_permesse, $user->categorie_permesse)) &&
                           !empty(array_intersect($stampante->sedi_permesse, $user->sedi_permesse));
                }
                
                return true; // Se l'utente non ha restrizioni
            });
    }

    public function render()
    {
        return view('livewire.scanner-inventario');
    }
}