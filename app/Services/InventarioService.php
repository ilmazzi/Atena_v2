<?php

namespace App\Services;

use App\Models\Articolo;
use App\Models\ArticoloStorico;
use App\Models\InventarioSessione;
use App\Models\InventarioScansione;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventarioService
{
    /**
     * Crea una nuova sessione di inventario
     */
    public function creaSessione(string $nome, int $sedeId, array $categoriePermesse = null, int $utenteId = null): InventarioSessione
    {
        $utenteId = $utenteId ?? auth()->id();
        
        $sessione = InventarioSessione::create([
            'nome' => $nome,
            'sede_id' => $sedeId,
            'categorie_permesse' => $categoriePermesse,
            'data_inizio' => now(),
            'stato' => 'attiva',
            'utente_id' => $utenteId,
            'articoli_totali' => $this->contaArticoliDaInventariare($sedeId, $categoriePermesse)
        ]);
        
        Log::info("Sessione inventario creata", [
            'sessione_id' => $sessione->id,
            'nome' => $nome,
            'sede_id' => $sedeId,
            'utente_id' => $utenteId
        ]);
        
        return $sessione;
    }

    /**
     * Conta gli articoli da inventariare per sede e categorie
     */
    private function contaArticoliDaInventariare(int $sedeId, array $categoriePermesse = null): int
    {
        $query = Articolo::whereHas('giacenze', function ($q) use ($sedeId) {
            $q->where('sede_id', $sedeId)
              ->where('quantita_residua', '>', 0);
        });
        
        if ($categoriePermesse) {
            $query->whereIn('categoria_merceologica_id', $categoriePermesse);
        }
        
        return $query->count();
    }

    /**
     * Registra una scansione di articolo
     */
    public function registraScansione(int $sessioneId, int $articoloId, string $azione, int $quantitaTrovata = null): InventarioScansione
    {
        $sessione = InventarioSessione::findOrFail($sessioneId);
        $articolo = Articolo::findOrFail($articoloId);
        
        // Verifica se l'articolo è già stato scansionato
        $scansioneEsistente = InventarioScansione::where('sessione_id', $sessioneId)
            ->where('articolo_id', $articoloId)
            ->first();
            
        if ($scansioneEsistente) {
            throw new \Exception("Articolo già scansionato in questa sessione");
        }
        
        // Ottieni quantità dal sistema
        $quantitaSistema = $articolo->giacenze()
            ->where('sede_id', $sessione->sede_id)
            ->sum('quantita_residua');
        
        $scansione = InventarioScansione::create([
            'sessione_id' => $sessioneId,
            'articolo_id' => $articoloId,
            'azione' => $azione,
            'quantita_trovata' => $quantitaTrovata,
            'quantita_sistema' => $quantitaSistema,
            'data_scansione' => now()
        ]);
        
        Log::info("Scansione registrata", [
            'sessione_id' => $sessioneId,
            'articolo_id' => $articoloId,
            'azione' => $azione,
            'quantita_trovata' => $quantitaTrovata,
            'quantita_sistema' => $quantitaSistema
        ]);
        
        return $scansione;
    }

    /**
     * Elimina articoli non trovati durante l'inventario
     */
    public function eliminaArticoliNonTrovati(int $sessioneId): array
    {
        $sessione = InventarioSessione::findOrFail($sessioneId);
        
        // Ottieni articoli trovati
        $articoliTrovati = InventarioScansione::where('sessione_id', $sessioneId)
            ->where('azione', 'trovato')
            ->pluck('articolo_id')
            ->toArray();
        
        // Ottieni tutti gli articoli da inventariare
        $query = Articolo::whereHas('giacenze', function ($q) use ($sessione) {
            $q->where('sede_id', $sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        });
        
        if ($sessione->categorie_permesse) {
            $query->whereIn('categoria_merceologica_id', $sessione->categorie_permesse);
        }
        
        $articoliDaEliminare = $query->whereNotIn('id', $articoliTrovati)->get();
        
        $eliminati = [];
        
        DB::transaction(function () use ($articoliDaEliminare, $sessione, &$eliminati) {
            foreach ($articoliDaEliminare as $articolo) {
                $eliminato = $this->spostaInStorico($articolo, $sessione);
                $eliminati[] = $eliminato;
            }
        });
        
        // Aggiorna statistiche sessione
        $sessione->calcolaStatistiche();
        
        Log::info("Articoli eliminati durante inventario", [
            'sessione_id' => $sessioneId,
            'articoli_eliminati' => count($eliminati)
        ]);
        
        return $eliminati;
    }

    /**
     * Sposta un articolo nello storico
     */
    private function spostaInStorico(Articolo $articolo, InventarioSessione $sessione): ArticoloStorico
    {
        // 1. Salva dati completi dell'articolo
        $datiCompleti = $articolo->toArray();
        
        // 2. Salva relazioni storiche
        $relazioniStorico = [
            'giacenze' => $articolo->giacenze ? $articolo->giacenze->map(function ($giacenza) {
                return $giacenza->toArray();
            })->toArray() : [],
            'ddt_dettagli' => $articolo->ddtDettaglio ? $articolo->ddtDettaglio->map(function ($ddt) {
                return $ddt->toArray();
            })->toArray() : [],
            'fatture_dettagli' => $articolo->fatturaDettaglio ? $articolo->fatturaDettaglio->map(function ($fattura) {
                return $fattura->toArray();
            })->toArray() : []
        ];
        
        // 3. Crea record storico
        $articoloStorico = ArticoloStorico::create([
            'articolo_id_originale' => $articolo->id,
            'codice' => $articolo->codice,
            'descrizione' => $articolo->descrizione,
            'dati_completi' => $datiCompleti,
            'relazioni_storico' => $relazioniStorico,
            'motivo_eliminazione' => 'inventario',
            'sessione_inventario_id' => $sessione->id,
            'utente_id' => auth()->id(),
            'data_eliminazione' => now()
        ]);
        
        // 4. Elimina articolo (HARD DELETE)
        $articolo->delete();
        
        return $articoloStorico;
    }

    /**
     * Chiude una sessione di inventario
     */
    public function chiudiSessione(int $sessioneId): InventarioSessione
    {
        $sessione = InventarioSessione::findOrFail($sessioneId);
        
        if (!$sessione->isAttiva()) {
            throw new \Exception("Sessione non attiva");
        }
        
        // Elimina articoli non trovati
        $this->eliminaArticoliNonTrovati($sessioneId);
        
        // Chiudi sessione
        $sessione->chiudi();
        
        Log::info("Sessione inventario chiusa", [
            'sessione_id' => $sessioneId,
            'articoli_trovati' => $sessione->articoli_trovati,
            'articoli_eliminati' => $sessione->articoli_eliminati,
            'valore_eliminato' => $sessione->valore_eliminato
        ]);
        
        return $sessione;
    }

    /**
     * Annulla una sessione di inventario
     */
    public function annullaSessione(int $sessioneId): InventarioSessione
    {
        $sessione = InventarioSessione::findOrFail($sessioneId);
        
        if (!$sessione->isAttiva()) {
            throw new \Exception("Sessione non attiva");
        }
        
        // Elimina scansioni
        $sessione->scansioni()->delete();
        
        // Annulla sessione
        $sessione->annulla();
        
        Log::info("Sessione inventario annullata", [
            'sessione_id' => $sessioneId
        ]);
        
        return $sessione;
    }

    /**
     * Ottieni statistiche di una sessione
     */
    public function getStatisticheSessione(int $sessioneId): array
    {
        $sessione = InventarioSessione::findOrFail($sessioneId);
        
        return [
            'sessione' => $sessione,
            'articoli_totali' => $sessione->articoli_totali,
            'articoli_trovati' => $sessione->articoli_trovati,
            'articoli_eliminati' => $sessione->articoli_eliminati,
            'valore_eliminato' => $sessione->valore_eliminato,
            'progresso' => $sessione->progresso,
            'scansioni_con_differenza' => $sessione->scansioni()->conDifferenza()->count(),
            'scansioni_con_eccesso' => $sessione->scansioni()->conEccesso()->count(),
            'scansioni_con_mancanza' => $sessione->scansioni()->conMancanza()->count()
        ];
    }

    /**
     * Ripristina un articolo dallo storico
     */
    public function ripristinaArticolo(int $articoloStoricoId): Articolo
    {
        try {
            $articoloStorico = ArticoloStorico::findOrFail($articoloStoricoId);
            
            if (!$articoloStorico->dati_completi) {
                throw new \Exception("Dati completi mancanti per l'articolo storico ID: {$articoloStoricoId}");
            }
            
            $articolo = $articoloStorico->ripristina();
            
            Log::info("Articolo ripristinato dallo storico", [
                'articolo_storico_id' => $articoloStoricoId,
                'nuovo_articolo_id' => $articolo->id,
                'codice' => $articolo->codice
            ]);
            
            return $articolo;
        } catch (\Exception $e) {
            Log::error("Errore ripristino articolo storico {$articoloStoricoId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ottieni report di inventario
     */
    public function getReportInventario(int $sessioneId): array
    {
        $sessione = InventarioSessione::with(['sede', 'utente'])->findOrFail($sessioneId);
        
        $scansioni = $sessione->scansioni()
            ->with('articolo')
            ->orderBy('data_scansione')
            ->get();
        
        $articoliEliminati = $sessione->articoliEliminati()
            ->orderBy('data_eliminazione')
            ->get();
        
        return [
            'sessione' => $sessione,
            'scansioni' => $scansioni,
            'articoli_eliminati' => $articoliEliminati,
            'statistiche' => $this->getStatisticheSessione($sessioneId)
        ];
    }
}
