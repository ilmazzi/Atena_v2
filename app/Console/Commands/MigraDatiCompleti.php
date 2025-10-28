<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Articolo;
use App\Models\Giacenza;
use App\Models\Ddt;
use App\Models\DdtDettaglio;
use App\Models\Fattura;
use App\Models\FatturaDettaglio;
use App\Models\CaricoDettaglio;
use App\Models\Fornitore;

class MigraDatiCompleti extends Command
{
    protected $signature = 'migra:dati-completi {--dry-run : Modalità dry-run} {--clean : Pulisci dati esistenti}';
    protected $description = 'Migrazione completa con gestione duplicati';

    private $dryRun = false;
    private $stats = [
        'fornitori' => 0,
        'articoli' => 0,
        'giacenze' => 0,
        'ddt' => 0,
        'ddt_dettagli' => 0,
        'fatture' => 0,
        'fatture_dettagli' => 0,
        'articoli_con_fornitore' => 0,
        'ddt_con_fornitore' => 0,
        'pf_migrati' => 0,
        'componenti_migrati' => 0,
        'duplicati_gestiti' => 0,
        'errori' => 0
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $clean = $this->option('clean');
        
        $this->info('🚀 MIGRAZIONE DATI COMPLETI');
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
            $this->pulisciDati();
        }
        
        // FASE 2: Migra fornitori
        $this->migraFornitori();
        
        // FASE 3: Migra articoli con gestione duplicati
        $this->migraArticoli();
        
        // FASE 4: Migra giacenze
        $this->migraGiacenze();
        
        // FASE 5: Migra DDT
        $this->migraDdt();
        
        // FASE 6: Migra Fatture
        $this->migraFatture();
        
        // FASE 7: Fix fornitori in articoli e DDT
        $this->fixFornitori();
        
        // FASE 8: Pulisci duplicati DDT
        $this->pulisciDuplicati();
        
        // FASE 9: Ricalcola conteggi
        $this->ricalcolaConteggi();
        
        // FASE 10: Migra prodotti finiti storici
        $this->migraProdottiFiniti();
        
        // FASE 11: Verifica integrità
        $this->verificaIntegrita();
        
        $this->newLine();
        $this->displaySummary();
        
        return 0;
    }
    
    private function pulisciDati()
    {
        $this->info('🧹 PULIZIA DATI ESISTENTI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Pulizia saltata (dry-run)');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Pulisci in ordine per FK
            DB::table('ddt_dettagli')->delete();
            DB::table('fatture_dettagli')->delete();
            DB::table('carico_dettagli')->delete();
            DB::table('giacenze')->delete();
            DB::table('ddt')->delete();
            DB::table('fatture')->delete();
            DB::table('articoli')->delete();
            DB::table('fornitori')->delete();
            
            DB::commit();
            $this->info('✅ Pulizia completata!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Errore durante pulizia: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function migraFornitori()
    {
        $this->info('🏢 MIGRAZIONE FORNITORI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $fornitori = DB::connection('mssql_prod')
            ->table('mag_fornitori')
            ->get();
            
        $this->info("Fornitori da migrare: {$fornitori->count()}");
        
        $progressBar = $this->output->createProgressBar($fornitori->count());
        $progressBar->start();
        
        foreach ($fornitori as $forn) {
            try {
                if (!$this->dryRun) {
                    Fornitore::create([
                        'id' => $forn->id,
                        'codice' => $forn->codice ?? 'FOR' . str_pad($forn->id, 4, '0', STR_PAD_LEFT),
                        'ragione_sociale' => $forn->ragione_sociale ?? $forn->nome ?? 'Fornitore ' . $forn->id,
                        'partita_iva' => $forn->partita_iva ?? null,
                        'codice_fiscale' => $forn->codice_fiscale ?? null,
                        'indirizzo' => $forn->indirizzo ?? null,
                        'citta' => $forn->citta ?? null,
                        'cap' => $forn->cap ?? null,
                        'provincia' => $forn->provincia ?? null,
                        'email' => $forn->email ?? null,
                        'telefono' => $forn->telefono ?? null,
                        'attivo' => $forn->attivo ?? true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $this->stats['fornitori']++;
                
            } catch (\Exception $e) {
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore fornitore {$forn->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function migraArticoli()
    {
        $this->info('💎 MIGRAZIONE ARTICOLI CON GESTIONE DUPLICATI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Recupera articoli dalla vista
        $articoli = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->get();
            
        $this->info("Articoli trovati: {$articoli->count()}");
        
        // Gestisci duplicati con suffissi
        $articoliConSuffissi = collect($articoli)
            ->groupBy(function($art) {
                return $art->id_magazzino . '-' . $art->carico;
            })
            ->flatMap(function($group, $codiceBase) {
                if ($group->count() == 1) {
                    // Nessun duplicato
                    $art = $group->first();
                    $art->codice_unico = $codiceBase;
                    return [$art];
                }
                
                // Gestisci duplicati con suffissi
                $this->stats['duplicati_gestiti'] += $group->count();
                return $group->map(function($art, $index) use ($codiceBase) {
                    $art->codice_unico = $codiceBase . '-' . ($index + 1);
                    $art->codice_originale = $codiceBase;
                    return $art;
                });
            });
        
        $this->info("Articoli da migrare: {$articoliConSuffissi->count()}");
        $this->info("Duplicati gestiti: {$this->stats['duplicati_gestiti']}");
        
        $progressBar = $this->output->createProgressBar($articoliConSuffissi->count());
        $progressBar->start();
        
        foreach ($articoliConSuffissi as $art) {
            try {
                if (!$this->dryRun) {
                    // Verifica se articolo già esiste per ID
                    $existing = Articolo::find($art->id);
                    if ($existing) {
                        // Articolo già presente, salta
                        $progressBar->advance();
                        continue;
                    }
                    
                    // Inserisci nuovo articolo
                    Articolo::create([
                        'id' => $art->id,
                        'codice' => $art->codice_unico,
                        'descrizione' => $art->descrizione ?? 'Articolo ' . $art->id,
                        'categoria_merceologica_id' => $art->id_magazzino,
                        'sede_id' => 1, // Default
                        'fornitore_id' => null,
                        'materiale' => $art->materiale ?? null,
                        'caratura' => $art->carati ?? null,
                        'prezzo_acquisto' => $art->costo_unitario ?? 0,
                        'stato' => $this->mapStato($art),
                        'tipo_carico' => $art->fatturato == 1 ? 'fattura' : 'ddt',
                        'numero_documento_carico' => $art->numero_documento ?? null,
                        'data_carico' => $art->data_documento ?? null,
                        'in_vetrina' => (bool)($art->vetrina ?? false),
                        'note' => $art->note ?? null,
                        'caratteristiche' => json_encode([
                            'marca' => $art->marca ?? null,
                            'referenza' => $art->referenza ?? null,
                            'oro' => $art->oro ?? null,
                            'pietre' => $art->pietre ?? null,
                            'brill' => $art->brill ?? null,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $this->stats['articoli']++;
                
            } catch (\Exception $e) {
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore articolo {$art->id} ({$art->codice_unico}): " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function migraGiacenze()
    {
        $this->info('📊 MIGRAZIONE GIACENZE');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Prendi TUTTI gli articoli migrati
        $articoli = Articolo::all();
        
        $this->info("Giacenze da creare: {$articoli->count()}");
        
        $progressBar = $this->output->createProgressBar($articoli->count());
        $progressBar->start();
        
        foreach ($articoli as $articolo) {
            try {
                if (!$this->dryRun) {
                    // Verifica se giacenza già esiste
                    $giacenzaExists = Giacenza::where('articolo_id', $articolo->id)->exists();
                    if ($giacenzaExists) {
                        $progressBar->advance();
                        continue;
                    }
                    
                    // Recupera dati giacenza da MSSQL se esistono
                    $giacMssql = DB::connection('mssql_prod')
                        ->table('elenco_articoli_magazzino')
                        ->where('id', $articolo->id)
                        ->first();
                    
                    Giacenza::create([
                        'articolo_id' => $articolo->id,
                        'categoria_merceologica_id' => $articolo->categoria_merceologica_id,
                        'sede_id' => $articolo->sede_id ?? 1,
                        'quantita' => $giacMssql->qta ?? 1,
                        'quantita_residua' => $giacMssql->qta_residua ?? $giacMssql->qta ?? 1,
                        'quantita_deposito' => 0,
                        'costo_unitario' => $giacMssql->costo_unitario ?? $articolo->prezzo_acquisto ?? 0,
                        'scaffale' => $giacMssql->ubicazione ?? null,
                        'note' => $giacMssql->ubicazione ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $this->stats['giacenze']++;
                }
                
            } catch (\Exception $e) {
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore giacenza articolo {$articolo->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function migraDdt()
    {
        $this->info('📄 MIGRAZIONE DDT');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Migra testate DDT
        $ddt = DB::connection('mssql_prod')
            ->table('mag_ddt_articoli_testate')
            ->whereNotNull('numero_documento')
            ->get();
            
        $this->info("DDT da migrare: {$ddt->count()}");
        
        $progressBar = $this->output->createProgressBar($ddt->count());
        $progressBar->start();
        
        foreach ($ddt as $d) {
            try {
                if (!$this->dryRun) {
                    Ddt::create([
                        'id' => $d->id,
                        'numero' => $d->numero_documento,
                        'data_documento' => $d->data_documento ?? now(),
                        'anno' => date('Y', strtotime($d->data_documento ?? 'now')),
                        'fornitore_id' => $d->fornitore ?? null,
                        'stato' => 'caricato',
                        'note' => $d->note ?? null,
                        'created_at' => $d->created_at ?? now(),
                        'updated_at' => $d->updated_at ?? now(),
                    ]);
                }
                
                $this->stats['ddt']++;
                
            } catch (\Exception $e) {
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore DDT {$d->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        // Migra dettagli DDT
        $this->migraDdtDettagli();
    }
    
    private function migraDdtDettagli()
    {
        $this->info('📋 MIGRAZIONE DETTAGLI DDT');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $dettagli = DB::connection('mssql_prod')
            ->table('mag_ddt_articoli_dettagli')
            ->get();
            
        $this->info("Dettagli DDT da migrare: {$dettagli->count()}");
        
        $progressBar = $this->output->createProgressBar($dettagli->count());
        $progressBar->start();
        
        foreach ($dettagli as $det) {
            try {
                if (!$this->dryRun) {
                    // Verifica che DDT e articolo esistano
                    $ddtExists = Ddt::where('id', $det->id_testata)->exists();
                    $articoloExists = Articolo::where('id', $det->id_articolo)->exists();
                    
                    if ($ddtExists && $articoloExists) {
                        DdtDettaglio::create([
                            'ddt_id' => $det->id_testata,
                            'articolo_id' => $det->id_articolo,
                            'quantita' => $det->quantita ?? 1,
                            'prezzo_unitario' => $det->prezzo_unitario ?? 0,
                            'caricato' => true,
                            'created_at' => now(),
                        ]);
                        
                        $this->stats['ddt_dettagli']++;
                    }
                }
                
            } catch (\Exception $e) {
                $this->stats['errori']++;
                $this->newLine();
                $this->error("❌ Errore dettaglio DDT {$det->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function migraFatture()
    {
        $this->info('🧾 MIGRAZIONE FATTURE');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Implementa migrazione fatture se necessario
        $this->info('⏭️ Migrazione fatture non implementata (da fare se necessario)');
    }
    
    private function fixFornitori()
    {
        $this->info('🔧 FIX FORNITORI IN ARTICOLI E DDT');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Fix fornitori saltato (dry-run)');
            return;
        }
        
        // Fix fornitori in articoli (dalla relazione con DDT)
        $articoliConFornitore = DB::connection('mssql_prod')
            ->table('mag_articoli')
            ->join('mag_ddt_articoli_dettagli', 'mag_ddt_articoli_dettagli.id_articolo', '=', 'mag_articoli.id')
            ->join('mag_ddt_articoli_testate', 'mag_ddt_articoli_testate.id', '=', 'mag_ddt_articoli_dettagli.id_testata')
            ->whereNotNull('mag_ddt_articoli_testate.fornitore')
            ->where('mag_ddt_articoli_testate.fornitore', '!=', 0)
            ->select(['mag_articoli.id', 'mag_ddt_articoli_testate.fornitore as fornitore_id'])
            ->distinct()
            ->get();
            
        $this->info("Articoli con fornitore da DDT: {$articoliConFornitore->count()}");
        
        $progressBar = $this->output->createProgressBar($articoliConFornitore->count());
        $progressBar->start();
        
        foreach ($articoliConFornitore as $art) {
            // Verifica che il fornitore esista in MySQL
            $fornExists = Fornitore::where('id', $art->fornitore_id)->exists();
            
            if ($fornExists) {
                DB::table('articoli')
                    ->where('id', $art->id)
                    ->update(['fornitore_id' => $art->fornitore_id]);
                $this->stats['articoli_con_fornitore']++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Aggiornati {$this->stats['articoli_con_fornitore']} articoli con fornitore");
        
        // Fix fornitori in DDT
        $ddtConFornitore = DB::connection('mssql_prod')
            ->table('mag_ddt_articoli_testate')
            ->whereNotNull('fornitore')
            ->where('fornitore', '!=', 0)
            ->select(['id', 'fornitore'])
            ->get();
            
        $this->info("DDT con fornitore: {$ddtConFornitore->count()}");
        
        $progressBar2 = $this->output->createProgressBar($ddtConFornitore->count());
        $progressBar2->start();
        
        foreach ($ddtConFornitore as $ddt) {
            if (is_numeric($ddt->fornitore)) {
                // fornitore è già un ID
                DB::table('ddt')
                    ->where('id', $ddt->id)
                    ->update(['fornitore_id' => $ddt->fornitore]);
                $this->stats['ddt_con_fornitore']++;
            }
            
            $progressBar2->advance();
        }
        
        $progressBar2->finish();
        $this->newLine();
        $this->info("✅ Aggiornati {$this->stats['ddt_con_fornitore']} DDT con fornitore");
    }
    
    private function pulisciDuplicati()
    {
        $this->info('🧹 PULIZIA DUPLICATI DDT');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Pulizia duplicati saltata (dry-run)');
            return;
        }
        
        // Esegui il comando di pulizia duplicati
        $this->call('documenti:pulisci-duplicati');
    }
    
    private function ricalcolaConteggi()
    {
        $this->info('🔢 RICALCOLO CONTEGGI DOCUMENTI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Ricalcolo conteggi saltato (dry-run)');
            return;
        }
        
        // Esegui il comando di ricalcolo
        $this->call('documenti:ricalcola-conteggi');
    }
    
    private function migraProdottiFiniti()
    {
        $this->info('🏭 MIGRAZIONE PRODOTTI FINITI STORICI');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Migrazione PF saltata (dry-run)');
            return;
        }
        
        // Esegui il comando di migrazione PF V2 (quello corretto)
        $this->call('pf:migra-v2');
    }
    
    private function mapStato($art): string
    {
        if (($art->deposito ?? 0) == 1 || ($art->deposito ?? 0) == 2) {
            return 'in_deposito';
        }
        if (($art->scaricato ?? 0) == 1) {
            return 'venduto';
        }
        return 'disponibile';
    }
    
    private function verificaIntegrita()
    {
        $this->info('✅ VERIFICA INTEGRITÀ');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Verifica saltata (dry-run)');
            return;
        }
        
        $this->info("Articoli: " . Articolo::count());
        $this->info("Giacenze: " . Giacenza::count());
        $this->info("DDT: " . Ddt::count());
        $this->info("Dettagli DDT: " . DdtDettaglio::count());
    }
    
    private function displaySummary()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO MIGRAZIONE COMPLETA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        $this->info('📦 DATI BASE:');
        $this->info("   Fornitori: {$this->stats['fornitori']}");
        $this->info("   Articoli: {$this->stats['articoli']}");
        $this->info("   Giacenze: {$this->stats['giacenze']}");
        $this->info("   DDT: {$this->stats['ddt']}");
        $this->info("   Dettagli DDT: {$this->stats['ddt_dettagli']}");
        $this->newLine();
        
        if (!$this->dryRun && ($this->stats['articoli_con_fornitore'] > 0 || $this->stats['ddt_con_fornitore'] > 0)) {
            $this->info('🔗 ASSOCIAZIONI FORNITORI:');
            $this->info("   Articoli collegati: {$this->stats['articoli_con_fornitore']}");
            $this->info("   DDT collegati: {$this->stats['ddt_con_fornitore']}");
            $this->newLine();
        }
        
        if (!$this->dryRun) {
            $this->info('🏭 PRODOTTI FINITI:');
            $pfCount = \App\Models\ProdottoFinito::count();
            $compCount = \App\Models\ComponenteProdotto::count();
            $this->info("   Prodotti Finiti: {$pfCount}");
            $this->info("   Componenti: {$compCount}");
            $this->newLine();
            
            $this->info('📊 STATO FINALE DATABASE:');
            $this->info("   DDT dopo pulizia duplicati: " . \App\Models\Ddt::count());
            $this->info("   DDT con articoli: " . \App\Models\Ddt::where('numero_articoli', '>', 0)->count());
            $this->newLine();
        }
        
        $this->info("Duplicati gestiti: {$this->stats['duplicati_gestiti']}");
        $this->info("Errori: {$this->stats['errori']}");
        
        if ($this->dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY-RUN completato. Esegui senza --dry-run per applicare le modifiche.');
        } else {
            $this->newLine();
            $this->info('✅ MIGRAZIONE COMPLETA TERMINATA CON SUCCESSO!');
        }
    }
}
