<?php

namespace App\Http\Livewire;

use App\Models\Fornitore;
use App\Models\CategoriaMerceologica;
use App\Models\Articolo;
use App\Models\Sede;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.vertical', ['title' => 'Elenco Articoli'])]
class ArticoliTable extends Component
{
    use WithPagination;

    // ID componente (Livewire)
    public $id;
    
    // Filtri Avanzati
    public $search = '';
    public $magazzinoFilter = ''; // Filtro singolo per compatibilità
    public $magazziniSelezionati = []; // Filtro multiplo per categorie
    public $showMagazzinoDropdown = false; // Controllo dropdown personalizzato
    public $statoFilter = '';
    public $fornitoreFilter = '';
    public $marcaFilter = '';
    public $ubicazioneFilter = '';
    public $giacenzaFilter = ''; // '', 'giacenti', 'scarichi'
    public $giacenza = ''; // Nuovo parametro per filtri dalla dashboard: 'positiva', 'zero', 'negativa', 'nessuna'
    public $statoArticoloFilter = ''; // '', 'disponibile', 'scaricato'
    public $prezzoMin = '';
    public $prezzoMax = '';
    public $dataDocumentoFrom = '';
    public $dataDocumentoTo = '';
    public $soloVetrina = false;
    
    // Modalità scarico parziale
    public $showModalScarico = false;
    public $articoloDaScaricare = null;
    public $quantitaDaScaricare = 1;
    public $giacenzaDisponibile = 0;
    
    // Modalità stampa etichetta
    public $showModalStampa = false;
    public $articoloDaStampare = null;
    public $prezzoEtichetta = '';
    public $formatoPrezzo = 'euro'; // 'euro' o 'codificato'
    public $stampanteSelezionata = '';
    public $stampantiDisponibili = [];
    
    // Paginazione e ordinamento
    public $perPage = 25;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'magazzinoFilter' => ['except' => ''],
        'magazziniSelezionati' => ['except' => []],
        'statoFilter' => ['except' => ''],
        'fornitoreFilter' => ['except' => ''],
        'marcaFilter' => ['except' => ''],
        'ubicazioneFilter' => ['except' => ''],
        'giacenzaFilter' => ['except' => ''],
        'giacenza' => ['except' => ''], // Nuovo parametro per filtri dalla dashboard
        'statoArticoloFilter' => ['except' => ''],
        'prezzoMin' => ['except' => ''],
        'prezzoMax' => ['except' => ''],
        'dataDocumentoFrom' => ['except' => ''],
        'dataDocumentoTo' => ['except' => ''],
        'soloVetrina' => ['except' => false],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    // RIMOSSO: Listener JavaScript non più necessario
    // Il dropdown si chiude automaticamente con Livewire

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedMagazzinoFilter()
    {
        $this->resetPage();
    }

    public function updatedMagazziniSelezionati()
    {
        $this->resetPage();
    }

    public function toggleMagazzinoDropdown()
    {
        $this->showMagazzinoDropdown = !$this->showMagazzinoDropdown;
    }

    public function toggleMagazzino($magazzinoId)
    {
        if (in_array($magazzinoId, $this->magazziniSelezionati)) {
            $this->magazziniSelezionati = array_diff($this->magazziniSelezionati, [$magazzinoId]);
        } else {
            $this->magazziniSelezionati[] = $magazzinoId;
        }
        $this->resetPage();
    }

    public function selezionaTuttiMagazzini()
    {
        $this->magazziniSelezionati = CategoriaMerceologica::pluck('id')->toArray();
        $this->resetPage();
    }

    public function deselezionaTuttiMagazzini()
    {
        $this->magazziniSelezionati = [];
        $this->resetPage();
    }

    public function updatedStatoFilter()
    {
        $this->resetPage();
    }

    public function updatedDataDocumentoFrom()
    {
        $this->resetPage();
    }

    public function updatedDataDocumentoTo()
    {
        $this->resetPage();
    }

    public function updatedFornitoreFilter()
    {
        $this->resetPage();
    }

    public function updatedMarcaFilter()
    {
        $this->resetPage();
    }

    public function updatedUbicazioneFilter()
    {
        $this->resetPage();
    }

    public function updatedGiacenzaFilter()
    {
        $this->resetPage();
    }

    public function updatedGiacenza()
    {
        $this->resetPage();
    }

    public function updatedPrezzoMin()
    {
        $this->resetPage();
    }

    public function updatedPrezzoMax()
    {
        $this->resetPage();
    }

    public function updatedDataFrom()
    {
        $this->resetPage();
    }

    public function updatedDataTo()
    {
        $this->resetPage();
    }

    public function updatedSoloVetrina()
    {
        $this->resetPage();
    }

    public function updatedSoloInventariati()
    {
        $this->resetPage();
    }
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->magazzinoFilter = '';
        $this->magazziniSelezionati = [];
        $this->showMagazzinoDropdown = false;
        $this->statoFilter = '';
        $this->fornitoreFilter = '';
        $this->marcaFilter = '';
        $this->ubicazioneFilter = '';
        $this->giacenzaFilter = '';
        $this->giacenza = '';
        $this->statoArticoloFilter = '';
        $this->prezzoMin = '';
        $this->prezzoMax = '';
        $this->dataDocumentoFrom = '';
        $this->dataDocumentoTo = '';
        $this->soloVetrina = false;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
        
        // Emit event per resettare Flatpickr
        $this->dispatch('filters-reset');
    }
    
    /**
     * Apre modal per scarico parziale o scarica direttamente
     */
    public function scaricaArticolo($articoloId)
    {
        try {
            $articolo = Articolo::findOrFail($articoloId);
            
            // Verifica che l'articolo sia disponibile
            if ($articolo->stato_articolo !== 'disponibile') {
                session()->flash('error', 'Articolo non disponibile per lo scarico');
                return;
            }
            
            $giacenza = $articolo->giacenza->quantita_residua ?? 0;
            
            // Se giacenza = 1, scarico diretto
            if ($giacenza == 1) {
                $this->eseguiScarico($articolo, 1);
            } else {
                // Se giacenza > 1, apri modal per scegliere quantità
                $this->articoloDaScaricare = $articolo;
                $this->giacenzaDisponibile = $giacenza;
                $this->quantitaDaScaricare = 1;
                $this->showModalScarico = true;
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante lo scarico: ' . $e->getMessage());
        }
    }
    
    /**
     * Esegue lo scarico con quantità specificata
     */
    public function eseguiScarico($articolo, $quantita)
    {
        try {
            $giacenzaAttuale = $articolo->giacenza->quantita_residua ?? 0;
            
            if ($quantita > $giacenzaAttuale) {
                session()->flash('error', 'Quantità richiesta superiore alla giacenza disponibile');
                return;
            }
            
            $nuovaGiacenza = $giacenzaAttuale - $quantita;
            
            // Aggiorna giacenza
            $articolo->giacenza()->update([
                'quantita_residua' => $nuovaGiacenza
            ]);
            
            // Se giacenza diventa 0, marca come scaricato
            if ($nuovaGiacenza == 0) {
                $articolo->update([
                    'stato_articolo' => 'scaricato',
                    'scaricato_il' => now(),
                    'scaricato_da' => auth()->id()
                ]);
            }
            
            $messaggio = $nuovaGiacenza == 0 
                ? "Articolo {$articolo->codice} scaricato completamente" 
                : "Scaricati {$quantita} pezzi di {$articolo->codice}. Giacenza residua: {$nuovaGiacenza}";
                
            session()->flash('success', $messaggio);
            
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante lo scarico: ' . $e->getMessage());
        }
    }
    
    /**
     * Conferma scarico parziale dal modal
     */
    public function confermaScaricoParziale()
    {
        if (!$this->articoloDaScaricare || $this->quantitaDaScaricare <= 0) {
            session()->flash('error', 'Dati non validi');
            return;
        }
        
        if ($this->quantitaDaScaricare > $this->giacenzaDisponibile) {
            session()->flash('error', 'Quantità superiore alla giacenza disponibile');
            return;
        }
        
        $this->eseguiScarico($this->articoloDaScaricare, $this->quantitaDaScaricare);
        
        // Chiudi modal
        $this->showModalScarico = false;
        $this->articoloDaScaricare = null;
        $this->quantitaDaScaricare = 1;
        $this->giacenzaDisponibile = 0;
    }
    
    /**
     * Chiudi modal scarico
     */
    public function chiudiModalScarico()
    {
        $this->showModalScarico = false;
        $this->articoloDaScaricare = null;
        $this->quantitaDaScaricare = 1;
        $this->giacenzaDisponibile = 0;
    }
    
    /**
     * Apre modal per stampa etichetta
     */
    public function apriModalStampa($articoloId)
    {
        try {
            $articolo = Articolo::findOrFail($articoloId);
            $this->articoloDaStampare = $articolo;
            
            // Pre-compila il prezzo se disponibile
            if ($articolo->prezzo_acquisto) {
                $this->prezzoEtichetta = number_format($articolo->prezzo_acquisto, 2, ',', '.');
                $this->formatoPrezzo = 'euro';
            } else {
                $this->prezzoEtichetta = '';
                $this->formatoPrezzo = 'euro';
            }
            
            // Carica stampanti disponibili per questo articolo
            $this->caricaStampantiDisponibili($articolo);
            
            $this->showModalStampa = true;
            
            // Chiudi dropdown secondo documentazione Livewire 3
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'apertura del modal: ' . $e->getMessage());
        }
    }
    
    /**
     * Carica stampanti disponibili per l'articolo
     */
    private function caricaStampantiDisponibili($articolo)
    {
        $user = auth()->user();
        
        // Carica tutte le stampanti attive
        $stampanti = \App\Models\Stampante::where('attiva', true)->get();
        
        $this->stampantiDisponibili = $stampanti->filter(function ($stampante) use ($articolo, $user) {
            // Verifica se la stampante può stampare questo articolo
            return $stampante->canPrintArticolo($articolo);
        })->map(function ($stampante) {
            return [
                'id' => $stampante->id,
                'nome' => $stampante->nome,
                'modello' => $stampante->modello,
                'ip_address' => $stampante->ip_address
            ];
        })->values()->toArray();
        
        // Seleziona automaticamente la stampante predefinita dell'utente se disponibile
        if ($user && $user->stampante_default_id) {
            $stampanteDefault = collect($this->stampantiDisponibili)->firstWhere('id', $user->stampante_default_id);
            if ($stampanteDefault) {
                $this->stampanteSelezionata = $user->stampante_default_id;
            }
        }
        
        // Se non c'è una stampante predefinita, seleziona la prima disponibile
        if (empty($this->stampanteSelezionata) && !empty($this->stampantiDisponibili)) {
            $this->stampanteSelezionata = $this->stampantiDisponibili[0]['id'];
        }
    }

    /**
     * Chiudi modal stampa
     */
    public function chiudiModalStampa()
    {
        $this->showModalStampa = false;
        $this->articoloDaStampare = null;
        $this->prezzoEtichetta = '';
        $this->formatoPrezzo = 'euro';
        $this->stampanteSelezionata = '';
        $this->stampantiDisponibili = [];
    }
    
    /**
     * Conferma stampa etichetta
     */
    public function confermaStampaEtichetta()
    {
        if (!$this->articoloDaStampare) {
            session()->flash('error', 'Nessun articolo selezionato');
            return;
        }
        
        if (empty($this->prezzoEtichetta)) {
            session()->flash('error', 'Il prezzo è obbligatorio');
            return;
        }
        
        if (empty($this->stampanteSelezionata)) {
            session()->flash('error', 'Seleziona una stampante');
            return;
        }
        
        try {
            // Prepara i dati per la stampa
            $datiStampa = [
                'articolo_id' => $this->articoloDaStampare->id,
                'prezzo' => $this->prezzoEtichetta,
                'formato_prezzo' => $this->formatoPrezzo,
                'stampante_id' => $this->stampanteSelezionata
            ];
            
            // Chiama il controller di stampa
            $response = app(\App\Http\Controllers\StampaController::class)
                ->stampaEtichettaConPrezzo($datiStampa);
            
            if ($response['success']) {
                session()->flash('success', "Etichetta stampata con successo per l'articolo {$this->articoloDaStampare->codice}");
            } else {
                session()->flash('error', $response['message'] ?? 'Errore durante la stampa');
            }
            
            // Chiudi modal
            $this->chiudiModalStampa();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante la stampa: ' . $e->getMessage());
        }
    }
    
    /**
     * Ripristina un articolo scaricato
     */
    public function ripristinaArticolo($articoloId)
    {
        try {
            $articolo = Articolo::findOrFail($articoloId);
            
            // Verifica che l'articolo sia scaricato
            if ($articolo->stato_articolo !== 'scaricato') {
                session()->flash('error', 'Articolo non è in stato scaricato');
                return;
            }
            
            // Ripristina stato articolo
            $articolo->update([
                'stato_articolo' => 'disponibile',
                'scaricato_il' => null,
                'scaricato_da' => null
            ]);
            
            // Ripristina giacenza originale
            $articolo->giacenza()->update([
                'quantita_residua' => $articolo->giacenza->quantita
            ]);
            
            session()->flash('success', "Articolo {$articolo->codice} ripristinato con successo");
            
            // Chiudi dropdown secondo documentazione Livewire 3
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il ripristino: ' . $e->getMessage());
        }
    }

    /**
     * Crea una query con tutti i filtri applicati (senza paginazione)
     */
    private function getFilteredQuery()
    {
        $query = Articolo::with([
            'categoria', 
            'sede', 
            'giacenza', 
            'ddtDettaglio.ddt.fornitore',
            'fatturaDettaglio.fattura.fornitore'
        ]);

        // Applica tutti i filtri (stessa logica del render)
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('articoli.codice', 'like', $searchTerm)
                  ->orWhere('articoli.descrizione', 'like', $searchTerm)
                  ->orWhere('articoli.descrizione_estesa', 'like', $searchTerm)
                  ->orWhere('articoli.numero_documento_carico', 'like', $searchTerm)
                  ->orWhere('articoli.materiale', 'like', $searchTerm)
                  ->orWhere('articoli.colore', 'like', $searchTerm)
                  ->orWhereHas('categoriaMerceologica', function($subQ) use ($searchTerm) {
                      $subQ->where('nome', 'like', $searchTerm)
                           ->orWhere('codice', 'like', $searchTerm);
                  })
                  ->orWhereHas('ddtDettaglio.ddt.fornitore', function($subQ) use ($searchTerm) {
                      $subQ->where('ragione_sociale', 'like', $searchTerm);
                  });
            });
        }

        if (!empty($this->magazziniSelezionati)) {
            $query->whereIn('articoli.categoria_merceologica_id', $this->magazziniSelezionati);
        } elseif ($this->magazzinoFilter) {
            $query->where('articoli.categoria_merceologica_id', $this->magazzinoFilter);
        }

        if ($this->statoFilter) {
            $query->where('articoli.stato', $this->statoFilter);
        }

        if ($this->fornitoreFilter) {
            $query->whereHas('ddtDettaglio.ddt', function($q) {
                $q->where('fornitore_id', $this->fornitoreFilter);
            });
        }

        if ($this->marcaFilter) {
            $query->whereRaw("JSON_EXTRACT(articoli.caratteristiche, '$.marca') LIKE ?", ['%' . $this->marcaFilter . '%']);
        }

        if ($this->ubicazioneFilter) {
            $query->whereHas('giacenza', function($q) {
                $q->where('sede_id', $this->ubicazioneFilter);
            });
        }

        if ($this->giacenzaFilter) {
            if ($this->giacenzaFilter === 'giacenti') {
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '>', 0);
                });
            } elseif ($this->giacenzaFilter === 'in_produzione') {
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '=', 0);
                })->whereHas('componentiUtilizzatoIn.prodottoFinito', function($q) {
                    $q->where('stato', 'completato');
                });
            } elseif ($this->giacenzaFilter === 'scarichi') {
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '=', 0);
                })->whereDoesntHave('componentiUtilizzatoIn.prodottoFinito', function($q) {
                    $q->where('stato', 'completato');
                });
            }
        }

        if ($this->giacenza) {
            if ($this->giacenza === 'positiva') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '>', 0);
                });
            } elseif ($this->giacenza === 'zero') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '=', 0);
                });
            } elseif ($this->giacenza === 'negativa') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '<', 0);
                });
            } elseif ($this->giacenza === 'nessuna') {
                $query->whereDoesntHave('giacenze');
            }
        }

        if ($this->statoArticoloFilter) {
            $query->where('articoli.stato_articolo', $this->statoArticoloFilter);
        }

        if ($this->prezzoMin) {
            $query->where('articoli.prezzo_acquisto', '>=', $this->prezzoMin);
        }

        if ($this->prezzoMax) {
            $query->where('articoli.prezzo_acquisto', '<=', $this->prezzoMax);
        }

        if ($this->dataDocumentoFrom) {
            $query->where('articoli.data_carico', '>=', $this->dataDocumentoFrom);
        }

        if ($this->dataDocumentoTo) {
            $query->where('articoli.data_carico', '<=', $this->dataDocumentoTo);
        }

        if ($this->soloVetrina) {
            $query->where('articoli.in_vetrina', true);
        }

        return $query;
    }

    /**
     * Calcola il valore totale degli articoli filtrati
     */
    private function calcolaValoreTotale($query)
    {
        // Cloniamo la query e applichiamo il join per il calcolo del valore
        $valoreTotale = (clone $query)
            ->join('giacenze', 'articoli.id', '=', 'giacenze.articolo_id')
            ->whereNotNull('articoli.prezzo_acquisto')
            ->where('giacenze.quantita_residua', '>', 0)
            ->sum(DB::raw('articoli.prezzo_acquisto * giacenze.quantita_residua'));
            
        return $valoreTotale ?? 0;
    }

    public function render()
    {
        // Query Eloquent per tutti gli articoli con relazioni
        $query = Articolo::with([
            'categoria', 
            'sede', 
            'giacenza', 
            'ddtDettaglio.ddt.fornitore',
            'fatturaDettaglio.fattura.fornitore',
            'categoriaMerceologica'
        ]);

        // Applica filtri
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('codice', 'like', $searchTerm)
                  ->orWhere('descrizione', 'like', $searchTerm)
                  ->orWhere('descrizione_estesa', 'like', $searchTerm)
                  ->orWhere('numero_documento_carico', 'like', $searchTerm)
                  ->orWhere('materiale', 'like', $searchTerm)
                  ->orWhere('colore', 'like', $searchTerm)
                  ->orWhereHas('categoriaMerceologica', function($subQ) use ($searchTerm) {
                      $subQ->where('nome', 'like', $searchTerm)
                           ->orWhere('codice', 'like', $searchTerm);
                  })
                  ->orWhereHas('ddtDettaglio.ddt.fornitore', function($subQ) use ($searchTerm) {
                      $subQ->where('ragione_sociale', 'like', $searchTerm);
                  });
            });
        }

        // Filtro per categorie (singolo o multiplo)
        if (!empty($this->magazziniSelezionati)) {
            $query->whereIn('categoria_merceologica_id', $this->magazziniSelezionati);
        } elseif ($this->magazzinoFilter) {
            $query->where('categoria_merceologica_id', $this->magazzinoFilter);
        }

        if ($this->statoFilter) {
            $query->where('stato', $this->statoFilter);
        }

        // Filtro fornitore tramite relazione DDT
        if ($this->fornitoreFilter) {
            $query->whereHas('ddtDettaglio.ddt', function($q) {
                $q->where('fornitore_id', $this->fornitoreFilter);
            });
        }

        if ($this->marcaFilter) {
            $query->whereRaw("JSON_EXTRACT(caratteristiche, '$.marca') LIKE ?", ['%' . $this->marcaFilter . '%']);
        }

        if ($this->ubicazioneFilter) {
            // Filtra per sede (ubicazione_magazzino → sede_id)
            $query->whereHas('giacenza', function($q) {
                $q->where('sede_id', $this->ubicazioneFilter);
            });
        }

        if ($this->giacenzaFilter) {
            if ($this->giacenzaFilter === 'giacenti') {
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '>', 0);
                });
            } elseif ($this->giacenzaFilter === 'in_produzione') {
                // Articoli con giacenza 0 MA usati in prodotti finiti completati
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '=', 0);
                })->whereHas('componentiUtilizzatoIn.prodottoFinito', function($q) {
                    $q->where('stato', 'completato');
                });
            } elseif ($this->giacenzaFilter === 'scarichi') {
                // Articoli con giacenza 0 E NON usati in prodotti finiti
                $query->whereHas('giacenza', function($q) {
                    $q->where('quantita_residua', '=', 0);
                })->whereDoesntHave('componentiUtilizzatoIn.prodottoFinito', function($q) {
                    $q->where('stato', 'completato');
                });
            }
        }

        // Nuovo filtro per giacenza dalla dashboard
        if ($this->giacenza) {
            if ($this->giacenza === 'positiva') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '>', 0);
                });
            } elseif ($this->giacenza === 'zero') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '=', 0);
                });
            } elseif ($this->giacenza === 'negativa') {
                $query->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '<', 0);
                });
            } elseif ($this->giacenza === 'nessuna') {
                $query->whereDoesntHave('giacenze');
            }
        }

        // Filtro stato articolo (disponibile/scaricato)
        if ($this->statoArticoloFilter) {
            $query->where('stato_articolo', $this->statoArticoloFilter);
        }

        if ($this->prezzoMin) {
            $query->where('prezzo_acquisto', '>=', $this->prezzoMin);
        }

        if ($this->prezzoMax) {
            $query->where('prezzo_acquisto', '<=', $this->prezzoMax);
        }

        if ($this->dataDocumentoFrom) {
            $query->where('data_carico', '>=', $this->dataDocumentoFrom);
        }

        if ($this->dataDocumentoTo) {
            $query->where('data_carico', '<=', $this->dataDocumentoTo);
        }

        if ($this->soloVetrina) {
            $query->where('in_vetrina', true);
        }

        // Applica sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Eager loading per performance
        $articoli = $query->with([
            'categoria', 
            'sede', 
            'giacenza', 
            'ddtDettaglio.ddt.fornitore',
            'fatturaDettaglio.fattura.fornitore',
            'componentiUtilizzatoIn.prodottoFinito'
        ])
            ->paginate($this->perPage);

        // Statistiche DINAMICHE basate sui filtri applicati
        $baseQuery = $this->getFilteredQuery();
        
        $stats = [
            'totali' => $baseQuery->count(),
            'con_giacenza' => (clone $baseQuery)
                ->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '>', 0);
                })->count(),
            'giacenza_zero' => (clone $baseQuery)
                ->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '=', 0);
                })->count(),
            'giacenza_negativa' => (clone $baseQuery)
                ->whereHas('giacenze', function($q) {
                    $q->where('quantita_residua', '<', 0);
                })->count(),
            'senza_giacenze' => (clone $baseQuery)
                ->whereDoesntHave('giacenze')->count(),
            'in_vetrina' => (clone $baseQuery)->where('in_vetrina', true)->count(),
            'valore_totale' => $this->calcolaValoreTotale($baseQuery),
        ];

        // Opzioni per i filtri - TUTTE le categorie attive con count articoli
        $magazzini = CategoriaMerceologica::where('attivo', true)
            ->withCount('articoli')
            ->orderBy('id')
            ->get();
        
        // Opzioni per filtri avanzati
        $fornitori = Fornitore::where('attivo', true)
            ->orderBy('ragione_sociale')
            ->get(['id', 'ragione_sociale']);
        
        // Marche estratte da JSON caratteristiche
        $marche = DB::table('articoli')
            ->whereNotNull('caratteristiche')
            ->whereRaw("JSON_EXTRACT(caratteristiche, '$.marca') IS NOT NULL")
            ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(caratteristiche, '$.marca')) as marca")
            ->orderBy('marca')
            ->pluck('marca')
            ->filter()
            ->values();
        
        // Sedi per filtro (ex-ubicazioni)
        $sedi = Sede::orderBy('nome')->get();

        return view('livewire.articoli-table', compact('articoli', 'stats', 'magazzini', 'fornitori', 'marche', 'sedi'));
    }
}