<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Notifica;
use App\Services\NotificaService;
use App\Models\Societa;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardNotifiche - Dashboard centralizzata notifiche conti deposito
 */
class DashboardNotifiche extends Component
{
    use WithPagination;

    public $filtroTipo = '';
    public $filtroLettura = '';
    public $societaSelezionata = null;

    protected $queryString = [
        'filtroTipo' => ['except' => ''],
        'filtroLettura' => ['except' => ''],
    ];

    public function mount()
    {
        // Se c'è solo una società, selezionala automaticamente
        $societaList = Societa::where('attivo', true)->get();
        if ($societaList->count() === 1) {
            $this->societaSelezionata = $societaList->first()->id;
        }
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getNotificheProperty()
    {
        if (!$this->societaSelezionata) {
            return Notifica::whereRaw('1 = 0')->paginate(15); // Query vuota
        }

        $query = Notifica::perSocieta($this->societaSelezionata)
            ->with(['contoDeposito', 'movimentoDeposito']);

        if ($this->filtroTipo) {
            $query->perTipo($this->filtroTipo);
        }

        if ($this->filtroLettura === 'lette') {
            $query->where('letta', true);
        } elseif ($this->filtroLettura === 'non_lette') {
            $query->nonLette();
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getStatisticheProperty()
    {
        if (!$this->societaSelezionata) {
            return [
                'totali' => 0,
                'non_lette' => 0,
                'resi' => 0,
                'vendite' => 0,
                'scadenze' => 0,
            ];
        }

        $service = new NotificaService();
        
        return [
            'totali' => Notifica::perSocieta($this->societaSelezionata)->count(),
            'non_lette' => $service->contaNonLette($this->societaSelezionata),
            'resi' => Notifica::perSocieta($this->societaSelezionata)->perTipo('reso')->count(),
            'vendite' => Notifica::perSocieta($this->societaSelezionata)->perTipo('vendita')->count(),
            'scadenze' => Notifica::perSocieta($this->societaSelezionata)
                ->whereIn('tipo', ['scadenza', 'deposito_scaduto'])
                ->count(),
        ];
    }

    public function getSocietaListProperty()
    {
        return Societa::where('attivo', true)->orderBy('codice')->get();
    }

    // ==========================================
    // ACTIONS
    // ==========================================

    public function marcaComeLetta($notificaId)
    {
        $notifica = Notifica::findOrFail($notificaId);
        $notifica->marcaComeLetta();
        
        session()->flash('message', '✅ Notifica segnata come letta');
    }

    public function marcaTutteComeLette()
    {
        if (!$this->societaSelezionata) {
            return;
        }

        Notifica::perSocieta($this->societaSelezionata)
            ->nonLette()
            ->update([
                'letta' => true,
                'letta_il' => now(),
            ]);
        
        session()->flash('message', '✅ Tutte le notifiche segnate come lette');
    }

    public function eliminaNotifica($notificaId)
    {
        Notifica::findOrFail($notificaId)->delete();
        session()->flash('message', '✅ Notifica eliminata');
    }

    public function render()
    {
        return view('livewire.dashboard-notifiche', [
            'notifiche' => $this->notifiche,
            'statistiche' => $this->statistiche,
            'societaList' => $this->societaList,
        ]);
    }
}
