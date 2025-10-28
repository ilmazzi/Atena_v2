<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProdottoFinito;
use App\Models\ComponenteProdotto;
use App\Models\Articolo;
use App\Models\Giacenza;

class MigraProdottiFinitiV2 extends Command
{
    protected $signature = 'pf:migra-v2 {--dry-run : Modalità dry-run} {--clean : Pulisci tabelle prima}';
    protected $description = 'Migrazione PF corretta con gestione duplicati componenti';

    private $dryRun = false;
    private $stats = [
        'pf_migrati' => 0,
        'componenti_migrati' => 0,
        'duplicati_gestiti' => 0,
        'errori' => 0
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $clean = $this->option('clean');
        
        $this->info('🚀 MIGRAZIONE PRODOTTI FINITI V2');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà salvata');
        }
        
        try {
            // Test connessione MSSQL
            DB::connection('mssql_prod')->getPdo();
            $this->info('✅ Connessione MSSQL produzione: OK');
        } catch (\Exception $e) {
            $this->error('❌ Errore connessione MSSQL: ' . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        
        // FASE 1: Pulizia (se richiesta)
        if ($clean) {
            $this->pulisciTabelle();
        }
        
        // FASE 2: Migra PF (categorie 9,22)
        $this->migraProdottiFiniti();
        
        // FASE 3: Migra componenti
        $this->migraComponenti();
        
        // FASE 4: Verifica integrità
        $this->verificaIntegrita();
        
        $this->newLine();
        $this->displaySummary();
        
        return 0;
    }
    
    private function pulisciTabelle()
    {
        $this->info('🧹 PULIZIA TABELLE ESISTENTI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Pulizia saltata (dry-run)');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Pulisci componenti
            $componentiEliminati = DB::table('componenti_prodotto')->delete();
            $this->info("✅ Eliminati {$componentiEliminati} componenti");
            
            // Pulisci PF
            $pfEliminati = DB::table('prodotti_finiti')->delete();
            $this->info("✅ Eliminati {$pfEliminati} prodotti finiti");
            
            // Ripristina stati articoli
            $articoliAggiornati = DB::table('articoli')
                ->whereIn('categoria_merceologica_id', [9, 22])
                ->update([
                    'stato_articolo' => 'disponibile',
                    'scarico_id' => null,
                    'prodotto_finito_id' => null,
                    'assemblato_il' => null,
                    'assemblato_da' => null
                ]);
            $this->info("✅ Aggiornati {$articoliAggiornati} articoli PF");
            
            // Ripristina giacenze componenti
            $giacenzeAggiornate = DB::table('giacenze')
                ->whereIn('articolo_id', function($query) {
                    $query->select('id')
                        ->from('articoli')
                        ->whereIn('categoria_merceologica_id', [5, 6, 7, 8]); // Categorie componenti
                })
                ->update([
                    'quantita_residua' => DB::raw('quantita')
                ]);
            $this->info("✅ Ripristinate {$giacenzeAggiornate} giacenze componenti");
            
            DB::commit();
            $this->info('✅ Pulizia completata!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Errore durante pulizia: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function migraProdottiFiniti()
    {
        $this->info('📦 MIGRAZIONE PRODOTTI FINITI (Categorie 9,22)');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Recupera articoli PF dalla vista
        $articoliPF = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->whereIn('id_magazzino', [9, 22])
            ->whereNotNull('id_pf')
            ->orderBy('id_magazzino')
            ->orderBy('carico')
            ->get();
            
        $this->info("Articoli PF trovati: {$articoliPF->count()}");
        
        if ($articoliPF->isEmpty()) {
            $this->warn('⚠️ Nessun articolo PF trovato');
            return;
        }
        
        $progressBar = $this->output->createProgressBar($articoliPF->count());
        $progressBar->start();
        
        foreach ($articoliPF as $artMSSQL) {
            try {
                if (!$this->dryRun) {
                    DB::beginTransaction();
                }
                
                $this->creaProdottoFinito($artMSSQL);
                
                if (!$this->dryRun) {
                    DB::commit();
                }
                
                $this->stats['pf_migrati']++;
                
            } catch (\Exception $e) {
                if (!$this->dryRun) {
                    DB::rollBack();
                }
                
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore PF {$artMSSQL->id_magazzino}-{$artMSSQL->carico}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function creaProdottoFinito($artMSSQL)
    {
        if ($this->dryRun) {
            return;
        }
        
        // Crea prodotto finito
        $prodottoFinito = ProdottoFinito::create([
            'codice' => $artMSSQL->id_magazzino . '-' . $artMSSQL->carico,
            'descrizione' => $artMSSQL->descrizione,
            'tipologia' => 'prodotto_finito',
            'magazzino_id' => $artMSSQL->id_magazzino,
            'stato' => 'completato',
            'data_inizio_lavorazione' => $artMSSQL->data_documento ?? now(),
            'data_completamento' => $artMSSQL->data_documento ?? now(),
            'costo_materiali' => $artMSSQL->valore_magazzino ?? 0,
            'costo_lavorazione' => 0,
            'costo_totale' => $artMSSQL->valore_magazzino ?? 0,
            'oro_totale' => $artMSSQL->oro ?? '',
            'brillanti_totali' => $artMSSQL->brill ?? '',
            'pietre_totali' => $artMSSQL->pietre ?? '',
            'note' => 'Importato da sistema storico',
            'creato_da' => 1,
            'assemblato_da' => 1,
        ]);
        
        // Collega articolo risultante
        $articoloRisultante = Articolo::where('codice', $artMSSQL->id_magazzino . '-' . $artMSSQL->carico)->first();
        if ($articoloRisultante) {
            $articoloRisultante->update([
                'prodotto_finito_id' => $prodottoFinito->id,
                'assemblato_il' => now(),
                'assemblato_da' => 1,
            ]);
        }
    }
    
    private function migraComponenti()
    {
        $this->info('🔧 MIGRAZIONE COMPONENTI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Recupera tutti i PF creati
        $prodottiFiniti = ProdottoFinito::all();
        $this->info("PF da elaborare: {$prodottiFiniti->count()}");
        
        $progressBar = $this->output->createProgressBar($prodottiFiniti->count());
        $progressBar->start();
        
        foreach ($prodottiFiniti as $pf) {
            try {
                if (!$this->dryRun) {
                    DB::beginTransaction();
                }
                
                $this->migraComponentiPF($pf);
                
                if (!$this->dryRun) {
                    DB::commit();
                }
                
            } catch (\Exception $e) {
                if (!$this->dryRun) {
                    DB::rollBack();
                }
                
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore componenti PF {$pf->codice}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function migraComponentiPF($pf)
    {
        if ($this->dryRun) {
            return;
        }
        
        // Estrai ID PF dal codice (es: 9-1 → ID 1)
        preg_match('/(\d+)-(\d+)/', $pf->codice, $matches);
        if (!isset($matches[2])) {
            throw new \Exception("Codice PF non valido: {$pf->codice}");
        }
        
        $idPF = (int)$matches[2];
        
        // Recupera componenti da mag_diba
        $componenti = DB::connection('mssql_prod')
            ->table('mag_diba')
            ->where('id_pf', $idPF)
            ->get();
            
        if ($componenti->isEmpty()) {
            return;
        }
        
        foreach ($componenti as $compMSSQL) {
            if (empty($compMSSQL->carico)) {
                continue;
            }
            
            // Gestisci duplicati componenti
            $articoliComponente = $this->gestisciDuplicatiComponenti($compMSSQL->carico);
            
            foreach ($articoliComponente as $artComponente) {
                $this->creaComponente($pf, $artComponente);
            }
        }
    }
    
    private function gestisciDuplicatiComponenti($carico)
    {
        // Converti formato (5/1006 → 5-1006)
        $caricoFormattato = str_replace('/', '-', $carico);
        
        // Trova articoli con questo carico
        $articoli = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->where('id_magazzino', 5) // Categoria componenti
            ->where('carico', str_replace('5-', '', $caricoFormattato))
            ->get();
            
        if ($articoli->count() == 1) {
            // Nessun duplicato
            $art = $articoli->first();
            $art->codice_unico = $caricoFormattato;
            return [$art];
        }
        
        // Gestisci duplicati con suffissi
        $this->stats['duplicati_gestiti'] += $articoli->count();
        
        return $articoli->map(function($art, $index) use ($caricoFormattato) {
            $art->codice_unico = $caricoFormattato . '-' . ($index + 1);
            $art->codice_originale = $caricoFormattato;
            return $art;
        });
    }
    
    private function creaComponente($pf, $artComponente)
    {
        if ($this->dryRun) {
            return;
        }
        
        // Trova articolo in MySQL
        $articoloMySQL = Articolo::where('codice', $artComponente->codice_unico)->first();
        if (!$articoloMySQL) {
            // Crea articolo se non esiste
            $articoloMySQL = $this->creaArticoloComponente($artComponente);
        }
        
        // Crea componente
        ComponenteProdotto::create([
            'prodotto_finito_id' => $pf->id,
            'articolo_id' => $articoloMySQL->id,
            'quantita' => 1,
            'costo_unitario' => $artComponente->costo_unitario ?? 0,
            'costo_totale' => ($artComponente->costo_unitario ?? 0) * 1,
            'stato' => 'prelevato',
            'prelevato_il' => now(),
            'prelevato_da' => 1,
        ]);
        
        // Aggiorna stato articolo
        $articoloMySQL->update(['stato_articolo' => 'in_prodotto_finito']);
        
        // Aggiorna giacenza
        $giacenza = $articoloMySQL->giacenza;
        if ($giacenza) {
            $giacenza->update(['quantita_residua' => 0]);
        }
        
        $this->stats['componenti_migrati']++;
    }
    
    private function creaArticoloComponente($artMSSQL)
    {
        // Crea articolo componente
        $articolo = Articolo::create([
            'codice' => $artMSSQL->codice_unico,
            'descrizione' => $artMSSQL->descrizione,
            'categoria_merceologica_id' => 5, // Categoria componenti
            'sede_id' => 1, // Default
            'materiale' => $artMSSQL->materiale ?? null,
            'caratura' => $artMSSQL->carati ?? null,
            'prezzo_acquisto' => $artMSSQL->costo_unitario ?? 0,
            'stato' => 'disponibile',
            'note' => "Componente duplicato - Originale: {$artMSSQL->codice_originale}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Crea giacenza
        Giacenza::create([
            'articolo_id' => $articolo->id,
            'categoria_merceologica_id' => 5,
            'sede_id' => 1,
            'quantita' => 1,
            'quantita_residua' => 1,
            'costo_unitario' => $artMSSQL->costo_unitario ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $articolo;
    }
    
    private function verificaIntegrita()
    {
        $this->info('✅ VERIFICA INTEGRITÀ');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Verifica saltata (dry-run)');
            return;
        }
        
        // Verifica PF
        $pfTotali = ProdottoFinito::count();
        $this->info("PF totali: {$pfTotali}");
        
        // Verifica componenti
        $componentiTotali = ComponenteProdotto::count();
        $this->info("Componenti totali: {$componentiTotali}");
        
        // Verifica duplicati componenti
        $duplicati = DB::table('componenti_prodotto')
            ->select('articolo_id', DB::raw('COUNT(*) as count'))
            ->groupBy('articolo_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
            
        if ($duplicati->count() > 0) {
            $this->warn("⚠️ Trovati {$duplicati->count()} articoli con componenti duplicati");
        } else {
            $this->info("✅ Nessun duplicato nei componenti");
        }
    }
    
    private function displaySummary()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO MIGRAZIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("PF migrati: {$this->stats['pf_migrati']}");
        $this->info("Componenti migrati: {$this->stats['componenti_migrati']}");
        $this->info("Duplicati gestiti: {$this->stats['duplicati_gestiti']}");
        $this->info("Errori: {$this->stats['errori']}");
        
        if ($this->dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY-RUN completato. Esegui senza --dry-run per applicare le modifiche.');
        } else {
            $this->newLine();
            $this->info('✅ Migrazione completata!');
        }
    }
}




