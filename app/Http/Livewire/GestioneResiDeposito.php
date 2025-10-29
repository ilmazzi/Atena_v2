<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ContoDeposito;
use App\Models\MovimentoDeposito;
use App\Services\ContoDepositoService;
use App\Services\NotificaService;
use Illuminate\Support\Facades\Auth;

/**
 * GestioneResiDeposito - Dashboard gestione resi conti deposito
 * 
 * Dashboard centralizzata per gestire tutti i resi:
 * - Resi da processare
 * - Resi completati
 * - Resi in attesa di DDT
 */
class GestioneResiDeposito extends Component
{
    use WithPagination;

    public $filtroStato = '';
    public $filtroDeposito = '';
    public $search = '';

    protected $queryString = [
        'filtroStato' => ['except' => ''],
        'filtroDeposito' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getResiProperty()
    {
        $query = MovimentoDeposito::with([
                'contoDeposito.sedeMittente',
                'contoDeposito.sedeDestinataria',
                'articolo',
                'prodottoFinito',
                'ddt',
            ])
            ->where('tipo_movimento', 'reso');

        // Filtro "In attesa DDT" - movimenti di depositi senza DDT reso generato
        if ($this->filtroStato === 'da_ddt') {
            $query->whereHas('contoDeposito', function($q) {
                $q->whereNull('ddt_reso_id');
            })
            ->whereNull('ddt_id'); // E anche senza ddt_id diretto (per sicurezza)
        }

        if ($this->filtroDeposito) {
            $query->where('conto_deposito_id', $this->filtroDeposito);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('articolo', function($subQ) {
                    $subQ->where('codice', 'like', "%{$this->search}%")
                         ->orWhere('descrizione', 'like', "%{$this->search}%");
                })
                ->orWhereHas('prodottoFinito', function($subQ) {
                    $subQ->where('codice', 'like', "%{$this->search}%")
                         ->orWhere('descrizione', 'like', "%{$this->search}%");
                })
                ->orWhereHas('contoDeposito', function($subQ) {
                    $subQ->where('codice', 'like', "%{$this->search}%");
                });
            });
        }

        return $query->orderBy('data_movimento', 'desc')->paginate(20);
    }

    public function getDepositiProperty()
    {
        return ContoDeposito::with(['sedeMittente', 'sedeDestinataria'])
            ->where('stato', '!=', 'chiuso')
            ->whereHas('movimenti', function($q) {
                $q->where('tipo_movimento', 'reso');
            })
            ->orderBy('codice')
            ->get();
    }

    public function getStatisticheProperty()
    {
        return [
            'resi_today' => MovimentoDeposito::where('tipo_movimento', 'reso')
                ->whereDate('data_movimento', today())
                ->count(),
            'resi_da_ddt' => MovimentoDeposito::where('tipo_movimento', 'reso')
                ->whereHas('contoDeposito', function($q) {
                    $q->whereNull('ddt_reso_id');
                })
                ->count(),
            'valore_totale_resi' => MovimentoDeposito::where('tipo_movimento', 'reso')
                ->whereDate('data_movimento', '>=', now()->subMonth())
                ->sum('costo_totale'),
        ];
    }

    // ==========================================
    // ACTIONS
    // ==========================================

    public function generaDdtReso($depositoId)
    {
        $deposito = ContoDeposito::findOrFail($depositoId);
        
        try {
            $service = new ContoDepositoService();
            $ddtDeposito = $service->generaDdtReso($deposito);
            
            session()->flash('message', "✅ DDT Reso generato: {$ddtDeposito->numero}");
        } catch (\Exception $e) {
            session()->flash('error', "❌ Errore: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestione-resi-deposito', [
            'resi' => $this->resi,
            'depositi' => $this->depositi,
            'statistiche' => $this->statistiche,
        ]);
    }
}
