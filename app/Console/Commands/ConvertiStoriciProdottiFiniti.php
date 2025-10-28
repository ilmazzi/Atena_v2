<?php

namespace App\Console\Commands;

use App\Models\Articolo;
use App\Models\ProdottoFinito;
use App\Models\ComponenteProdotto;
use App\Models\Giacenza;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertiStoriciProdottiFiniti extends Command
{
    protected $signature = 'produzione:converti-storici-pf
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire}
                            {--limit= : Limita il numero di prodotti da convertire}
                            {--start-from= : Inizia da un ID specifico}';

    protected $description = 'Converte articoli storici Cat. 9/22 in prodotti finiti con componenti tracciati';

    private $stats = [
        'articoli_trovati' => 0,
        'pf_creati' => 0,
        'componenti_creati' => 0,
        'componenti_scaricati' => 0,
        'errori' => 0,
        'già_convertiti' => 0,
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $startFrom = $this->option('start-from');
        
        if ($dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà salvata');
        }
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📦 CONVERSIONE PRODOTTI FINITI STORICI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        // Test connessioni
        try {
            DB::connection('mssql_prod')->getPdo();
            $this->info('✅ Connessione MSSQL produzione: OK');
        } catch (\Exception $e) {
            $this->error('❌ Impossibile connettersi a MSSQL: ' . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        
        // Query articoli Cat. 9/22 (prodotti finiti storici)
        $query = Articolo::with('giacenza')
            ->whereIn('categoria_merceologica_id', [9, 22])
            ->whereNull('prodotto_finito_id') // Non già convertiti
            ->orderBy('id');
        
        if ($startFrom) {
            $query->where('id', '>=', $startFrom);
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $articoli = $query->get();
        $this->stats['articoli_trovati'] = $articoli->count();
        
        $this->info("📊 Trovati {$this->stats['articoli_trovati']} articoli da convertire");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($articoli->count());
        $progressBar->start();
        
        foreach ($articoli as $articolo) {
            try {
                if (!$dryRun) {
                    DB::beginTransaction();
                }
                
                $this->convertiArticolo($articolo, $dryRun);
                
                if (!$dryRun) {
                    DB::commit();
                }
                
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore articolo {$articolo->codice}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Riepilogo
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO CONVERSIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("Articoli trovati: <fg=cyan>{$this->stats['articoli_trovati']}</>");
        $this->line("Prodotti finiti creati: <fg=green>{$this->stats['pf_creati']}</>");
        $this->line("Componenti creati: <fg=yellow>{$this->stats['componenti_creati']}</>");
        $this->line("Componenti scaricati: <fg=blue>{$this->stats['componenti_scaricati']}</>");
        $this->line("Già convertiti (skippati): <comment>{$this->stats['già_convertiti']}</comment>");
        $this->line("Errori: <fg=red>{$this->stats['errori']}</>");
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY-RUN completato. Esegui senza --dry-run per applicare le modifiche.');
        } else {
            $this->newLine();
            $this->info('✅ Conversione completata!');
        }
        
        return 0;
    }
    
    private function convertiArticolo(Articolo $articolo, bool $dryRun)
    {
        // 1. Estrai ID dal codice articolo (9-1 → ID 1, 22-5 → ID 5)
        preg_match('/(\d+)-(\d+)/', $articolo->codice, $matches);
        
        if (!isset($matches[2])) {
            // Codice non valido, skippa
            if ($dryRun) {
                $this->warn("  ⚠️ Codice {$articolo->codice} formato non valido");
            }
            return;
        }
        
        $idPF = (int)$matches[2];
        
        // 2. Cerca prodotto finito in MSSQL tramite ID
        // Il codice 9-250 corrisponde al PF con ID 250 in MSSQL
        $vecchioPF = DB::connection('mssql_prod')
            ->table('mag_prodotti_finiti')
            ->where('id', $idPF)
            ->first();
        
        if (!$vecchioPF) {
            // PF non trovato in MSSQL, skippa (probabilmente creato dopo migrazione)
            return;
        }
        
        if ($dryRun) {
            $this->newLine();
            $this->info("📦 Articolo: {$articolo->codice} → PF MSSQL ID: {$idPF}");
            $this->line("   Descrizione: " . substr($vecchioPF->descrizione, 0, 50));
        }
        
        // Verifica se già convertito
        $pfEsistente = ProdottoFinito::where('codice', $articolo->codice)->first();
        if ($pfEsistente) {
            $this->stats['già_convertiti']++;
            return;
        }
        
        // 2. Recupera componenti da mag_diba
        $vecchiComponenti = DB::connection('mssql_prod')
            ->table('mag_diba')
            ->where('id_pf', $vecchioPF->id)
            ->get();
        
        if ($vecchiComponenti->isEmpty()) {
            // Skippa prodotti finiti senza componenti tracciati
            if ($dryRun) {
                $this->warn("   ⚠️ Nessun componente trovato in mag_diba");
            }
            return;
        }
        
        if ($dryRun) {
            $this->line("   Componenti trovati: {$vecchiComponenti->count()}");
        }
        
        // 3. Crea record prodotto_finito
        if (!$dryRun) {
            $prodottoFinito = ProdottoFinito::create([
                'codice' => $articolo->codice, // Usa stesso codice dell'articolo
                'descrizione' => $vecchioPF->descrizione,
                'tipologia' => 'prodotto_finito',
                'magazzino_id' => $articolo->categoria_merceologica_id,
                'stato' => 'completato',
                'costo_materiali' => $vecchioPF->valore_magazzino ?? 0,
                'costo_lavorazione' => 0,
                'costo_totale' => $vecchioPF->prezzo ?? $vecchioPF->valore_magazzino ?? 0,
                'data_inizio_lavorazione' => $vecchioPF->data ?? now(),
                'data_completamento' => $vecchioPF->data ?? now(),
                'creato_da' => 1, // Admin
                'assemblato_da' => 1,
                'note' => 'Importato da sistema storico',
            ]);
            
            // Collega articolo al prodotto finito
            $articolo->update(['prodotto_finito_id' => $prodottoFinito->id]);
            
            $this->stats['pf_creati']++;
        }
        
        // 5. Per ogni componente
        foreach ($vecchiComponenti as $vecchioComp) {
            // Salta se carico vuoto
            if (empty($vecchioComp->carico)) {
                if ($dryRun) {
                    $this->warn("     ⚠️ Componente con carico vuoto, skippato");
                }
                continue;
            }
            
            // Converti formato codice: "5/1006" → "5-1006"
            $codiceMySql = str_replace('/', '-', $vecchioComp->carico);
            
            // Trova articolo componente in MySQL tramite codice convertito
            $componente = Articolo::where('codice', $codiceMySql)->first();
            
            if (!$componente) {
                if ($dryRun) {
                    $this->warn("     ⚠️ Componente {$vecchioComp->carico} (MySQL: {$codiceMySql}) non trovato");
                }
                continue;
            }
            
            if ($dryRun) {
                $this->line("     ✅ {$codiceMySql} - Giacenza: " . ($componente->giacenza->quantita_residua ?? 'N/A'));
            }
            
            if (!$dryRun) {
                // Crea record componente_prodotto
                ComponenteProdotto::create([
                    'prodotto_finito_id' => $prodottoFinito->id,
                    'articolo_id' => $componente->id,
                    'quantita' => 1, // Assumo 1 pezzo per componente storico
                    'costo_unitario' => $componente->prezzo_acquisto ?? 0,
                    'costo_totale' => $componente->prezzo_acquisto ?? 0,
                    'stato' => 'prelevato',
                    'prelevato_il' => $vecchioPF->data ?? now(),
                    'prelevato_da' => 1,
                ]);
                
                $this->stats['componenti_creati']++;
                
                // Scarica componente (SOLO se giacenza_residua > 0)
                $giacenza = $componente->giacenza;
                if ($giacenza && $giacenza->quantita_residua > 0) {
                    $giacenza->decrement('quantita_residua', 1);
                    $this->stats['componenti_scaricati']++;
                }
                
                // Aggiorna stato componente
                $componente->update(['stato_articolo' => 'in_prodotto_finito']);
            }
        }
    }
}
