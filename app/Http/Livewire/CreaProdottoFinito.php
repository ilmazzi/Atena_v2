<?php

namespace App\Http\Livewire;

use App\Models\Articolo;
use App\Models\CategoriaMerceologica;
use App\Models\ProdottoFinito;
use App\Models\Sede;
use App\Services\ProdottoFinitoService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.vertical')]
class CreaProdottoFinito extends Component
{
    // Step wizard
    public $step = 1;
    
    // Modalità modifica
    public $prodottoId = null;
    public $isModifica = false;
    public $titoloPagina = 'Nuovo Prodotto Finito';
    
    // Dati prodotto finito
    public $descrizione = '';
    public $tipologia = 'prodotto_finito';
    public $categoriaId = 9; // Default: Gioielleria
    public $sedeId = '';
    public $costoLavorazione = 0;
    public $note = '';
    
    // Componenti selezionati
    public $componenti = []; // Array di ['articolo_id' => id, 'quantita' => qty, 'articolo' => obj]
    
    // Ricerca articoli
    public $searchArticoli = '';
    public $categoriaComponentiFilter = '';
    public $soloDisponibili = true;
    
    // Dati calcolati
    public $oroTotale = '';
    public $brillantiTotali = '';
    public $pietreTotali = '';
    public $costoMaterialiTotale = 0;
    public $costoTotale = 0;
    
    protected $rules = [
        'descrizione' => 'required|string|max:500',
        'tipologia' => 'required|in:prodotto_finito,semilavorato,componente',
        'categoriaId' => 'required|exists:categorie_merceologiche,id',
        'sedeId' => 'required|exists:sedi,id',
        'costoLavorazione' => 'nullable|numeric|min:0',
    ];

    public function mount($id = null)
    {
        // Imposta sede di default
        $sedeDefault = Sede::where('attivo', true)->first();
        $this->sedeId = $sedeDefault->id ?? '';
        
        // Se è una modifica, carica i dati del prodotto
        if ($id) {
            $this->prodottoId = $id;
            $this->isModifica = true;
            $this->titoloPagina = 'Modifica Prodotto Finito';
            $this->caricaProdottoPerModifica($id);
        }
    }

    private function caricaProdottoPerModifica($id)
    {
        $prodotto = ProdottoFinito::with(['componentiArticoli.articolo'])->findOrFail($id);
        
        // Carica dati base
        $this->descrizione = $prodotto->descrizione;
        $this->tipologia = $prodotto->tipologia;
        $this->categoriaId = $prodotto->magazzino_id;
        $this->costoLavorazione = $prodotto->costo_lavorazione ?? 0;
        $this->note = $prodotto->note ?? '';
        
        // Carica componenti
        $this->componenti = [];
        foreach ($prodotto->componentiArticoli as $componente) {
            $this->componenti[$componente->articolo_id] = [
                'articolo_id' => $componente->articolo_id,
                'quantita' => $componente->quantita,
                'articolo' => $componente->articolo,
            ];
        }
        
        // Ricalcola dati
        $this->ricalcolaDati();
        
        // Vai direttamente al step 3 (riepilogo)
        $this->step = 3;
    }

    public function aggiungiComponente($articoloId)
    {
        // Verifica se già aggiunto
        if (isset($this->componenti[$articoloId])) {
            $this->componenti[$articoloId]['quantita']++;
        } else {
            $articolo = Articolo::with('giacenza')->find($articoloId);
            
            if (!$articolo) {
                $this->dispatch('show-toast', 
                    type: 'error',
                    message: 'Articolo non trovato'
                );
                return;
            }
            
            $this->componenti[$articoloId] = [
                'articolo_id' => $articolo->id,
                'quantita' => 1,
                'articolo' => $articolo,
            ];
        }
        
        $this->ricalcolaDati();
    }

    public function rimuoviComponente($articoloId)
    {
        unset($this->componenti[$articoloId]);
        $this->ricalcolaDati();
    }

    public function aggiornaQuantita($articoloId, $quantita)
    {
        if (isset($this->componenti[$articoloId])) {
            $this->componenti[$articoloId]['quantita'] = max(1, (int)$quantita);
            $this->ricalcolaDati();
        }
    }

    public function ricalcolaDati()
    {
        $oro = [];
        $brillanti = [];
        $pietre = [];
        $costoMateriali = 0;
        
        foreach ($this->componenti as $comp) {
            $articolo = $comp['articolo'];
            $quantita = $comp['quantita'];
            
            // Estrai dati gioielleria
            $caratteristiche = is_string($articolo->caratteristiche)
                ? json_decode($articolo->caratteristiche, true)
                : $articolo->caratteristiche;
            
            if (!empty($caratteristiche['oro'])) {
                $oro[] = $caratteristiche['oro'];
            }
            if (!empty($caratteristiche['brill'])) {
                $brillanti[] = $caratteristiche['brill'];
            }
            if (!empty($caratteristiche['pietre'])) {
                $pietre[] = $caratteristiche['pietre'];
            }
            
            // Calcola costo
            $costoMateriali += ($articolo->prezzo_acquisto ?? 0) * $quantita;
        }
        
        $this->oroTotale = !empty($oro) ? implode(' + ', array_unique($oro)) : '';
        $this->brillantiTotali = !empty($brillanti) ? implode(' + ', array_unique($brillanti)) : '';
        $this->pietreTotali = !empty($pietre) ? implode(' + ', array_unique($pietre)) : '';
        $this->costoMaterialiTotale = $costoMateriali;
        $this->costoTotale = $costoMateriali + $this->costoLavorazione;
    }

    public function avanti()
    {
        if ($this->step === 1) {
            $this->validate([
                'descrizione' => 'required|string|max:500',
                'tipologia' => 'required',
                'categoriaId' => 'required',
                'sedeId' => 'required',
            ]);
            $this->step = 2;
        } elseif ($this->step === 2) {
            if (empty($this->componenti)) {
                $this->dispatch('show-toast',
                    type: 'error',
                    message: 'Aggiungi almeno un componente'
                );
                return;
            }
            $this->step = 3;
        }
    }

    public function indietro()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function salva()
    {
        try {
            // Log per debug
            Log::info('🚀 Inizio salvataggio prodotto finito', [
                'descrizione' => $this->descrizione,
                'componenti_count' => count($this->componenti),
                'sedeId' => $this->sedeId,
                'categoriaId' => $this->categoriaId,
            ]);
            
            $this->validate();
            
            if (empty($this->componenti)) {
                Log::warning('❌ Nessun componente selezionato');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Aggiungi almeno un componente'
                ]);
                return;
            }
            
            $service = app(ProdottoFinitoService::class);
            
            // Prepara dati
            $dati = [
                'descrizione' => $this->descrizione,
                'tipologia' => $this->tipologia,
                'costo_lavorazione' => $this->costoLavorazione ?? 0,
                'note' => $this->note,
            ];
            
            $componentiData = array_map(fn($c) => [
                'articolo_id' => $c['articolo_id'],
                'quantita' => $c['quantita'],
            ], $this->componenti);
            
            Log::info('📦 Dati preparati per assemblaggio', [
                'dati' => $dati,
                'componenti' => $componentiData,
            ]);
            
            if ($this->isModifica) {
                // Aggiorna prodotto esistente
                $prodottoFinito = $service->aggiornaProdotto(
                    $this->prodottoId,
                    $dati,
                    $componentiData,
                    $this->sedeId,
                    $this->categoriaId
                );
                
                Log::info('✅ Prodotto finito aggiornato con successo', [
                    'id' => $prodottoFinito->id,
                    'codice' => $prodottoFinito->codice,
                ]);
                
                $messaggio = 'Prodotto finito aggiornato con successo! Codice: ' . $prodottoFinito->codice;
            } else {
                // Crea nuovo prodotto
                $prodottoFinito = $service->assemblaProdotto(
                    $dati,
                    $componentiData,
                    $this->sedeId,
                    $this->categoriaId
                );
                
                Log::info('✅ Prodotto finito creato con successo', [
                    'id' => $prodottoFinito->id,
                    'codice' => $prodottoFinito->codice,
                ]);
                
                $messaggio = 'Prodotto finito creato con successo! Codice: ' . $prodottoFinito->codice;
            }
            
            // Mostra messaggio di successo e redirect
            session()->flash('success', $messaggio);
            
            // Redirect all'elenco
            return redirect()->route('prodotti-finiti.index');
            
        } catch (\Exception $e) {
            Log::error('❌ Errore durante creazione prodotto finito', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Errore: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        // Articoli disponibili per componenti
        $articoliDisponibili = collect();
        
        if ($this->step === 2) {
            $query = Articolo::with('giacenza', 'categoria')
                ->where('stato', 'disponibile')
                ->whereHas('giacenza', function($q) {
                    $q->where('sede_id', $this->sedeId);
                });
            
            // Filtra solo disponibili se checkbox attivo
            if ($this->soloDisponibili) {
                $query->whereHas('giacenza', function($q) {
                    $q->where('sede_id', $this->sedeId)
                      ->where('quantita_residua', '>', 0);
                });
            }
            
            if ($this->searchArticoli) {
                $searchTerm = '%' . $this->searchArticoli . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('codice', 'like', $searchTerm)
                      ->orWhere('descrizione', 'like', $searchTerm);
                });
            }
            
            if ($this->categoriaComponentiFilter) {
                $query->where('categoria_merceologica_id', $this->categoriaComponentiFilter);
            }
            
            // Escludi articoli già aggiunti
            $query->whereNotIn('id', array_keys($this->componenti));
            
            $articoliDisponibili = $query->limit(50)->get();
        }
        
        $categorie = CategoriaMerceologica::where('attivo', true)->get();
        $sedi = Sede::where('attivo', true)->get();
        
        return view('livewire.crea-prodotto-finito', compact('articoliDisponibili', 'categorie', 'sedi'));
    }
}

