<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ContoDeposito;
use App\Models\Articolo;
use App\Models\ProdottoFinito;
use App\Services\ContoDepositoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * GestisciContoDeposito - Gestione singolo conto deposito
 * 
 * Permette di:
 * - Visualizzare dettagli deposito
 * - Aggiungere articoli/PF al deposito
 * - Registrare vendite
 * - Gestire resi
 */
class GestisciContoDeposito extends Component
{
    use WithPagination;

    public $depositoId;
    public $deposito;

    // Modali
    public $showAggiungiArticoliModal = false;
    public $showRegistraVenditaModal = false;

    // Form aggiunta articoli
    public $search = '';
    public $tipoItem = 'articoli'; // 'articoli' o 'prodotti_finiti'
    public $articoliSelezionati = [];
    public $prodottiFinitiSelezionati = [];

    // Form vendita
    public $itemVendita = null;
    public $quantitaVendita = 1;
    
    // Form vendita multipla
    public $showVenditaMultiplaModal = false;
    public $articoliSelezionatiVendita = [];
    public $prodottiFinitiSelezionatiVendita = [];
    
    // Dati fattura vendita
    public $numeroFattura = '';
    public $dataFattura = '';
    public $clienteNome = '';
    public $clienteCognome = '';
    public $clienteTelefono = '';
    public $clienteEmail = '';
    public $importoTotaleFattura = 0;
    public $noteFattura = '';

    protected $rules = [
        'articoliSelezionati.*.quantita' => 'required|integer|min:1',
        'quantitaVendita' => 'required|integer|min:1',
        
        // Regole vendita multipla - OPZIONALI
        'numeroFattura' => 'nullable|string|max:50',
        'dataFattura' => 'nullable|date',
        'clienteNome' => 'nullable|string|max:100',
        'clienteCognome' => 'nullable|string|max:100',
        'clienteTelefono' => 'nullable|string|max:20',
        'clienteEmail' => 'nullable|email|max:100',
        'importoTotaleFattura' => 'nullable|numeric|min:0.01',
        'noteFattura' => 'nullable|string|max:500',
        
        // Selezioni - OPZIONALI
        'articoliSelezionatiVendita' => 'nullable|array',
        'prodottiFinitiSelezionatiVendita' => 'nullable|array',
    ];

    public function mount($depositoId)
    {
        $this->depositoId = $depositoId;
        $this->deposito = ContoDeposito::with([
            'sedeMittente', 
            'sedeDestinataria',
            'movimenti.articolo.giacenza',
            'movimenti.prodottoFinito',
            'creatoDa'
        ])->findOrFail($depositoId);
        
        // Inizializza data fattura ad oggi
        $this->dataFattura = now()->format('Y-m-d');
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getArticoliDisponibiliProperty()
    {
        if (!$this->showAggiungiArticoliModal || $this->tipoItem !== 'articoli') {
            return collect();
        }

        return Articolo::with(['categoriaMerceologica', 'sede', 'giacenza'])
            ->where('sede_id', $this->deposito->sede_mittente_id)
            ->whereHas('giacenza', function ($query) {
                $query->where('quantita_residua', '>', 0);
            })
            ->where('in_vetrina', false)
            ->where(function ($query) {
                $query->whereNull('conto_deposito_corrente_id')
                      ->orWhere('quantita_in_deposito', 0);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('codice', 'like', '%' . $this->search . '%')
                      ->orWhere('descrizione', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('codice')
            ->limit(50)
            ->get();
    }

    public function getProdottiFinitiDisponibiliProperty()
    {
        if (!$this->showAggiungiArticoliModal || $this->tipoItem !== 'prodotti_finiti') {
            return collect();
        }

        return ProdottoFinito::with(['categoriaMerceologica'])
            ->where('stato', 'completato')
            ->where('in_conto_deposito', false)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('codice', 'like', '%' . $this->search . '%')
                      ->orWhere('descrizione', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('codice')
            ->limit(50)
            ->get();
    }

    public function getArticoliInDepositoProperty()
    {
        $service = new ContoDepositoService();
        return $service->getArticoliRimanentiInDeposito($this->deposito);
    }

    public function getProdottiFinitiInDepositoProperty()
    {
        $service = new ContoDepositoService();
        return $service->getProdottiFinitiRimanentiInDeposito($this->deposito);
    }

    // ==========================================
    // ACTIONS - GESTIONE ARTICOLI
    // ==========================================

    public function apriAggiungiArticoliModal()
    {
        $this->reset(['search', 'articoliSelezionati', 'prodottiFinitiSelezionati']);
        $this->tipoItem = 'articoli';
        $this->showAggiungiArticoliModal = true;
    }

    public function chiudiAggiungiArticoliModal()
    {
        $this->showAggiungiArticoliModal = false;
        $this->resetValidation();
    }

    public function toggleArticolo($articoloId)
    {
        if (isset($this->articoliSelezionati[$articoloId])) {
            unset($this->articoliSelezionati[$articoloId]);
        } else {
            $articolo = Articolo::with('giacenza')->find($articoloId);
            $this->articoliSelezionati[$articoloId] = [
                'articolo_id' => $articoloId,
                'quantita' => 1,
                'max_quantita' => $articolo->getQuantitaDisponibile(),
                'costo_unitario' => $articolo->prezzo_acquisto ?? 0
            ];
        }
    }

    public function toggleProdottoFinito($pfId)
    {
        if (isset($this->prodottiFinitiSelezionati[$pfId])) {
            unset($this->prodottiFinitiSelezionati[$pfId]);
        } else {
            $pf = ProdottoFinito::find($pfId);
            $this->prodottiFinitiSelezionati[$pfId] = [
                'prodotto_finito_id' => $pfId,
                'costo_unitario' => $pf->costo_totale ?? 0
            ];
        }
    }

    public function aggiungiArticoliAlDeposito()
    {
        // Validazione specifica per aggiunta articoli (solo quantità degli articoli selezionati)
        $this->validate([
            'articoliSelezionati.*.quantita' => 'required|integer|min:1',
        ]);

        try {
            $service = new ContoDepositoService();
            $articoliAggiunti = 0;

            // Aggiungi articoli selezionati
            foreach ($this->articoliSelezionati as $articoloData) {
                $service->inviaArticoloInDeposito(
                    $this->deposito,
                    $articoloData['articolo_id'],
                    $articoloData['quantita'],
                    $articoloData['costo_unitario']
                );
                $articoliAggiunti++;
            }

            // Aggiungi prodotti finiti selezionati
            foreach ($this->prodottiFinitiSelezionati as $pfData) {
                $service->inviaProdottoFinitoInDeposito(
                    $this->deposito,
                    $pfData['prodotto_finito_id'],
                    $pfData['costo_unitario']
                );
                $articoliAggiunti++;
            }

            // Aggiorna statistiche deposito
            $this->deposito->aggiornaStatistiche();
            $this->deposito->refresh();

            session()->flash('success', "{$articoliAggiunti} articoli/PF aggiunti al deposito");
            $this->chiudiAggiungiArticoliModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'aggiunta: ' . $e->getMessage());
        }
    }

    // ==========================================
    // ACTIONS - VENDITE
    // ==========================================
    
    /**
     * Apre il modal per vendita multipla con fattura
     */
    public function apriVenditaMultiplaModal()
    {
        // NON resettare le selezioni! Solo i campi fattura
        $this->reset([
            'numeroFattura',
            'clienteNome',
            'clienteCognome', 
            'clienteTelefono',
            'clienteEmail',
            'importoTotaleFattura',
            'noteFattura'
        ]);
        
        $this->dataFattura = now()->format('Y-m-d');
        $this->showVenditaMultiplaModal = true;
    }
    
    /**
     * Chiude il modal vendita multipla
     */
    public function chiudiVenditaMultiplaModal()
    {
        $this->showVenditaMultiplaModal = false;
        $this->resetValidation();
    }
    
    /**
     * Toggle selezione articolo per vendita
     */
    public function toggleArticoloVendita($articoloId)
    {
        if (isset($this->articoliSelezionatiVendita[$articoloId])) {
            unset($this->articoliSelezionatiVendita[$articoloId]);
        } else {
            $articoloData = $this->articoliInDeposito->firstWhere('articolo.id', $articoloId);
            if ($articoloData) {
                // SOLO dati essenziali, NO oggetti Eloquent
                $this->articoliSelezionatiVendita[$articoloId] = [
                    'articolo_id' => $articoloId,
                    'quantita' => min(1, $articoloData['quantita']),
                    'max_quantita' => $articoloData['quantita'],
                    'costo_unitario' => $articoloData['costo_unitario'],
                    // Dati per display (solo stringhe/numeri)
                    'codice' => $articoloData['articolo']['codice'] ?? '',
                    'descrizione' => $articoloData['articolo']['descrizione'] ?? '',
                ];
            }
        }
        
        $this->calcolaImportoTotale();
    }
    
    /**
     * Toggle selezione prodotto finito per vendita
     */
    public function toggleProdottoFinitoVendita($pfId)
    {
        // Debug log per verificare che il metodo viene chiamato
        \Log::info("toggleProdottoFinitoVendita chiamato con ID: {$pfId}");
        
        if (isset($this->prodottiFinitiSelezionatiVendita[$pfId])) {
            unset($this->prodottiFinitiSelezionatiVendita[$pfId]);
            \Log::info("PF {$pfId} rimosso dalla selezione");
        } else {
            $pfData = $this->prodottiFinitiInDeposito->firstWhere('prodotto_finito.id', $pfId);
            if ($pfData) {
                // SOLO dati essenziali, NO oggetti Eloquent
                $this->prodottiFinitiSelezionatiVendita[$pfId] = [
                    'prodotto_finito_id' => $pfId,
                    'quantita' => 1,
                    'costo_unitario' => $pfData['costo_unitario'],
                    // Dati per display (solo stringhe/numeri)
                    'codice' => $pfData['prodotto_finito']['codice'] ?? '',
                    'descrizione' => $pfData['prodotto_finito']['descrizione'] ?? '',
                ];
                \Log::info("PF {$pfId} aggiunto alla selezione");
            } else {
                \Log::error("PF {$pfId} non trovato nella collection prodottiFinitiInDeposito");
            }
        }
        
        $this->calcolaImportoTotale();
        
        // Debug della selezione attuale
        \Log::info("Selezione attuale: " . count($this->prodottiFinitiSelezionatiVendita) . " PF selezionati");
    }
    
    /**
     * Calcola automaticamente l'importo totale
     */
    public function calcolaImportoTotale()
    {
        $totale = 0;
        
        // Somma articoli selezionati
        foreach ($this->articoliSelezionatiVendita as $articolo) {
            $totale += $articolo['quantita'] * $articolo['costo_unitario'];
        }
        
        // Somma prodotti finiti selezionati
        foreach ($this->prodottiFinitiSelezionatiVendita as $pf) {
            $totale += $pf['costo_unitario'];
        }
        
        $this->importoTotaleFattura = $totale;
    }
    
    /**
     * Aggiorna quantità e ricalcola totale
     */
    public function updatedArticoliSelezionatiVendita()
    {
        $this->calcolaImportoTotale();
    }
    
    /**
     * Registra vendita multipla con fattura
     */
    public function registraVenditaMultipla()
    {
        \Log::info("🔥 registraVenditaMultipla CHIAMATO!");
        \Log::info("📊 Selezioni: Articoli=" . count($this->articoliSelezionatiVendita) . ", PF=" . count($this->prodottiFinitiSelezionatiVendita));
        \Log::info("📝 Campi fattura: numeroFattura='{$this->numeroFattura}', clienteNome='{$this->clienteNome}', clienteCognome='{$this->clienteCognome}'");
        
        // Validazione specifica per vendita multipla
        \Log::info("🔍 Pre-validazione...");
        try {
            $this->validate([
                'numeroFattura' => 'required|string|max:50',
                'dataFattura' => 'required|date',
                'clienteNome' => 'required|string|max:100',
                'clienteCognome' => 'required|string|max:100',
                'clienteTelefono' => 'nullable|string|max:20',
                'clienteEmail' => 'nullable|email|max:100',
                'importoTotaleFattura' => 'required|numeric|min:0',
                'noteFattura' => 'nullable|string|max:500',
            ]);
            \Log::info("✅ Validazione OK!");
        } catch (\Exception $e) {
            \Log::error("❌ Validazione FALLITA: " . $e->getMessage());
            throw $e;
        }
        
        \Log::info("🔍 Controllo selezioni...");
        if (empty($this->articoliSelezionatiVendita) && empty($this->prodottiFinitiSelezionatiVendita)) {
            \Log::error("❌ Nessuna selezione trovata!");
            session()->flash('error', 'Seleziona almeno un articolo o prodotto finito da vendere');
            return;
        }
        \Log::info("✅ Selezioni trovate, procedo...");

        \Log::info("🚀 Inizio transazione...");
        try {
            DB::transaction(function () {
                \Log::info("📦 Dentro transazione DB...");
                
                // TODO: Creare record fattura se necessario
                // $fattura = $this->creaFatturaVendita();
                
                \Log::info("🔧 Creazione ContoDepositoService...");
                $service = new ContoDepositoService();
                \Log::info("✅ ContoDepositoService creato!");
                
                \Log::info("🛒 Inizio registrazione vendite...");
                
                // Registra vendite articoli
                \Log::info("🔍 Articoli selezionati: " . count($this->articoliSelezionatiVendita));
                foreach ($this->articoliSelezionatiVendita as $articoloId => $articoloData) {
                    \Log::info("📦 Registro vendita articolo ID: {$articoloId}...");
                    $articolo = Articolo::findOrFail($articoloId);
                    $service->registraVendita(
                        $this->deposito,
                        $articolo,
                        $articoloData['quantita']
                    );
                }
                
                // Registra vendite prodotti finiti
                \Log::info("🔍 PF selezionati: " . count($this->prodottiFinitiSelezionatiVendita));
                foreach ($this->prodottiFinitiSelezionatiVendita as $pfId => $pfData) {
                    \Log::info("🏆 Registro vendita PF ID: {$pfId}...");
                    $prodottoFinito = ProdottoFinito::findOrFail($pfId);
                    $service->registraVendita(
                        $this->deposito,
                        $prodottoFinito,
                        1
                    );
                }
                
                // Aggiorna deposito
                $this->deposito->refresh();
            });
            
            $totaleItemsVenduti = count($this->articoliSelezionatiVendita) + count($this->prodottiFinitiSelezionatiVendita);
            
            \Log::info("🎉 VENDITA COMPLETATA! Items venduti: {$totaleItemsVenduti}");
            
            // Reset selezioni dopo vendita
            $this->articoliSelezionatiVendita = [];
            $this->prodottiFinitiSelezionatiVendita = [];
            
            session()->flash('success', "🎉 Vendita registrata con successo! {$totaleItemsVenduti} articoli venduti per €" . number_format($this->importoTotaleFattura, 2, ',', '.'));
            
            $this->chiudiVenditaMultiplaModal();
            
            // Forza refresh della pagina per mostrare i cambiamenti
            $this->redirect(route('conti-deposito.gestisci', $this->depositoId));
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante la registrazione: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se un articolo è selezionato per vendita
     */
    public function isArticoloSelezionatoVendita($articoloId): bool
    {
        return isset($this->articoliSelezionatiVendita[$articoloId]);
    }
    
    /**
     * Verifica se un PF è selezionato per vendita
     */
    public function isProdottoFinitoSelezionatoVendita($pfId): bool
    {
        return isset($this->prodottiFinitiSelezionatiVendita[$pfId]);
    }
    
    /**
     * Ottiene il totale articoli selezionati per vendita
     */
    public function getTotaleSelezionatiVendita(): int
    {
        return count($this->articoliSelezionatiVendita) + count($this->prodottiFinitiSelezionatiVendita);
    }

    public function apriRegistraVenditaModal($tipo, $itemId)
    {
        if ($tipo === 'articolo') {
            $articoloData = $this->articoliInDeposito->firstWhere('articolo.id', $itemId);
            $this->itemVendita = [
                'tipo' => 'articolo',
                'item' => $articoloData['articolo'],
                'quantita_disponibile' => $articoloData['quantita'],
                'costo_unitario' => $articoloData['costo_unitario']
            ];
        } else {
            $pfData = $this->prodottiFinitiInDeposito->firstWhere('prodotto_finito.id', $itemId);
            $this->itemVendita = [
                'tipo' => 'prodotto_finito',
                'item' => $pfData['prodotto_finito'],
                'quantita_disponibile' => 1,
                'costo_unitario' => $pfData['costo_unitario']
            ];
        }

        $this->quantitaVendita = 1;
        $this->showRegistraVenditaModal = true;
    }

    public function chiudiRegistraVenditaModal()
    {
        $this->showRegistraVenditaModal = false;
        $this->itemVendita = null;
        $this->resetValidation();
    }

    public function registraVendita()
    {
        // Validazione specifica per vendita singola
        $this->validate([
            'quantitaVendita' => 'required|integer|min:1',
        ]);

        try {
            $service = new ContoDepositoService();
            
            $service->registraVendita(
                $this->deposito,
                $this->itemVendita['item'],
                $this->quantitaVendita
            );

            // Aggiorna deposito
            $this->deposito->refresh();

            session()->flash('success', 'Vendita registrata con successo');
            $this->chiudiRegistraVenditaModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante la registrazione: ' . $e->getMessage());
        }
    }

    // ==========================================
    // ACTIONS - DDT
    // ==========================================

    public function generaDdtInvio()
    {
        try {
            $service = new ContoDepositoService();
            $ddtDeposito = $service->generaDdtInvio($this->deposito);
            
            // Aggiorna deposito
            $this->deposito->refresh();

            session()->flash('success', "DDT di invio {$ddtDeposito->numero} generato con successo");
            
            // Redirect alla stampa DDT Deposito
            return redirect()->route('ddt-deposito.stampa', $ddtDeposito->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante la generazione DDT: ' . $e->getMessage());
        }
    }

    public function generaDdtReso()
    {
        try {
            $service = new ContoDepositoService();
            
            // Prima gestisci il reso automatico
            $movimentiReso = $service->gestisciResoScadenza($this->deposito);
            
            // Poi genera il DDT
            $ddtDeposito = $service->generaDdtReso($this->deposito);
            
            // Aggiorna deposito
            $this->deposito->refresh();

            session()->flash('success', "DDT di reso {$ddtDeposito->numero} generato per {$movimentiReso->count()} articoli");
            
            // Redirect alla stampa DDT Deposito
            return redirect()->route('ddt-deposito.stampa', $ddtDeposito->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante la generazione DDT reso: ' . $e->getMessage());
        }
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function isArticoloSelezionato($articoloId): bool
    {
        return isset($this->articoliSelezionati[$articoloId]);
    }

    public function isProdottoFinitoSelezionato($pfId): bool
    {
        return isset($this->prodottiFinitiSelezionati[$pfId]);
    }

    public function getTotaleSelezionati(): int
    {
        return count($this->articoliSelezionati) + count($this->prodottiFinitiSelezionati);
    }

    public function render()
    {
        
        return view('livewire.gestisci-conto-deposito', [
            'articoliDisponibili' => $this->articoliDisponibili,
            'prodottiFinitiDisponibili' => $this->prodottiFinitiDisponibili,
            'articoliInDeposito' => $this->articoliInDeposito,
            'prodottiFinitiInDeposito' => $this->prodottiFinitiInDeposito,
            // Variabili per vendita multipla
            'articoliSelezionatiVendita' => $this->articoliSelezionatiVendita,
            'prodottiFinitiSelezionatiVendita' => $this->prodottiFinitiSelezionatiVendita,
        ]);
    }
}