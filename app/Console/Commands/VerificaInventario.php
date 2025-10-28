<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Articolo;
use App\Models\Giacenza;
use App\Models\InventarioSessione;
use App\Models\CategoriaMerceologica;

class VerificaInventario extends Command
{
    protected $signature = 'inventario:verifica {sessione_id?}';
    protected $description = 'Verifica i dati di inventario per una sessione specifica';

    public function handle()
    {
        $sessioneId = $this->argument('sessione_id');
        
        if ($sessioneId) {
            $this->verificaSessione($sessioneId);
        } else {
            $this->verificaGenerale();
        }
    }

    private function verificaSessione($sessioneId)
    {
        $sessione = InventarioSessione::with(['sede', 'utente'])->find($sessioneId);
        
        if (!$sessione) {
            $this->error("Sessione {$sessioneId} non trovata!");
            return;
        }

        $this->info("=== VERIFICA SESSIONE {$sessioneId} ===");
        $this->info("Nome: {$sessione->nome}");
        $this->info("Sede: {$sessione->sede->nome} (ID: {$sessione->sede_id})");
        $this->info("Utente: {$sessione->utente->name}");
        $this->info("Categorie: " . implode(', ', $sessione->categorie_permesse ?? []));
        $this->newLine();

        // Verifica articoli per sede
        $articoliSede = Articolo::whereHas('giacenze', function ($q) use ($sessione) {
            $q->where('sede_id', $sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        })->count();

        $this->info("Articoli per sede {$sessione->sede->nome}: {$articoliSede}");

        // Verifica per categorie
        if ($sessione->categorie_permesse && !empty($sessione->categorie_permesse)) {
            $this->info("--- Articoli per categoria ---");
            foreach ($sessione->categorie_permesse as $categoriaId) {
                $count = Articolo::whereHas('giacenze', function ($q) use ($sessione) {
                    $q->where('sede_id', $sessione->sede_id)
                      ->where('quantita_residua', '>', 0);
                })->where('categoria_merceologica_id', $categoriaId)->count();
                
                $categoria = CategoriaMerceologica::find($categoriaId);
                $nome = $categoria ? $categoria->nome : "ID:$categoriaId";
                $this->info("Categoria {$categoriaId} ({$nome}): {$count}");
            }
        } else {
            $this->info("Nessuna categoria specificata - include TUTTE le categorie");
        }

        // Totale con filtri
        $query = Articolo::whereHas('giacenze', function ($q) use ($sessione) {
            $q->where('sede_id', $sessione->sede_id)
              ->where('quantita_residua', '>', 0);
        });

        if ($sessione->categorie_permesse && !empty($sessione->categorie_permesse)) {
            $query->whereIn('categoria_merceologica_id', $sessione->categorie_permesse);
        }

        $totaleConFiltri = $query->count();
        $this->newLine();
        $this->info("TOTALE CON FILTRI: {$totaleConFiltri}");
    }

    private function verificaGenerale()
    {
        $this->info("=== VERIFICA GENERALE INVENTARIO ===");
        
        // Totale articoli
        $totaleArticoli = Articolo::count();
        $this->info("Totale articoli nel database: {$totaleArticoli}");

        // Totale giacenze
        $totaleGiacenze = Giacenza::where('quantita_residua', '>', 0)->count();
        $this->info("Totale giacenze con qta > 0: {$totaleGiacenze}");

        // Per sede
        $this->info("--- Articoli per sede ---");
        $sedi = \App\Models\Sede::all();
        foreach ($sedi as $sede) {
            $count = Articolo::whereHas('giacenze', function ($q) use ($sede) {
                $q->where('sede_id', $sede->id)
                  ->where('quantita_residua', '>', 0);
            })->count();
            $this->info("Sede {$sede->id} ({$sede->nome}): {$count}");
        }

        // Per categoria
        $this->info("--- Articoli per categoria ---");
        $categorie = CategoriaMerceologica::all();
        foreach ($categorie as $categoria) {
            $count = Articolo::whereHas('giacenze', function ($q) {
                $q->where('quantita_residua', '>', 0);
            })->where('categoria_merceologica_id', $categoria->id)->count();
            $this->info("Categoria {$categoria->id} ({$categoria->nome}): {$count}");
        }

        // Sessioni attive
        $sessioniAttive = InventarioSessione::where('stato', 'attiva')->count();
        $this->newLine();
        $this->info("Sessioni attive: {$sessioniAttive}");
    }
}