<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ContoDeposito;
use App\Models\Articolo;
use App\Models\ProdottoFinito;
use App\Services\ContoDepositoService;
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

    protected $rules = [
        'articoliSelezionati.*.quantita' => 'required|integer|min:1',
        'quantitaVendita' => 'required|integer|min:1',
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
        $this->validate();

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
        $this->validate();

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
            $ddt = $service->generaDdtInvio($this->deposito);
            
            // Aggiorna deposito
            $this->deposito->refresh();

            session()->flash('success', "DDT di invio {$ddt->numero} generato con successo");
            
            // Redirect alla stampa DDT
            return redirect()->route('ddt.stampa', $ddt->id);

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
            $ddt = $service->generaDdtReso($this->deposito);
            
            // Aggiorna deposito
            $this->deposito->refresh();

            session()->flash('success', "DDT di reso {$ddt->numero} generato per {$movimentiReso->count()} articoli");
            
            // Redirect alla stampa DDT
            return redirect()->route('ddt.stampa', $ddt->id);

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
        ]);
    }
}