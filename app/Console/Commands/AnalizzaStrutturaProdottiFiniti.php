<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalizzaStrutturaProdottiFiniti extends Command
{
    protected $signature = 'produzione:analizza-struttura';
    protected $description = 'Analizza la struttura delle tabelle prodotti finiti in MSSQL produzione';

    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔍 ANALISI STRUTTURA PRODOTTI FINITI - DB PRODUZIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            // Test connessione
            DB::connection('mssql_prod')->getPdo();
            $this->info('✅ Connessione a MSSQL produzione: OK');
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('❌ Impossibile connettersi al DB produzione: ' . $e->getMessage());
            return 1;
        }

        // ==========================================
        // 1. ANALISI TABELLA mag_prodotti_finiti
        // ==========================================
        $this->info('📋 TABELLA: mag_prodotti_finiti');
        $this->info('─────────────────────────────────────────────────────');
        
        try {
            // Schema tabella
            $columns = DB::connection('mssql_prod')
                ->select("SELECT 
                    COLUMN_NAME, 
                    DATA_TYPE, 
                    CHARACTER_MAXIMUM_LENGTH,
                    IS_NULLABLE,
                    COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'mag_prodotti_finiti'
                ORDER BY ORDINAL_POSITION");
            
            if (empty($columns)) {
                $this->warn('⚠️  Tabella mag_prodotti_finiti NON trovata nel DB produzione');
            } else {
                $this->table(
                    ['Campo', 'Tipo', 'Lunghezza', 'Nullable', 'Default'],
                    array_map(fn($c) => [
                        $c->COLUMN_NAME,
                        $c->DATA_TYPE,
                        $c->CHARACTER_MAXIMUM_LENGTH ?? '-',
                        $c->IS_NULLABLE,
                        $c->COLUMN_DEFAULT ?? '-'
                    ], $columns)
                );
                
                // Conteggio record
                $count = DB::connection('mssql_prod')->table('mag_prodotti_finiti')->count();
                $this->info("📊 Record totali: {$count}");
            }
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ==========================================
        // 2. ANALISI TABELLA mag_diba
        // ==========================================
        $this->info('📋 TABELLA: mag_diba (Distinta Base Articoli)');
        $this->info('─────────────────────────────────────────────────────');
        
        try {
            $columns = DB::connection('mssql_prod')
                ->select("SELECT 
                    COLUMN_NAME, 
                    DATA_TYPE, 
                    CHARACTER_MAXIMUM_LENGTH,
                    IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'mag_diba'
                ORDER BY ORDINAL_POSITION");
            
            if (empty($columns)) {
                $this->warn('⚠️  Tabella mag_diba NON trovata nel DB produzione');
            } else {
                $this->table(
                    ['Campo', 'Tipo', 'Lunghezza', 'Nullable'],
                    array_map(fn($c) => [
                        $c->COLUMN_NAME,
                        $c->DATA_TYPE,
                        $c->CHARACTER_MAXIMUM_LENGTH ?? '-',
                        $c->IS_NULLABLE
                    ], $columns)
                );
                
                $count = DB::connection('mssql_prod')->table('mag_diba')->count();
                $this->info("📊 Record totali (componenti): {$count}");
            }
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ==========================================
        // 3. ANALISI DATI
        // ==========================================
        $this->info('📊 ANALISI DATI PRODUZIONE');
        $this->info('─────────────────────────────────────────────────────');
        
        try {
            $stats = (object)[
                'prodotti_finiti_totali' => DB::connection('mssql_prod')->table('mag_prodotti_finiti')->count(),
                'componenti_totali' => DB::connection('mssql_prod')->table('mag_diba')->count(),
            ];
            
            if ($stats->prodotti_finiti_totali > 0) {
                $stats->media_componenti = round($stats->componenti_totali / $stats->prodotti_finiti_totali, 2);
                
                // Prodotti finiti per tipologia (se campo esiste)
                $perTipologia = DB::connection('mssql_prod')
                    ->table('mag_prodotti_finiti')
                    ->select(DB::raw('COUNT(*) as count'))
                    ->first();
                
                // Articoli che sono prodotti finiti (hanno id_pf)
                $articoliPF = DB::connection('mssql_prod')
                    ->table('mag_articoli')
                    ->whereNotNull('id_pf')
                    ->count();
                
                $this->line("• Prodotti finiti totali: <fg=yellow>{$stats->prodotti_finiti_totali}</>");
                $this->line("• Componenti totali (DIBA): <comment>{$stats->componenti_totali}</comment>");
                $this->line("• Media componenti per prodotto: <info>{$stats->media_componenti}</info>");
                $this->line("• Articoli risultanti (con id_pf): <fg=green>{$articoliPF}</>");
            } else {
                $this->warn('⚠️  Nessun prodotto finito trovato nel DB produzione');
            }
        } catch (\Exception $e) {
            $this->error('Errore analisi dati: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ==========================================
        // 4. ESEMPI PRODOTTI FINITI
        // ==========================================
        $this->info('📝 ESEMPI PRODOTTI FINITI (ultimi 10)');
        $this->info('─────────────────────────────────────────────────────');
        
        try {
            $esempi = DB::connection('mssql_prod')
                ->table('mag_prodotti_finiti')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
            
            if ($esempi->isNotEmpty()) {
                foreach ($esempi as $pf) {
                    // Conta componenti
                    $numComponenti = DB::connection('mssql_prod')
                        ->table('mag_diba')
                        ->where('id_pf', $pf->id)
                        ->count();
                    
                    $this->line("ID: <comment>{$pf->id}</comment> | Desc: " . substr($pf->descrizione ?? 'N/A', 0, 50) . " | Componenti: <fg=cyan>{$numComponenti}</>");
                }
            }
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ==========================================
        // 5. CONFRONTO STRUTTURA VECCHIA VS NUOVA
        // ==========================================
        $this->info('🔄 CONFRONTO STRUTTURE');
        $this->info('─────────────────────────────────────────────────────');
        
        $this->line('<fg=cyan>VECCHIA STRUTTURA (MSSQL Produzione):</>');
        $this->line('  mag_prodotti_finiti → ProdottoFinito');
        $this->line('  mag_diba → Componenti (distinta base)');
        $this->line('  mag_articoli.id_pf → Link articolo finale');
        $this->newLine();
        
        $this->line('<fg=green>NUOVA STRUTTURA (MySQL athena_v2):</>');
        $this->line('  prodotti_finiti → ProdottoFinito (migrata)');
        $this->line('  diba → Componenti');
        $this->line('  Usa: categoria_merceologica_id invece di id_magazzino');
        
        $this->newLine();

        // ==========================================
        // 6. RACCOMANDAZIONI
        // ==========================================
        $this->info('💡 RACCOMANDAZIONI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->warn('Analisi struttura completata. Valuta le seguenti opzioni:');
        $this->newLine();
        $this->line('1️⃣  <fg=green>MIGRAZIONE DATI</> (se struttura OK)');
        $this->line('   - Importa mag_prodotti_finiti → prodotti_finiti');
        $this->line('   - Importa mag_diba → diba');
        $this->line('   - Aggiorna riferimenti articoli');
        $this->newLine();
        $this->line('2️⃣  <fg=yellow>RISTRUTTURAZIONE</> (se serve migliorare)');
        $this->line('   - Ridisegna schema più robusto');
        $this->line('   - Aggiungi vincoli referenziali');
        $this->line('   - Migliora tracking storico');
        
        $this->newLine();
        $this->info('✅ Analisi completata!');
    }
}
