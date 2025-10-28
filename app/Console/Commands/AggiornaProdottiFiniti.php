<?php

namespace App\Console\Commands;

use App\Models\ProdottoFinito;
use App\Models\Articolo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggiornaProdottiFiniti extends Command
{
    protected $signature = 'prodotti-finiti:aggiorna
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire}';

    protected $description = 'Aggiorna prodotti finiti esistenti: imposta stato componenti e corregge dati';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà salvata');
        }
        
        $prodottiFiniti = ProdottoFinito::with('componentiArticoli.articolo')->get();
        
        $this->info("📦 Trovati {$prodottiFiniti->count()} prodotti finiti");
        $this->newLine();
        
        foreach ($prodottiFiniti as $pf) {
            $this->line("─────────────────────────────────────────");
            $this->info("Prodotto Finito: {$pf->codice} - {$pf->descrizione}");
            $this->line("Stato attuale: {$pf->stato}");
            
            if (!$dryRun) {
                DB::beginTransaction();
            }
            
            try {
                // 1. Aggiorna stato prodotto finito (sempre 'completato')
                if ($pf->stato !== 'completato') {
                    $this->warn("  ⚠️ Stato '{$pf->stato}' → 'completato'");
                    if (!$dryRun) {
                        $pf->update([
                            'stato' => 'completato',
                            'data_completamento' => $pf->data_completamento ?? now(),
                            'assemblato_da' => $pf->assemblato_da ?? $pf->creato_da,
                        ]);
                    }
                } else {
                    $this->info("  ✅ Stato già 'completato'");
                }
                
                // 2. Aggiorna stato componenti
                $this->line("\n  Componenti ({$pf->componentiArticoli->count()}):");
                foreach ($pf->componentiArticoli as $componente) {
                    $articolo = $componente->articolo;
                    
                    if (!$articolo) {
                        $this->error("    ❌ Articolo ID {$componente->articolo_id} non trovato!");
                        continue;
                    }
                    
                    $statoAttuale = $articolo->stato_articolo ?? 'NULL';
                    $giacenzaResidua = $articolo->giacenza->quantita_residua ?? 'N/A';
                    
                    $this->line("    • {$articolo->codice}");
                    $this->line("      Giacenza residua: {$giacenzaResidua}");
                    $this->line("      Stato attuale: {$statoAttuale}");
                    
                    // Imposta stato 'in_prodotto_finito' solo se giacenza = 0
                    if ($giacenzaResidua == 0 && $statoAttuale !== 'in_prodotto_finito') {
                        $this->warn("      → Aggiorno stato a 'in_prodotto_finito'");
                        if (!$dryRun) {
                            $articolo->update(['stato_articolo' => 'in_prodotto_finito']);
                        }
                    } elseif ($giacenzaResidua == 0 && $statoAttuale === 'in_prodotto_finito') {
                        $this->info("      ✅ Stato già corretto");
                    } else {
                        $this->warn("      ⚠️ Giacenza non zero ({$giacenzaResidua}) - potrebbe essere un problema!");
                    }
                }
                
                if (!$dryRun) {
                    DB::commit();
                    $this->info("\n  ✅ Aggiornamento completato");
                } else {
                    $this->info("\n  🔍 Modifiche simulate (non salvate)");
                }
                
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                $this->error("\n  ❌ Errore: " . $e->getMessage());
            }
            
            $this->newLine();
        }
        
        $this->line("─────────────────────────────────────────");
        
        if ($dryRun) {
            $this->warn('🔍 DRY-RUN completato. Esegui senza --dry-run per applicare le modifiche.');
        } else {
            $this->info('✅ Aggiornamento completato!');
        }
        
        return 0;
    }
}
