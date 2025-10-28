<?php

namespace App\Http\Livewire;

use App\Models\Articolo;
use App\Models\InventarioSessione;
use App\Models\InventarioScansione;
use App\Models\Sede;
use App\Models\CategoriaMerceologica;
use Livewire\Component;
use Livewire\WithPagination;

class InventarioMonitor extends Component
{
    use WithPagination;

    public $sessioneId = null;
    public $sessione = null;
    public $sedeId = '';
    public $categoriaId = '';
    public $statoArticolo = ''; // tutti, trovati, mancanti, non_scansionati
    public $sedi = [];
    public $categorie = [];
    public $statistiche = [];
    public $risultatiVerifica = [];
    public $risultatiConfronto = [];
    public $showModalVerifica = false;
    public $showModalConfronto = false;
    public $articoliDaInventariare = [];
    public $articoliTrovati = [];
    public $articoliMancanti = [];
    public $articoliNonScansionati = [];

    public function mount($sessione = null)
    {
        $this->sessioneId = $sessione;
        $this->sedi = Sede::all();
        $this->categorie = CategoriaMerceologica::all();
        
        if ($this->sessioneId) {
            $this->caricaSessione();
        }
    }

    public function caricaSessione()
    {
        if ($this->sessioneId) {
            $this->sessione = InventarioSessione::with(['sede', 'utente'])
                ->find($this->sessioneId);
            
            if ($this->sessione) {
                $this->sedeId = $this->sessione->sede_id;
                $this->calcolaStatistiche();
                $this->caricaArticoli();
            }
        }
    }

    public function calcolaStatistiche()
    {
        if (!$this->sessione) return;

        // Articoli totali da inventariare
        $query = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        });

        if ($this->sessione->categorie_permesse && !empty($this->sessione->categorie_permesse)) {
            $query->whereIn('categoria_merceologica_id', $this->sessione->categorie_permesse);
        }

        $articoliTotali = $query->count();

        // Articoli scansionati (distinct per articolo)
        $articoliScansionati = InventarioScansione::where('sessione_id', $this->sessioneId)
            ->distinct('articolo_id')
            ->count('articolo_id');

        // Articoli trovati (distinct per articolo)
        $articoliTrovati = InventarioScansione::where('sessione_id', $this->sessioneId)
            ->where('azione', 'trovato')
            ->distinct('articolo_id')
            ->count('articolo_id');

        // Articoli eliminati (distinct per articolo)
        $articoliEliminati = InventarioScansione::where('sessione_id', $this->sessioneId)
            ->where('azione', 'eliminato')
            ->distinct('articolo_id')
            ->count('articolo_id');

        // Articoli non scansionati
        $articoliNonScansionati = $articoliTotali - $articoliScansionati;

        // Progresso
        $progresso = $articoliTotali > 0 ? round(($articoliScansionati / $articoliTotali) * 100, 2) : 0;

        // Debug: Log dei calcoli per verificare
        \Log::info('Statistiche Inventario', [
            'sessione_id' => $this->sessioneId,
            'sede_id' => $this->sessione->sede_id,
            'categorie_permesse' => $this->sessione->categorie_permesse,
            'articoli_totali' => $articoliTotali,
            'articoli_scansionati' => $articoliScansionati,
            'articoli_trovati' => $articoliTrovati,
            'articoli_eliminati' => $articoliEliminati,
            'articoli_non_scansionati' => $articoliNonScansionati,
            'progresso' => $progresso
        ]);

        $this->statistiche = [
            'articoli_totali' => $articoliTotali,
            'articoli_scansionati' => $articoliScansionati,
            'articoli_trovati' => $articoliTrovati,
            'articoli_eliminati' => $articoliEliminati,
            'articoli_non_scansionati' => $articoliNonScansionati,
            'progresso' => $progresso,
            'completato' => $articoliNonScansionati == 0
        ];
    }

    public function caricaArticoli()
    {
        if (!$this->sessione) return;

        // Questo metodo ora serve solo per aggiornare le statistiche
        // La paginazione è gestita da getArticoliFiltrati()
        $this->calcolaStatistiche();
    }

    public function filtraArticoli()
    {
        $this->caricaArticoli();
    }

    public function verificaDati()
    {
        if (!$this->sessione) return;

        // Verifica articoli totali per sede (senza filtri categoria)
        $articoliPerSede = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        })->count();

        // Verifica articoli per categoria
        $articoliPerCategoria = [];
        if ($this->sessione->categorie_permesse) {
            foreach ($this->sessione->categorie_permesse as $categoriaId) {
                $count = Articolo::whereHas('giacenze', function ($q) {
                    $q->where('sede_id', $this->sessione->sede_id)
                      ->where('quantita_residua', '>', 0);
                })->where('categoria_merceologica_id', $categoriaId)->count();
                
                $categoria = \App\Models\CategoriaMerceologica::find($categoriaId);
                $articoliPerCategoria[$categoria->nome ?? "ID:$categoriaId"] = $count;
            }
        }

        // Verifica totale con filtri categoria
        $articoliConFiltri = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        });
        
        if ($this->sessione->categorie_permesse) {
            $articoliConFiltri->whereIn('categoria_merceologica_id', $this->sessione->categorie_permesse);
        }
        $totaleConFiltri = $articoliConFiltri->count();

        // Verifica scansioni
        $scansioniTotali = InventarioScansione::where('sessione_id', $this->sessioneId)->count();
        $scansioniDistinct = InventarioScansione::where('sessione_id', $this->sessioneId)
            ->distinct('articolo_id')
            ->count('articolo_id');

        // Verifica totale articoli in database (tutte le sedi)
        $totaleArticoliDB = Articolo::count();
        $totaleGiacenze = \App\Models\Giacenza::where('quantita_residua', '>', 0)->count();

        \Log::info('Verifica Dati Inventario DETTAGLIATA', [
            'sessione_id' => $this->sessioneId,
            'sede_id' => $this->sessione->sede_id,
            'sede_nome' => $this->sessione->sede->nome,
            'categorie_permesse' => $this->sessione->categorie_permesse,
            'articoli_per_sede' => $articoliPerSede,
            'articoli_per_categoria' => $articoliPerCategoria,
            'totale_con_filtri' => $totaleConFiltri,
            'totale_articoli_db' => $totaleArticoliDB,
            'totale_giacenze' => $totaleGiacenze,
            'scansioni_totali' => $scansioniTotali,
            'scansioni_distinct' => $scansioniDistinct
        ]);

        // Memorizza i risultati per mostrare nel modal
        $this->risultatiVerifica = [
            'sessione_id' => $this->sessioneId,
            'sede_id' => $this->sessione->sede_id,
            'sede_nome' => $this->sessione->sede->nome,
            'categorie_permesse' => $this->sessione->categorie_permesse,
            'articoli_per_sede' => $articoliPerSede,
            'articoli_per_categoria' => $articoliPerCategoria,
            'totale_con_filtri' => $totaleConFiltri,
            'totale_articoli_db' => $totaleArticoliDB,
            'totale_giacenze' => $totaleGiacenze,
            'scansioni_totali' => $scansioniTotali,
            'scansioni_distinct' => $scansioniDistinct
        ];
        
        $this->showModalVerifica = true;
        session()->flash('info', "✅ Dati verificati! Visualizza i risultati qui sotto.");
    }

    public function confrontaConArticoli()
    {
        if (!$this->sessione) return;

        // Conteggio dalla pagina articoli (tutti gli articoli con giacenze > 0)
        $articoliPagina = Articolo::whereHas('giacenze', function ($q) {
            $q->where('quantita_residua', '>', 0);
        })->count();

        // Conteggio per sede specifica
        $articoliSede = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        })->count();

        // Conteggio per categorie 1-9
        $articoliCategorie19 = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        })->whereIn('categoria_merceologica_id', [1,2,3,4,5,6,7,8,9])->count();

        // Conteggio per tutte le categorie
        $articoliTutteCategorie = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        })->count();

        \Log::info('Confronto con Pagina Articoli', [
            'sessione_id' => $this->sessioneId,
            'sede_id' => $this->sessione->sede_id,
            'sede_nome' => $this->sessione->sede->nome,
            'articoli_pagina_totale' => $articoliPagina,
            'articoli_sede' => $articoliSede,
            'articoli_categorie_1_9' => $articoliCategorie19,
            'articoli_tutte_categorie' => $articoliTutteCategorie,
            'categorie_permesse_sessione' => $this->sessione->categorie_permesse
        ]);

        // Memorizza i risultati per mostrare nel modal
        $this->risultatiConfronto = [
            'sessione_id' => $this->sessioneId,
            'sede_id' => $this->sessione->sede_id,
            'sede_nome' => $this->sessione->sede->nome,
            'articoli_pagina_totale' => $articoliPagina,
            'articoli_sede' => $articoliSede,
            'articoli_categorie_1_9' => $articoliCategorie19,
            'articoli_tutte_categorie' => $articoliTutteCategorie,
            'categorie_permesse_sessione' => $this->sessione->categorie_permesse
        ];
        
        $this->showModalConfronto = true;
        session()->flash('info', "✅ Confronto completato! Visualizza i risultati qui sotto.");
    }

    public function resetFiltri()
    {
        $this->categoriaId = '';
        $this->statoArticolo = '';
        $this->caricaArticoli();
    }

    public function chiudiModalVerifica()
    {
        $this->showModalVerifica = false;
        $this->risultatiVerifica = [];
    }

    public function chiudiModalConfronto()
    {
        $this->showModalConfronto = false;
        $this->risultatiConfronto = [];
    }

    public function getScansioniEffettuate()
    {
        if (!$this->sessione) {
            return collect();
        }

        return InventarioScansione::where('sessione_id', $this->sessioneId)
            ->with(['articolo.categoriaMerceologica'])
            ->orderBy('data_scansione', 'desc')
            ->get();
    }

    public function getArticoliFiltrati()
    {
        if (!$this->sessione) {
            return \App\Models\Articolo::where('id', 0)->paginate(20);
        }

        // Query base per articoli da inventariare
        $query = Articolo::whereHas('giacenze', function ($q) {
            $q->where('sede_id', $this->sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        });

        if ($this->sessione->categorie_permesse && !empty($this->sessione->categorie_permesse)) {
            $query->whereIn('categoria_merceologica_id', $this->sessione->categorie_permesse);
        }

        if ($this->categoriaId) {
            $query->where('categoria_merceologica_id', $this->categoriaId);
        }

        // Applica filtri per stato
        switch ($this->statoArticolo) {
            case 'trovati':
                $articoliTrovati = InventarioScansione::where('sessione_id', $this->sessioneId)
                    ->where('azione', 'trovato')
                    ->pluck('articolo_id')
                    ->toArray();
                if (!empty($articoliTrovati)) {
                    $query->whereIn('id', $articoliTrovati);
                } else {
                    $query->where('id', 0); // Nessun risultato
                }
                break;
            case 'mancanti':
                $articoliEliminati = InventarioScansione::where('sessione_id', $this->sessioneId)
                    ->where('azione', 'eliminato')
                    ->pluck('articolo_id')
                    ->toArray();
                if (!empty($articoliEliminati)) {
                    $query->whereIn('id', $articoliEliminati);
                } else {
                    $query->where('id', 0); // Nessun risultato
                }
                break;
            case 'non_scansionati':
                $articoliScansionati = InventarioScansione::where('sessione_id', $this->sessioneId)
                    ->pluck('articolo_id')
                    ->toArray();
                if (!empty($articoliScansionati)) {
                    $query->whereNotIn('id', $articoliScansionati);
                }
                // Se non ci sono scansioni, mostra tutti gli articoli
                break;
        }

        return $query->with(['categoriaMerceologica', 'giacenze'])
            ->orderBy('codice')
            ->paginate(20);
    }

    public function render()
    {
        $articoli = $this->getArticoliFiltrati();
        
        return view('livewire.inventario-monitor', [
            'articoli' => $articoli
        ])->layout('layouts.vertical');
    }
}
