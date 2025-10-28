<?php

namespace App\Console\Commands;

use App\Models\ProdottoFinito;
use App\Models\ComponenteProdotto;
use App\Models\Articolo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigraProdottiFiniti extends Command
{
    protected $signature = 'produzione:migra-prodotti-finiti 
                            {--dry-run : Mostra solo cosa verrebbe fatto senza applicare modifiche}
                            {--limit= : Limita il numero di prodotti da migrare}';

    protected $description = 'Migra prodotti finiti e DIBA dal database MSSQL produzione';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        if ($dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà applicata');
            $this->newLine();
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📦 MIGRAZIONE PRODOTTI FINITI DA MSSQL');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            // Test connessione MSSQL
            DB::connection('mssql_prod')->getPdo();
            $this->info('✅ Connessione MSSQL produzione: OK');
        } catch (\Exception $e) {
            $this->error('❌ Impossibile connettersi: ' . $e->getMessage());
            return 1;
        }

        // Query prodotti finiti
        $query = DB::connection('mssql_prod')
            ->table('mag_prodotti_finiti')
            ->orderBy('id');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $prodottiMssql = $query->get();
        
        $this->info("📋 Trovati {$prodottiMssql->count()} prodotti finiti da migrare");
        $this->newLine();

        if ($prodottiMssql->isEmpty()) {
            $this->warn('⚠️  Nessun prodotto finito trovato');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($prodottiMssql->count());
        $progressBar->start();

        $migrati = 0;
        $errori = 0;
        $erroriLog = [];

        foreach ($prodottiMssql as $pfMssql) {
            try {
                if (!$dryRun) {
                    DB::beginTransaction();
                }
                
                // 1. Trova articolo risultante (quello con id_pf nel vecchio DB)
                $articoloVecchio = DB::connection('mssql_prod')
                    ->table('mag_articoli')
                    ->where('id_pf', $pfMssql->id)
                    ->first();
                
                if (!$articoloVecchio) {
                    throw new \Exception("Articolo risultante non trovato per PF ID {$pfMssql->id}");
                }
                
                // 2. Mappa vecchio id_magazzino a nuova categoria_merceologica_id
                $categoriaId = $this->mappaMagazzino($articoloVecchio->id_magazzino);
                
                // 3. Crea prodotto finito
                if (!$dryRun) {
                    $prodottoFinito = ProdottoFinito::create([
                        'codice' => 'PF-' . $pfMssql->id, // Usa ID vecchio come riferimento
                        'descrizione' => $pfMssql->descrizione,
                        'tipologia' => 'prodotto_finito',
                        'magazzino_id' => $categoriaId,
                        'costo_materiali' => $pfMssql->valore_magazzino ?? 0,
                        'costo_totale' => $pfMssql->valore_magazzino ?? 0,
                        'stato' => 'completato', // Già assemblati in produzione
                        'data_completamento' => $pfMssql->data,
                        'note' => "Migrato da MSSQL - ID originale: {$pfMssql->id}",
                    ]);
                    
                    // 4. Migra componenti (DIBA)
                    $componenti = DB::connection('mssql_prod')
                        ->table('mag_diba')
                        ->where('id_pf', $pfMssql->id)
                        ->get();
                    
                    foreach ($componenti as $diba) {
                        // Trova articolo componente nel nuovo DB (match per codice)
                        $articoloVecchioDB = DB::connection('mssql_prod')
                            ->table('mag_articoli')
                            ->where('id', $diba->id_articolo)
                            ->first();
                        
                        if ($articoloVecchioDB) {
                            // Cerca nel nuovo DB per codice simile
                            $articoloNuovo = Articolo::where('codice', 'like', '%' . $articoloVecchioDB->carico . '%')
                                ->orWhere('numero_documento_carico', $articoloVecchioDB->carico)
                                ->first();
                            
                            if ($articoloNuovo) {
                                ComponenteProdotto::create([
                                    'prodotto_finito_id' => $prodottoFinito->id,
                                    'articolo_id' => $articoloNuovo->id,
                                    'quantita' => 1, // Default
                                    'costo_unitario' => $articoloNuovo->prezzo_acquisto ?? 0,
                                    'stato' => 'utilizzato',
                                ]);
                            }
                        }
                    }
                    
                    // 5. Collega articolo risultante se esiste nel nuovo DB
                    $articoloRisultante = Articolo::where('codice', 'like', '%' . $articoloVecchio->carico . '%')
                        ->first();
                    
                    if ($articoloRisultante) {
                        $articoloRisultante->update([
                            'prodotto_finito_id' => $prodottoFinito->id,
                            'assemblato_il' => $pfMssql->data,
                        ]);
                        
                        $prodottoFinito->update([
                            'articolo_risultante_id' => $articoloRisultante->id,
                        ]);
                    }
                    
                    DB::commit();
                }
                
                $migrati++;
                
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                $errori++;
                $erroriLog[] = "PF ID {$pfMssql->id}: {$e->getMessage()}";
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Riepilogo
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO MIGRAZIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("   • Prodotti finiti totali: <fg=yellow>{$prodottiMssql->count()}</>");
        $this->line("   • Migrati con successo: <fg=green>{$migrati}</>");
        
        if ($errori > 0) {
            $this->line("   • Errori: <fg=red>{$errori}</>");
            $this->newLine();
            $this->warn('⚠️  ERRORI (primi 10):');
            foreach (array_slice($erroriLog, 0, 10) as $errore) {
                $this->line("   • {$errore}");
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->warn('✅ Analisi completata. Rimuovi --dry-run per applicare.');
        } else {
            $this->info('✅ Migrazione completata!');
        }
    }
    
    /**
     * Mappa vecchio id_magazzino a nuova categoria_merceologica_id
     */
    private function mappaMagazzino(int $vecchioId): int
    {
        // Mappa specifica per il tuo sistema
        // Adatta in base alla tua configurazione
        $mapping = [
            9 => 9,   // Gioielleria → Gioielleria
            22 => 9,  // Semilavorati Roma → Gioielleria
            // Aggiungi altri mapping se necessario
        ];
        
        return $mapping[$vecchioId] ?? 9; // Default categoria 9
    }
}
