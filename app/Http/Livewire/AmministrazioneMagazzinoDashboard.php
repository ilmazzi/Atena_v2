<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Articolo;
use App\Models\Giacenza;
use App\Models\Fattura;
use App\Models\FatturaDettaglio;
use App\Models\Fornitore;
use App\Models\Sede;
use App\Models\CategoriaMerceologica;
use App\Models\ArticoloStoricoCosto;
use App\Exports\StatisticheMagazzinoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AmministrazioneMagazzinoDashboard - Dashboard amministrativa magazzini
 * 
 * Gestisce:
 * - Visualizzazione articoli giacenti con costi
 * - Identificazione articoli senza costo
 * - Inserimento/modifica fatture acquisto e costi
 * - Valorizzazione magazzino per diverse dimensioni
 * - Statistiche amministrative
 */
class AmministrazioneMagazzinoDashboard extends Component
{
    use WithPagination;

    // Filtri
    public $sedeId = '';
    public $fornitoreId = '';
    public $categoriaId = '';
    public $marcaId = ''; // Per future implementazioni marche
    public $search = '';
    public $soloSenzaCosto = false;
    public $soloGiacenti = true;

    // Modal gestione fattura
    public $showFatturaModal = false;
    public $fatturaSelezionata = null;
    public $articoloSelezionato = null;

    // Form fattura
    public $numeroFattura = '';
    public $dataFattura = '';
    public $fornitoreFatturaId = '';
    public $sedeFatturaId = '';

    // Form articolo fattura
    public $quantitaArticolo = 1;
    public $costoUnitarioArticolo = 0;
    public $articoliFattura = [];

    // Statistiche
    public $viewStatistiche = 'sede'; // 'sede', 'fornitore', 'categoria', 'marca', 'globale'
    public $sortStatisticheField = 'valore'; // Campo per sorting statistiche
    public $sortStatisticheDirection = 'desc'; // Direzione sorting
    
    // Confronto periodi
    public $dataInizio = '';
    public $dataFine = '';
    public $mostraConfronto = false;
    
    // Storico costi
    public $showStoricoCostiModal = false;
    public $articoloStorico = null;
    
    // Selezione multipla articoli
    public $articoliSelezionati = []; // Array di ID articoli selezionati
    
    // Filtro ricerca statistiche
    public $searchStatistiche = ''; // Per filtrare le statistiche
    public $mostraTuttiFornitori = false; // Per limitare i fornitori visibili
    public $limiteFornitori = 10; // Numero massimo di fornitori da mostrare di default
    public $mostraTutteMarche = false; // Per limitare le marche visibili
    public $limiteMarche = 10; // Numero massimo di marche da mostrare di default
    

    protected $queryString = [
        'sedeId' => ['except' => ''],
        'fornitoreId' => ['except' => ''],
        'categoriaId' => ['except' => ''],
        'search' => ['except' => ''],
        'soloSenzaCosto' => ['except' => false],
        'soloGiacenti' => ['except' => true],
    ];

    public function mount()
    {
        $this->dataFattura = now()->format('Y-m-d');
        $this->dataInizio = now()->subMonth()->format('Y-m-d');
        $this->dataFine = now()->format('Y-m-d');
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getSediProperty()
    {
        return Sede::where('attivo', true)->orderBy('nome')->get();
    }

    public function getFornitoriProperty()
    {
        return Fornitore::where('attivo', true)->orderBy('ragione_sociale')->get();
    }

    public function getCategorieProperty()
    {
        return CategoriaMerceologica::where('attivo', true)->orderBy('nome')->get();
    }

    public function getArticoliGiacentiProperty()
    {
        $query = Articolo::with([
            'giacenza',
            'categoriaMerceologica',
            'sede',
            'fatturaDettaglio.fattura.fornitore',
            'ddtDettaglio.ddt.fornitore'
        ])
        ->whereHas('giacenza', function ($q) {
            if ($this->soloGiacenti) {
                $q->where('quantita_residua', '>', 0);
            }
        });

        // Filtro sede
        if ($this->sedeId) {
            $query->whereHas('giacenza', function ($q) {
                $q->where('sede_id', $this->sedeId);
            });
        }

        // Filtro categoria
        if ($this->categoriaId) {
            $query->where('categoria_merceologica_id', $this->categoriaId);
        }

        // Filtro marca (dalle caratteristiche JSON) - usa marcaFiltroId per evitare conflitto
        if ($this->marcaId && $this->marcaId !== '' && $this->marcaId !== null) {
            if ($this->marcaId === 'n/a') {
                // Articoli senza marca
                $query->where(function ($q) {
                    $q->whereNull('caratteristiche')
                      ->orWhere('caratteristiche', '{}')
                      ->orWhereRaw("JSON_EXTRACT(caratteristiche, '$.marca') IS NULL")
                      ->orWhereRaw("JSON_EXTRACT(caratteristiche, '$.Marca') IS NULL")
                      ->orWhereRaw("JSON_EXTRACT(caratteristiche, '$.brand') IS NULL")
                      ->orWhereRaw("JSON_EXTRACT(caratteristiche, '$.Brand') IS NULL");
                });
            } else {
                // Articoli con marca specifica (case insensitive)
                $query->where(function ($q) {
                    $marcaLower = strtolower($this->marcaId);
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(caratteristiche, '$.marca'))) = ?", [$marcaLower])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(caratteristiche, '$.Marca'))) = ?", [$marcaLower])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(caratteristiche, '$.brand'))) = ?", [$marcaLower])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(caratteristiche, '$.Brand'))) = ?", [$marcaLower]);
                });
            }
        }

        // Filtro speciale per fornitori (gestisce anche 'n/a' per articoli senza fornitore)
        if ($this->fornitoreId !== null && $this->fornitoreId !== '') {
            if ($this->fornitoreId === 'n/a') {
                // Articoli senza fornitore (né da fattura né da DDT)
                $query->whereDoesntHave('fatturaDettaglio.fattura.fornitore')
                      ->whereDoesntHave('ddtDettaglio.ddt.fornitore');
            } else {
                // Articoli con fornitore specifico (da fattura o DDT)
                $query->where(function ($q) {
                    $q->whereHas('fatturaDettaglio.fattura', function ($subQ) {
                        $subQ->where('fornitore_id', $this->fornitoreId);
                    })->orWhereHas('ddtDettaglio.ddt', function ($subQ) {
                        $subQ->where('fornitore_id', $this->fornitoreId);
                    });
                });
            }
        }

        // Solo senza costo
        if ($this->soloSenzaCosto) {
            $query->where(function ($q) {
                $q->whereNull('prezzo_acquisto')
                  ->orWhere('prezzo_acquisto', 0);
            });
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('codice', 'like', '%' . $this->search . '%')
                  ->orWhere('descrizione', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('codice')->paginate(50);
    }

    public function getStatisticheProperty()
    {
        $stats = [
            'totale_articoli' => 0,
            'totale_valore' => 0,
            'totale_quantita' => 0,
            'articoli_senza_costo' => 0,
            'valore_senza_costo' => 0,
            'per_sede' => [],
            'per_fornitore' => [],
            'per_categoria' => [],
            'per_marca' => [],
        ];

        // Query base articoli giacenti
        $articoliQuery = Articolo::with([
            'giacenza', 
            'fatturaDettaglio.fattura.fornitore',
            'ddtDettaglio.ddt.fornitore'
        ])
            ->whereHas('giacenza', function ($q) {
                $q->where('quantita_residua', '>', 0);
            });

        // Applica filtri se presenti
        if ($this->sedeId) {
            $articoliQuery->whereHas('giacenza', function ($q) {
                $q->where('sede_id', $this->sedeId);
            });
        }

        $articoli = $articoliQuery->get();

        foreach ($articoli as $articolo) {
            $qta = $articolo->giacenza->quantita_residua ?? 0;
            $costo = $articolo->prezzo_acquisto ?? 0;
            $valore = $qta * $costo;

            $stats['totale_articoli']++;
            $stats['totale_quantita'] += $qta;
            
            if ($costo > 0) {
                $stats['totale_valore'] += $valore;
            } else {
                $stats['articoli_senza_costo']++;
            }

            // Per sede
            $sedeId = $articolo->giacenza->sede_id ?? 'n/a';
            if (!isset($stats['per_sede'][$sedeId])) {
                $stats['per_sede'][$sedeId] = [
                    'nome' => $articolo->giacenza->sede->nome ?? 'N/A',
                    'articoli' => 0,
                    'quantita' => 0,
                    'valore' => 0,
                ];
            }
            $stats['per_sede'][$sedeId]['articoli']++;
            $stats['per_sede'][$sedeId]['quantita'] += $qta;
            $stats['per_sede'][$sedeId]['valore'] += $valore;

            // Per fornitore - preferisci fattura, altrimenti DDT
            $fornitore = $articolo->fatturaDettaglio->first()?->fattura?->fornitore 
                        ?? $articolo->ddtDettaglio->first()?->ddt?->fornitore;
            $fornitoreId = $fornitore ? $fornitore->id : 'n/a';
            $fornitoreNome = $fornitore ? $fornitore->ragione_sociale : 'Senza Fornitore';
            
            if (!isset($stats['per_fornitore'][$fornitoreId])) {
                $stats['per_fornitore'][$fornitoreId] = [
                    'nome' => $fornitoreNome,
                    'articoli' => 0,
                    'quantita' => 0,
                    'valore' => 0,
                ];
            }
            $stats['per_fornitore'][$fornitoreId]['articoli']++;
            $stats['per_fornitore'][$fornitoreId]['quantita'] += $qta;
            $stats['per_fornitore'][$fornitoreId]['valore'] += $valore;

            // Per categoria
            $categoriaId = $articolo->categoria_merceologica_id ?? 'n/a';
            if (!isset($stats['per_categoria'][$categoriaId])) {
                $stats['per_categoria'][$categoriaId] = [
                    'nome' => $articolo->categoriaMerceologica->nome ?? 'N/A',
                    'articoli' => 0,
                    'quantita' => 0,
                    'valore' => 0,
                ];
            }
            $stats['per_categoria'][$categoriaId]['articoli']++;
            $stats['per_categoria'][$categoriaId]['quantita'] += $qta;
            $stats['per_categoria'][$categoriaId]['valore'] += $valore;

            // Per marca (estratto da caratteristiche JSON)
            $marca = null;
            if ($articolo->caratteristiche) {
                $caratteristiche = is_string($articolo->caratteristiche) 
                    ? json_decode($articolo->caratteristiche, true) 
                    : $articolo->caratteristiche;
                
                if (is_array($caratteristiche)) {
                    // Cerca 'marca' o 'brand' nelle caratteristiche (case insensitive)
                    $marca = $caratteristiche['marca'] ?? $caratteristiche['Marca'] ?? 
                             $caratteristiche['brand'] ?? $caratteristiche['Brand'] ?? null;
                }
            }
            
            $marcaId = $marca ? strtolower(trim($marca)) : 'n/a';
            $marcaNome = $marca ? trim($marca) : 'Senza Marca';
            
            if (!isset($stats['per_marca'][$marcaId])) {
                $stats['per_marca'][$marcaId] = [
                    'nome' => $marcaNome,
                    'articoli' => 0,
                    'quantita' => 0,
                    'valore' => 0,
                ];
            }
            $stats['per_marca'][$marcaId]['articoli']++;
            $stats['per_marca'][$marcaId]['quantita'] += $qta;
            $stats['per_marca'][$marcaId]['valore'] += $valore;
        }

        return $stats;
    }

    // ==========================================
    // ACTIONS
    // ==========================================

    public function resetFiltri()
    {
        $this->reset(['sedeId', 'fornitoreId', 'categoriaId', 'search', 'soloSenzaCosto']);
        $this->soloGiacenti = true;
    }

    public function apriFatturaModal($articoloId = null)
    {
        \Log::info('🔓 APERTURA MODAL FATTURA', ['articolo_id' => $articoloId, 'selezionati' => $this->articoliSelezionati]);
        
        // Reset valori precedenti
        $this->reset(['articoliFattura', 'quantitaArticolo', 'costoUnitarioArticolo']);
        $this->quantitaArticolo = 1;
        $this->costoUnitarioArticolo = 0;
        $this->articoloSelezionato = null;
        
        // Se ci sono articoli selezionati multipli, li aggiungi tutti
        if (!empty($this->articoliSelezionati) && count($this->articoliSelezionati) > 0) {
            $articoli = Articolo::whereIn('id', $this->articoliSelezionati)->get();
            foreach ($articoli as $art) {
                $this->articoliFattura[] = [
                    'articolo_id' => $art->id,
                    'quantita' => 1,
                    'costo_unitario' => $art->prezzo_acquisto ?? 0,
                ];
            }
            \Log::info('📦 ARTICOLI MULTIPLI AGGIUNTI', ['count' => count($this->articoliFattura)]);
        } 
        // Se è un singolo articolo, aggiungilo direttamente
        elseif ($articoloId) {
            $articolo = Articolo::with(['fatturaDettaglio.fattura'])->findOrFail($articoloId);
            $this->articoliFattura[] = [
                'articolo_id' => $articolo->id,
                'quantita' => 1,
                'costo_unitario' => $articolo->prezzo_acquisto ?? 0,
            ];
            \Log::info('📦 ARTICOLO SINGOLO AGGIUNTO', [
                'id' => $articolo->id,
                'codice' => $articolo->codice,
            ]);
        }
        
        $this->showFatturaModal = true;
    }
    
    public function toggleSelezioneArticolo($articoloId)
    {
        if (in_array($articoloId, $this->articoliSelezionati)) {
            $this->articoliSelezionati = array_values(array_diff($this->articoliSelezionati, [$articoloId]));
        } else {
            $this->articoliSelezionati[] = $articoloId;
        }
    }
    
    public function deselezionaTuttiArticoli()
    {
        $this->articoliSelezionati = [];
    }
    
    public function selezionaTuttiArticoli()
    {
        $articoli = $this->articoliGiacenti->items();
        $this->articoliSelezionati = collect($articoli)->pluck('id')->toArray();
    }

    public function aggiungiArticoloAllaFattura($articoloId = null)
    {
        if (!$articoloId && !$this->articoloSelezionato) {
            session()->flash('error', 'Nessun articolo selezionato');
            return;
        }

        $articolo = $articoloId ? Articolo::find($articoloId) : $this->articoloSelezionato;
        if (!$articolo) {
            session()->flash('error', 'Articolo non trovato');
            return;
        }

        $costo = floatval($this->costoUnitarioArticolo ?? $articolo->prezzo_acquisto ?? 0);
        $quantita = intval($this->quantitaArticolo ?? 1);

        if ($costo <= 0) {
            session()->flash('error', 'Il costo deve essere maggiore di 0');
            return;
        }

        $nextIndex = count(array_filter($this->articoliFattura ?? []));
        
        if (!is_array($this->articoliFattura)) {
            $this->articoliFattura = [];
        }

        // Verifica se l'articolo è già nella lista
        $giaPresente = collect($this->articoliFattura)->contains(function ($art) use ($articolo) {
            return isset($art['articolo_id']) && $art['articolo_id'] == $articolo->id;
        });

        if ($giaPresente) {
            session()->flash('info', 'Articolo già presente nella fattura');
            return;
        }

        $this->articoliFattura[$nextIndex] = [
            'articolo_id' => $articolo->id,
            'quantita' => $quantita,
            'costo_unitario' => $costo,
        ];

        \Log::info('➕ ARTICOLO AGGIUNTO ALLA FATTURA', [
            'articolo_id' => $articolo->id,
            'quantita' => $quantita,
            'costo_unitario' => $costo,
            'totale_articoli' => count($this->articoliFattura),
        ]);

        // Reset campi
        $this->quantitaArticolo = 1;
        $this->costoUnitarioArticolo = 0;
        $this->articoloSelezionato = null;
        
        session()->flash('success', 'Articolo aggiunto alla fattura!');
    }

    public function rimuoviArticoloDallaFattura($index)
    {
        if (isset($this->articoliFattura[$index])) {
            unset($this->articoliFattura[$index]);
            $this->articoliFattura = array_values($this->articoliFattura); // Reindizza array
        }
    }

    public function ordinaStatistiche($campo)
    {
        if ($this->sortStatisticheField === $campo) {
            $this->sortStatisticheDirection = $this->sortStatisticheDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortStatisticheField = $campo;
            $this->sortStatisticheDirection = 'desc';
        }
    }

    public function filtraPerSede($sedeId)
    {
        $this->sedeId = $sedeId;
        $this->resetPage();
        // Scroll alla tabella articoli
        $this->dispatch('scroll-to-articles');
    }

    public function filtraPerFornitore($fornitoreId)
    {
        // Imposta il filtro fornitore (può essere un ID numerico o 'n/a')
        $this->fornitoreId = $fornitoreId;
        $this->resetPage();
        // Scroll alla tabella articoli
        $this->dispatch('scroll-to-articles');
    }

    public function filtraPerCategoria($categoriaId)
    {
        $this->categoriaId = $categoriaId;
        $this->resetPage();
        // Scroll alla tabella articoli
        $this->dispatch('scroll-to-articles');
    }

    public function getStatisticheOrdinateProperty()
    {
        $statistiche = $this->statistiche;
        
        if ($this->viewStatistiche === 'sede' && isset($statistiche['per_sede'])) {
            $statistiche['per_sede'] = collect($statistiche['per_sede'])
                ->sortBy($this->sortStatisticheField, SORT_REGULAR, $this->sortStatisticheDirection === 'desc')
                ->toArray();
        } elseif ($this->viewStatistiche === 'fornitore' && isset($statistiche['per_fornitore'])) {
            $collection = collect($statistiche['per_fornitore'])
                ->sortBy($this->sortStatisticheField, SORT_REGULAR, $this->sortStatisticheDirection === 'desc');
            
            // Applica filtro ricerca se presente
            if ($this->searchStatistiche) {
                $collection = $collection->filter(function ($item, $key) {
                    return stripos($item['nome'], $this->searchStatistiche) !== false;
                });
            }
            
            $statistiche['per_fornitore'] = $collection->toArray();
        } elseif ($this->viewStatistiche === 'categoria' && isset($statistiche['per_categoria'])) {
            $statistiche['per_categoria'] = collect($statistiche['per_categoria'])
                ->sortBy($this->sortStatisticheField, SORT_REGULAR, $this->sortStatisticheDirection === 'desc')
                ->toArray();
        } elseif ($this->viewStatistiche === 'marca' && isset($statistiche['per_marca'])) {
            $collection = collect($statistiche['per_marca'])
                ->sortBy($this->sortStatisticheField, SORT_REGULAR, $this->sortStatisticheDirection === 'desc');
            
            // Applica filtro ricerca se presente
            if ($this->searchStatistiche) {
                $collection = $collection->filter(function ($item, $key) {
                    return stripos($item['nome'], $this->searchStatistiche) !== false;
                });
            }
            
            $statistiche['per_marca'] = $collection->toArray();
        }
        
        return $statistiche;
    }
    
    public function getFornitoriVisibiliProperty()
    {
        $statistiche = $this->statisticheOrdinate;
        if (!isset($statistiche['per_fornitore'])) {
            return [];
        }
        
        $fornitori = $statistiche['per_fornitore'];
        
        if ($this->mostraTuttiFornitori) {
            return $fornitori;
        }
        
        return array_slice($fornitori, 0, $this->limiteFornitori, true);
    }
    
    public function toggleMostraTuttiFornitori()
    {
        $this->mostraTuttiFornitori = !$this->mostraTuttiFornitori;
    }
    
    public function getMarcheVisibiliProperty()
    {
        $statistiche = $this->statisticheOrdinate;
        if (!isset($statistiche['per_marca'])) {
            return [];
        }
        
        $marche = $statistiche['per_marca'];
        
        if ($this->mostraTutteMarche) {
            return $marche;
        }
        
        return array_slice($marche, 0, $this->limiteMarche, true);
    }
    
    public function toggleMostraTutteMarche()
    {
        $this->mostraTutteMarche = !$this->mostraTutteMarche;
    }
    
    public function filtraPerMarca($marcaId)
    {
        // Se marcaId è 'n/a', cerca articoli senza marca
        $this->marcaId = $marcaId;
        $this->resetPage();
        // Scroll alla tabella articoli
        $this->dispatch('scroll-to-articles');
    }

    public function chiudiFatturaModal()
    {
        $this->showFatturaModal = false;
        $this->reset(['fatturaSelezionata', 'articoloSelezionato', 'numeroFattura', 'dataFattura', 
                     'fornitoreFatturaId', 'sedeFatturaId', 'articoliFattura', 'quantitaArticolo', 'costoUnitarioArticolo']);
    }

    public function salvaFattura()
    {
        $this->validate([
            'numeroFattura' => 'required|string|max:50',
            'dataFattura' => 'required|date',
            'fornitoreFatturaId' => 'required|exists:fornitori,id',
            'sedeFatturaId' => 'required|exists:sedi,id',
        ]);

        // Verifica che ci siano articoli nella fattura
        $articoliValidati = collect($this->articoliFattura)
            ->filter(function ($art) {
                return !empty($art) && isset($art['articolo_id']) && !empty($art['articolo_id']) 
                    && isset($art['costo_unitario']) && $art['costo_unitario'] > 0;
            });

        if ($articoliValidati->isEmpty()) {
            session()->flash('error', 'ERRORE: Devi aggiungere almeno un articolo con costo alla fattura. Clicca "Aggiungi alla Fattura" dopo aver inserito il costo.');
            return;
        }

        \Log::info('💾 SALVATAGGIO FATTURA', [
            'numero' => $this->numeroFattura,
            'articoli_count' => $articoliValidati->count(),
            'articoli' => $articoliValidati->toArray(),
        ]);

        DB::transaction(function () use ($articoliValidati) {
            // Crea o aggiorna fattura
            $fattura = Fattura::updateOrCreate(
                [
                    'numero' => $this->numeroFattura,
                    'anno' => date('Y', strtotime($this->dataFattura)),
                    'fornitore_id' => $this->fornitoreFatturaId,
                ],
                [
                    'data_documento' => $this->dataFattura,
                    'sede_id' => $this->sedeFatturaId,
                    'stato' => 'caricata',
                    'totale' => 0,
                    'imponibile' => 0,
                    'iva' => 0,
                ]
            );

            // Salva articoli della fattura
            foreach ($articoliValidati as $art) {
                    // Salva storico costo prima di aggiornare - carica articolo
                    $articolo = Articolo::find($art['articolo_id']);
                    
                    if (!$articolo) {
                        \Log::error('❌ ARTICOLO NON TROVATO', ['articolo_id' => $art['articolo_id']]);
                        continue;
                    }
                    
                    // Calcola totali riga
                    $quantita = $art['quantita'] ?? 1;
                    $prezzoUnitario = $art['costo_unitario'] ?? 0;
                    $totaleRiga = $quantita * $prezzoUnitario;
                    
                    // Crea dettaglio fattura
                    FatturaDettaglio::updateOrCreate(
                        [
                            'fattura_id' => $fattura->id,
                            'articolo_id' => $articolo->id,
                        ],
                        [
                            'quantita' => $quantita,
                            'prezzo_unitario' => $prezzoUnitario,
                            'totale_riga' => $totaleRiga,
                            'codice_articolo' => $articolo->codice ?? null,
                            'descrizione' => $articolo->descrizione ?? null,
                            'caricato' => true,
                        ]
                    );

                    // Aggiorna costo articolo (l'articolo è già stato trovato sopra)
                    $costoPrecedente = $articolo->prezzo_acquisto;
                    
                    \Log::info('📝 AGGIORNAMENTO ARTICOLO', [
                        'articolo_id' => $articolo->id,
                        'codice' => $articolo->codice,
                        'costo_precedente' => $costoPrecedente,
                        'costo_nuovo' => $prezzoUnitario,
                    ]);
                    
                    // Registra storico solo se il costo è cambiato
                    if ($costoPrecedente != $prezzoUnitario) {
                        ArticoloStoricoCosto::create([
                            'articolo_id' => $articolo->id,
                            'costo_precedente' => $costoPrecedente,
                            'costo_nuovo' => $prezzoUnitario,
                            'fattura_id' => $fattura->id,
                            'user_id' => auth()->id() ?? null,
                            'note' => "Costo aggiornato da fattura {$fattura->numero}",
                        ]);
                    }
                    
                    // Aggiorna costo articolo
                    $articolo->update(['prezzo_acquisto' => $prezzoUnitario]);
                    
                    \Log::info('✅ ARTICOLO AGGIORNATO', [
                        'articolo_id' => $articolo->id,
                        'prezzo_acquisto' => $articolo->fresh()->prezzo_acquisto,
                    ]);
            }

            // Recalcola totale fattura
            $totale = $articoliValidati->sum(function ($art) {
                return ($art['costo_unitario'] ?? 0) * ($art['quantita'] ?? 1);
            });
            
            $fattura->update([
                'totale' => $totale,
                'imponibile' => $totale,
                'quantita_totale' => $articoliValidati->sum('quantita'),
                'numero_articoli' => $articoliValidati->count(),
            ]);
            
            \Log::info('💰 FATTURA SALVATA', [
                'fattura_id' => $fattura->id,
                'numero' => $fattura->numero,
                'totale' => $totale,
                'numero_articoli' => $articoliValidati->count(),
            ]);

            session()->flash('success', 'Fattura salvata con successo! Costi aggiornati.');
        });

        $this->chiudiFatturaModal();
        
        // Reset selezione dopo salvataggio
        $this->articoliSelezionati = [];
        
        // Refresh articoli per vedere i cambiamenti
        $this->resetPage();
    }


    public function updatedViewStatistiche()
    {
        $this->resetPage();
    }

    public function apriStoricoCosti($articoloId)
    {
        $this->articoloStorico = Articolo::with(['storicoCosti.user', 'storicoCosti.fattura'])
            ->findOrFail($articoloId);
        $this->showStoricoCostiModal = true;
    }

    public function chiudiStoricoCostiModal()
    {
        $this->showStoricoCostiModal = false;
        $this->articoloStorico = null;
    }

    public function exportExcel()
    {
        $filtri = [
            'sedeId' => $this->sedeId,
            'fornitoreId' => $this->fornitoreId,
            'categoriaId' => $this->categoriaId,
            'search' => $this->search,
            'soloSenzaCosto' => $this->soloSenzaCosto,
            'soloGiacenti' => $this->soloGiacenti,
        ];

        $export = new StatisticheMagazzinoExport($filtri, $this->statistiche);
        
        $filename = 'statistiche_magazzino_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download($export, $filename);
    }

    public function exportPdf()
    {
        // Export PDF implementato tramite print browser
        session()->flash('info', 'Usa il pulsante Stampa del browser per salvare come PDF');
    }

    public function getStatisticheConfrontoProperty()
    {
        if (!$this->mostraConfronto || !$this->dataInizio || !$this->dataFine) {
            return null;
        }

        $dataInizio = Carbon::parse($this->dataInizio)->startOfDay();
        $dataFine = Carbon::parse($this->dataFine)->endOfDay();

        // Calcola valorizzazione al periodo precedente
        $articoliPeriodoPrecedente = Articolo::with(['giacenza', 'fatturaDettaglio.fattura.fornitore'])
            ->whereHas('giacenza', function ($q) {
                $q->where('quantita_residua', '>', 0);
            })
            ->where('created_at', '<', $dataInizio)
            ->get();

        $valorePeriodoPrecedente = $articoliPeriodoPrecedente->sum(function ($art) {
            $qta = $art->giacenza->quantita_residua ?? 0;
            $costo = $art->prezzo_acquisto ?? 0;
            return $qta * $costo;
        });

        // Calcola valorizzazione attuale
        $valoreAttuale = $this->statistiche['totale_valore'];

        return [
            'valore_precedente' => $valorePeriodoPrecedente,
            'valore_attuale' => $valoreAttuale,
            'variazione' => $valoreAttuale - $valorePeriodoPrecedente,
            'variazione_percentuale' => $valorePeriodoPrecedente > 0 
                ? (($valoreAttuale - $valorePeriodoPrecedente) / $valorePeriodoPrecedente) * 100 
                : 0,
        ];
    }

    public function render()
    {
        return view('livewire.amministrazione-magazzino-dashboard', [
            'articoli' => $this->articoliGiacenti,
            'statistiche' => $this->statisticheOrdinate,
            'statisticheConfronto' => $this->statisticheConfronto,
            'sedi' => $this->sedi,
            'fornitori' => $this->fornitori,
            'categorie' => $this->categorie,
        ]);
    }
}
