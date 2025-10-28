<?php

namespace App\Console\Commands;

use App\Models\Articolo;
use App\Models\Ddt;
use App\Models\Fattura;
use App\Models\DdtDettaglio;
use App\Models\FatturaDettaglio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecuperaArticoliOrfani extends Command
{
    protected $signature = 'articoli:recupera-orfani 
                            {--dry-run : Mostra solo cosa verrebbe fatto senza applicare modifiche}';

    protected $description = 'Recupera articoli orfani creando i dettagli DDT/Fattura mancanti';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà applicata al database');
            $this->newLine();
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔧 RECUPERO ARTICOLI ORFANI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // 1. Trova articoli orfani
        $articoliIdInDettagli = DB::table('ddt_dettagli')
            ->distinct()
            ->pluck('articolo_id')
            ->toArray();

        $articoliOrfani = Articolo::whereNotNull('numero_documento_carico')
            ->whereNotIn('id', $articoliIdInDettagli)
            ->orderBy('numero_documento_carico')
            ->get();

        $this->info("📦 Trovati <fg=yellow>{$articoliOrfani->count()}</> articoli orfani");
        $this->newLine();

        if ($articoliOrfani->isEmpty()) {
            $this->info('✅ Nessun articolo orfano trovato!');
            return;
        }

        $progressBar = $this->output->createProgressBar($articoliOrfani->count());
        $progressBar->start();

        $recuperati = 0;
        $nonTrovati = 0;
        $errori = 0;
        $erroriLog = [];

        foreach ($articoliOrfani as $articolo) {
            $numeroDoc = $articolo->numero_documento_carico;
            
            // Salta valori non validi
            if (empty($numeroDoc) || $numeroDoc === '0' || $numeroDoc === '') {
                $nonTrovati++;
                $progressBar->advance();
                continue;
            }

            // Cerca DDT corrispondente
            $ddt = Ddt::where('numero', $numeroDoc)
                ->where('fornitore_id', $articolo->fornitore_id)
                ->first();

            if (!$ddt) {
                // Prova senza fornitore
                $ddt = Ddt::where('numero', $numeroDoc)->first();
            }

            // Se non trova DDT, cerca Fattura
            $fattura = null;
            if (!$ddt) {
                $fattura = Fattura::where('numero', $numeroDoc)
                    ->where('fornitore_id', $articolo->fornitore_id)
                    ->first();
                
                if (!$fattura) {
                    $fattura = Fattura::where('numero', $numeroDoc)->first();
                }
            }

            if ($ddt) {
                // Crea dettaglio DDT mancante
                if (!$dryRun) {
                    try {
                        DB::beginTransaction();
                        
                        // Verifica che non esista già
                        $esistente = DdtDettaglio::where('ddt_id', $ddt->id)
                            ->where('articolo_id', $articolo->id)
                            ->first();
                        
                        if (!$esistente) {
                            DB::table('ddt_dettagli')->insert([
                                'ddt_id' => $ddt->id,
                                'articolo_id' => $articolo->id,
                                'quantita' => 1, // Default, potrebbe essere errato
                                'caricato' => true,
                            ]);
                            
                            // Ricalcola conteggi DDT
                            $numeroArticoli = DB::table('ddt_dettagli')
                                ->where('ddt_id', $ddt->id)
                                ->distinct('articolo_id')
                                ->count('articolo_id');
                            
                            $quantitaTotale = DB::table('ddt_dettagli')
                                ->where('ddt_id', $ddt->id)
                                ->sum('quantita');
                            
                            DB::table('ddt')->where('id', $ddt->id)->update([
                                'numero_articoli' => $numeroArticoli,
                                'quantita_totale' => $quantitaTotale,
                            ]);
                            
                            $recuperati++;
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errori++;
                        $erroriLog[] = "Articolo {$articolo->codice} (Doc: {$numeroDoc}): {$e->getMessage()}";
                    }
                } else {
                    $recuperati++;
                }
            } elseif ($fattura) {
                // Crea dettaglio Fattura mancante
                if (!$dryRun) {
                    try {
                        DB::beginTransaction();
                        
                        $esistente = FatturaDettaglio::where('fattura_id', $fattura->id)
                            ->where('articolo_id', $articolo->id)
                            ->first();
                        
                        if (!$esistente) {
                            DB::table('fatture_dettagli')->insert([
                                'fattura_id' => $fattura->id,
                                'articolo_id' => $articolo->id,
                                'quantita' => 1,
                                'caricato' => true,
                            ]);
                            
                            // Ricalcola conteggi Fattura
                            $numeroArticoli = DB::table('fatture_dettagli')
                                ->where('fattura_id', $fattura->id)
                                ->distinct('articolo_id')
                                ->count('articolo_id');
                            
                            $quantitaTotale = DB::table('fatture_dettagli')
                                ->where('fattura_id', $fattura->id)
                                ->sum('quantita');
                            
                            DB::table('fatture')->where('id', $fattura->id)->update([
                                'numero_articoli' => $numeroArticoli,
                                'quantita_totale' => $quantitaTotale,
                            ]);
                            
                            $recuperati++;
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errori++;
                        $erroriLog[] = "Articolo {$articolo->codice} (Doc: {$numeroDoc}): {$e->getMessage()}";
                    }
                } else {
                    $recuperati++;
                }
            } else {
                $nonTrovati++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Riepilogo
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("   • Articoli orfani trovati: <fg=yellow>{$articoliOrfani->count()}</>");
        $this->line("   • Articoli recuperati: <fg=green>{$recuperati}</>");
        $this->line("   • Documenti non trovati: <fg=red>{$nonTrovati}</>");
        
        if ($errori > 0) {
            $this->line("   • Errori: <fg=red>{$errori}</>");
            $this->newLine();
            $this->warn('⚠️  ERRORI RISCONTRATI:');
            foreach ($erroriLog as $errore) {
                $this->line("   • {$errore}");
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->warn("✅ Analisi completata. Rimuovi --dry-run per applicare le modifiche.");
        } else {
            $this->info('✅ Recupero completato!');
            $this->newLine();
            $this->info('🔄 Ora esegui: php artisan documenti:ricalcola-conteggi');
        }
    }
}
