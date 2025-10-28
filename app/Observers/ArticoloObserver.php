<?php

namespace App\Observers;

use App\Models\Articolo;
use App\Http\Controllers\MagazzinoViewController;
use Illuminate\Support\Facades\Cache;

class ArticoloObserver
{
    /**
     * Handle the Articolo "created" event.
     */
    public function created(Articolo $articolo): void
    {
        // Pulisci il cache del magazzino quando viene creato un nuovo articolo
        Cache::forget("magazzino_articoli_{$articolo->magazzino_id}");
    }

    /**
     * Handle the Articolo "updated" event.
     */
    public function updated(Articolo $articolo): void
    {
        // Pulisci il cache del magazzino quando viene aggiornato un articolo
        Cache::forget("magazzino_articoli_{$articolo->magazzino_id}");
        
        // Se il magazzino è cambiato, pulisci anche il cache del vecchio magazzino
        if ($articolo->wasChanged('magazzino_id')) {
            $oldMagazzinoId = $articolo->getOriginal('magazzino_id');
            if ($oldMagazzinoId) {
                Cache::forget("magazzino_articoli_{$oldMagazzinoId}");
            }
        }
    }

    /**
     * Handle the Articolo "deleted" event.
     */
    public function deleted(Articolo $articolo): void
    {
        // Pulisci il cache del magazzino quando viene eliminato un articolo
        Cache::forget("magazzino_articoli_{$articolo->magazzino_id}");
    }

    /**
     * Handle the Articolo "restored" event.
     */
    public function restored(Articolo $articolo): void
    {
        // Pulisci il cache del magazzino quando viene ripristinato un articolo
        Cache::forget("magazzino_articoli_{$articolo->magazzino_id}");
    }

    /**
     * Handle the Articolo "force deleted" event.
     */
    public function forceDeleted(Articolo $articolo): void
    {
        // Pulisci il cache del magazzino quando viene eliminato definitivamente un articolo
        Cache::forget("magazzino_articoli_{$articolo->magazzino_id}");
    }
}
