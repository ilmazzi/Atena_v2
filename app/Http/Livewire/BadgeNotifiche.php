<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Notifica;
use App\Models\Sede;
use Illuminate\Support\Facades\Auth;

/**
 * BadgeNotifiche - Badge contatore notifiche non lette
 * 
 * Componente piccolo per mostrare il contatore notifiche nella topbar
 */
class BadgeNotifiche extends Component
{
    public $conteggio = 0;

    // Auto-refresh ogni 30 secondi
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function mount()
    {
        $this->aggiornaConteggio();
    }

    public function aggiornaConteggio()
    {
        $user = Auth::user();
        
        if (!$user) {
            $this->conteggio = 0;
            return;
        }

        // Ottieni le società dell'utente dalle sedi_permesse
        $societaIds = [];
        $sediPermesseIds = $user->sedi_permesse ?? [];
        
        if (!empty($sediPermesseIds)) {
            // Carica le sedi per ottenere le società
            $sedi = \App\Models\Sede::whereIn('id', $sediPermesseIds)->get(['id', 'societa_id']);
            
            foreach ($sedi as $sede) {
                if ($sede->societa_id && !in_array($sede->societa_id, $societaIds)) {
                    $societaIds[] = $sede->societa_id;
                }
            }
        }
        
        if (empty($societaIds)) {
            // Se l'utente è admin (nessuna restrizione), mostra tutte le notifiche
            if ($user->isAdmin()) {
                $this->conteggio = Notifica::nonLette()->count();
            } else {
                $this->conteggio = 0;
            }
            return;
        }
        
        // Conta le notifiche non lette per le società dell'utente
        $this->conteggio = Notifica::nonLette()
            ->whereIn('societa_id', $societaIds)
            ->count();
    }

    public function render()
    {
        $this->aggiornaConteggio();
        
        return view('livewire.badge-notifiche');
    }
}
