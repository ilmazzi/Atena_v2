<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Fornitore;
use App\Models\Articolo;
use App\Models\Giacenza;
use App\Models\Ddt;
use App\Models\DdtDettaglio;
use App\Models\ProdottoFinito;
use App\Models\ComponenteProdotto;
use App\Models\Movimentazione;
use App\Models\MovimentazioneDettaglio;

class MigraDatiCorretta extends Command
{
    protected $signature = 'migra:dati-corretta {--dry-run : Esegui senza modificare il database}';
    protected $description = 'Migrazione corretta: separare articoli da prodotti finiti';

    private $stats = [
        'utenti_migrati' => 0,
        'fornitori_migrati' => 0,
        'articoli_migrati' => 0,
        'prodotti_finiti_migrati' => 0,
        'componenti_migrati' => 0,
        'giacenze_migrate' => 0,
        'ddt_migrati' => 0,
        'fatture_migrate' => 0,
        'movimentazioni_create' => 0,
        'errori' => 0
    ];

    public function handle()
    {
        $this->info('🚀 MIGRAZIONE CORRETTA: SEPARARE ARTICOLI DA PRODOTTI FINITI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->dryRun = $this->option('dry-run');
        if ($this->dryRun) {
            $this->warn('⚠️ MODALITÀ DRY-RUN: Nessuna modifica al database');
        }

        try {
            // 0. Crea ubicazioni di default
            $this->creaUbicazioniDefault();
            
            // 0.5. Migra categorie merceologiche
            $this->migraCategorieMerceologiche();
            
            // 0.6. Migra utenti
            $this->migraUtenti();
            
            // 1. Migra fornitori
            $this->migraFornitori();
            
            // 2. Migra articoli (categoria 1-8, 10-21)
            $this->migraArticoli();
            
            // 3. Migra prodotti finiti (categoria 9,22)
            $this->migraProdottiFiniti();
            
            // 4. Verifica integrità
            $this->verificaIntegrita();
            
            // 5. Mostra statistiche
            $this->displaySummary();

        } catch (\Exception $e) {
            $this->error("❌ Errore durante la migrazione: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function creaUbicazioniDefault()
    {
        $this->info('📍 [0/4] CREAZIONE UBICAZIONI E SEDI DEFAULT');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Creazione ubicazioni saltata (dry-run)');
            return;
        }

        // Crea sede di default
        DB::table('sedi')->insertOrIgnore([
            'id' => 1,
            'codice' => 'CAV',
            'nome' => 'Cavour',
            'indirizzo' => 'Via Cavour',
            'citta' => 'Torino',
            'provincia' => 'TO',
            'cap' => '10123',
            'telefono' => '011-1234567',
            'email' => 'cavour@athena.it',
            'tipo' => 'negozio',
            'attivo' => 1,
            'note' => 'Sede creata per la migrazione',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crea ubicazione di default
        DB::table('ubicazioni')->insertOrIgnore([
            'id' => 1,
            'sede_id' => 1,
            'scaffale' => 'Default',
            'ripiano' => '1',
            'box' => 'A',
            'posizione' => 'Posizione Default',
            'codice' => 'DEF-001',
            'descrizione' => 'Ubicazione di default per la migrazione',
            'capacita_massima' => 1000,
            'articoli_presenti' => 0,
            'attivo' => 1,
            'note' => 'Ubicazione creata per la migrazione',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Creata sede e ubicazione di default");
    }

    private function migraCategorieMerceologiche()
    {
        $this->info('📁 [0.5/4] MIGRAZIONE CATEGORIE MERCEOLOGICHE');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Migrazione categorie saltata (dry-run)');
            return;
        }

        // Migra categorie merceologiche da mag_magazzini
        $categorieMssql = DB::connection('mssql_prod')
            ->table('mag_magazzini')
            ->get();

        $this->line("Categorie trovate: {$categorieMssql->count()}");
        $progressBar = $this->output->createProgressBar($categorieMssql->count());
        $progressBar->start();

        foreach ($categorieMssql as $cat) {
            DB::table('categorie_merceologiche')->insertOrIgnore([
                'id' => $cat->id,
                'sede_id' => 1, // Default CAVOUR
                'codice' => $cat->codice ?? 'MAG' . $cat->id,
                'nome' => $cat->nome,
                'indirizzo' => null,
                'citta' => 'Torino',
                'provincia' => 'TO',
                'cap' => '10123',
                'tipo' => 'principale',
                'attivo' => 1,
                'note' => 'Migrato da MSSQL - Magazzino ' . $cat->nome,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrate {$categorieMssql->count()} categorie merceologiche");
    }

    private function migraUtenti()
    {
        $this->info('👥 [0.6/4] MIGRAZIONE UTENTI');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Migrazione utenti saltata (dry-run)');
            return;
        }

        // Migra utenti da users
        $utentiMssql = DB::connection('mssql_prod')
            ->table('users')
            ->get();

        $this->line("Utenti trovati: {$utentiMssql->count()}");
        $progressBar = $this->output->createProgressBar($utentiMssql->count());
        $progressBar->start();

        foreach ($utentiMssql as $utente) {
            DB::table('users')->insertOrIgnore([
                'id' => $utente->id,
                'name' => $utente->name ?? $utente->username ?? 'Utente ' . $utente->id,
                'email' => $utente->email ?? 'utente' . $utente->id . '@athena.local',
                'password' => $utente->password ?? bcrypt('password'), // Password di default
                'email_verified_at' => now(),
                'created_at' => $utente->created_at ?? now(),
                'updated_at' => $utente->updated_at ?? now(),
            ]);
            $this->stats['utenti_migrati']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['utenti_migrati']} utenti");
    }

    private function migraFornitori()
    {
        $this->info('📋 [1/4] MIGRAZIONE FORNITORI');
        
        if ($this->dryRun) {
            $this->warn('⏭️ Migrazione fornitori saltata (dry-run)');
            return;
        }

        // Migra fornitori da mag_fornitori
        $fornitoriMssql = DB::connection('mssql_prod')
            ->table('mag_fornitori')
            ->get();

        $this->line("Fornitori trovati: {$fornitoriMssql->count()}");
        $progressBar = $this->output->createProgressBar($fornitoriMssql->count());
        $progressBar->start();

        foreach ($fornitoriMssql as $forn) {
            Fornitore::create([
                'id' => $forn->id,
                'ragione_sociale' => $forn->ragione_sociale,
                'codice_fiscale' => $forn->codice_fiscale ?? null,
                'partita_iva' => $forn->partita_iva ?? null,
                'indirizzo' => $forn->indirizzo ?? null,
                'citta' => $forn->citta ?? null,
                'cap' => $forn->cap ?? null,
                'telefono' => $forn->telefono ?? null,
                'email' => $forn->email ?? null,
                'note' => $forn->note ?? null,
            ]);
            $this->stats['fornitori_migrati']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['fornitori_migrati']} fornitori");
    }

    private function migraArticoli()
    {
        $this->info('📦 [2/4] MIGRAZIONE ARTICOLI (categoria 1-8, 10-21)');
        
        // Filtra solo articoli normali (escludi 9,22)
        $articoliMssql = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->whereNotIn('id_magazzino', [9, 22])
            ->get();

        $this->line("Articoli normali trovati: {$articoliMssql->count()}");
        
        // Gestisci duplicati con suffissi
        $articoliConSuffissi = $this->gestisciDuplicati($articoliMssql);
        
        $progressBar = $this->output->createProgressBar($articoliConSuffissi->count());
        $progressBar->start();

        foreach ($articoliConSuffissi as $art) {
            if (!$this->dryRun) {
                // Crea articolo preservando l'ID originale
                $articolo = Articolo::create([
                    'id' => $art->id, // MANTIENI ID ORIGINALE!
                    'codice' => $art->codice_unico,
                    'descrizione' => $art->descrizione,
                    'categoria_merceologica_id' => $art->id_magazzino,
                    'sede_id' => 1, // Default CAVOUR
                    'materiale' => $art->materiale,
                    'caratura' => $art->carati,
                    'prezzo_acquisto' => $art->costo_unitario ?? 0,
                    'in_vetrina' => (bool)($art->vetrina ?? false),
                    'note' => $art->note,
                    'caratteristiche' => json_encode([
                        'marca' => $art->marca,
                        'referenza' => $art->referenza,
                        'oro' => $art->oro,
                        'pietre' => $art->pietre,
                        'brill' => $art->brill,
                    ]),
                ]);

                // Crea giacenza
                Giacenza::create([
                    'articolo_id' => $articolo->id,
                    'sede_id' => 1, // Default CAVOUR
                    'quantita' => $art->qta ?? 1,
                    'quantita_residua' => $art->qta_residua ?? 1,
                    'costo_unitario' => $art->costo_unitario ?? 0,
                    'ubicazione_id' => 1, // Default
                ]);

                // Gestisci fornitore
                $this->gestisciFornitoreArticolo($articolo, $art);

                // Gestisci DDT
                $this->gestisciDdtArticolo($articolo, $art);

                $this->stats['articoli_migrati']++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['articoli_migrati']} articoli");
    }

    private function gestisciDuplicati($articoli)
    {
        return collect($articoli)
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
                return $group->map(function($art, $index) use ($codiceBase) {
                    $art->codice_unico = $codiceBase . '-' . ($index + 1);
                    return $art;
                });
            });
    }

    private function gestisciFornitoreArticolo($articolo, $art)
    {
        if ($this->dryRun) return;

        $fornitoreId = null;

        // Priorità 1: DDT != '0' → fornitore da DDT
        if ($art->numero_documento != '0' && $art->numero_documento) {
            $fornitoreId = $this->trovaFornitoreDaDdt($art);
        }
        
        // Priorità 2: DDT = '0' + fornitore_import → fornitore da fornitore_import
        if (!$fornitoreId && isset($art->fornitore_import) && $art->fornitore_import) {
            $fornitoreId = $this->trovaOCreaFornitoreDaImport($art->fornitore_import);
        }

        // Aggiorna articolo con fornitore
        if ($fornitoreId) {
            $articolo->update(['fornitore_id' => $fornitoreId]);
        }
    }

    private function trovaFornitoreDaDdt($art)
    {
        // Cerca fornitore da DDT
        $ddtMssql = DB::connection('mssql_prod')
            ->table('mag_ddt_articoli_testate')
            ->where('id', $art->id_testata)
            ->first();

        if ($ddtMssql && $ddtMssql->fornitore) {
            return $ddtMssql->fornitore;
        }

        return null;
    }

    private function trovaOCreaFornitoreDaImport($fornitoreImport)
    {
        // Cerca fornitore esistente
        $fornitore = Fornitore::where('ragione_sociale', 'LIKE', '%' . $fornitoreImport . '%')->first();
        
        if ($fornitore) {
            return $fornitore->id;
        }

        // Crea nuovo fornitore
        $nuovoFornitore = Fornitore::create([
            'ragione_sociale' => $fornitoreImport,
            'codice_fiscale' => null,
            'partita_iva' => null,
            'indirizzo' => null,
            'citta' => null,
            'cap' => null,
            'telefono' => null,
            'email' => null,
            'note' => 'Creato da fornitore_import',
        ]);

        return $nuovoFornitore->id;
    }

    private function gestisciDdtArticolo($articolo, $art)
    {
        if ($this->dryRun) return;

        // Solo se numero_documento != '0'
        if ($art->numero_documento == '0' || !$art->numero_documento) {
            return;
        }

        // Crea DDT
        $ddt = Ddt::create([
            'numero' => $art->numero_documento,
            'data_documento' => $art->data_documento,
            'anno' => $art->data_documento ? date('Y', strtotime($art->data_documento)) : date('Y'),
            'fornitore_id' => $articolo->fornitore_id ?? 1, // Default fornitore se null
            'sede_id' => 1, // Default CAVOUR
            'totale' => 0, // Calcolato dopo
        ]);

        // Crea ddt_dettaglio
        DdtDettaglio::create([
            'ddt_id' => $ddt->id,
            'articolo_id' => $articolo->id,
            'quantita' => $art->qta ?? 1,
            'prezzo_unitario' => $art->costo_unitario ?? 0,
            'totale' => ($art->costo_unitario ?? 0) * ($art->qta ?? 1),
        ]);

        $this->stats['ddt_migrati']++;
    }

    private function migraProdottiFiniti()
    {
        $this->info('🏭 [3/4] MIGRAZIONE PRODOTTI FINITI (categoria 9,22)');
        
        // Filtra solo prodotti finiti (categoria 9,22)
        $prodottiFinitiMssql = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->whereIn('id_magazzino', [9, 22])
            ->get();

        $this->line("Prodotti finiti trovati: {$prodottiFinitiMssql->count()}");
        $progressBar = $this->output->createProgressBar($prodottiFinitiMssql->count());
        $progressBar->start();

        foreach ($prodottiFinitiMssql as $pf) {
            if (!$this->dryRun) {
                // Crea prodotto finito con id_pf (non id!)
                $prodottoFinito = ProdottoFinito::create([
                    'id' => $pf->id_pf,
                    'codice' => $pf->id_magazzino . '-' . $pf->carico,
                    'descrizione' => $pf->descrizione,
                    'tipologia' => $pf->id_magazzino == 9 ? 'prodotto_finito' : 'semilavorato',
                    'magazzino_id' => $pf->id_magazzino,
                    'costo_totale' => $pf->valore_magazzino ?? 0,
                    'stato' => 'completato',
                    'data_completamento' => $pf->data_documento ?? now(),
                    'note' => "Migrato da MSSQL - ID articolo originale: {$pf->id}, ID PF: {$pf->id_pf}",
                ]);

                // NON creare giacenza per prodotto finito
                // I prodotti finiti non hanno giacenze separate
                // La loro giacenza è gestita tramite i componenti

                // Gestisci componenti
                $this->gestisciComponentiProdottoFinito($prodottoFinito, $pf);

                $this->stats['prodotti_finiti_migrati']++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['prodotti_finiti_migrati']} prodotti finiti");
    }

    private function gestisciComponentiProdottoFinito($prodottoFinito, $pf)
    {
        if ($this->dryRun) return;

        // Cerca componenti da mag_diba usando id_pf (non id!)
        $componentiMssql = DB::connection('mssql_prod')
            ->table('mag_diba')
            ->where('id_pf', $pf->id_pf)
            ->get();

        foreach ($componentiMssql as $comp) {
            // Cerca articolo componente
            $articoloComponente = Articolo::find($comp->id_articolo);
            
            if ($articoloComponente) {
                ComponenteProdotto::create([
                    'prodotto_finito_id' => $prodottoFinito->id,
                    'articolo_id' => $articoloComponente->id,
                    'quantita' => $comp->quantita ?? 1,
                    'costo_unitario' => $articoloComponente->prezzo_acquisto ?? 0,
                    'costo_totale' => ($articoloComponente->prezzo_acquisto ?? 0) * ($comp->quantita ?? 1),
                    'stato' => 'prelevato',
                    'prelevato_il' => now(),
                    'prelevato_da' => 1, // Utente di sistema
                ]);
                $this->stats['componenti_migrati']++;
            }
        }
    }

    private function verificaIntegrita()
    {
        $this->info('✅ [4/4] VERIFICA INTEGRITÀ');
        
        $articoli = Articolo::count();
        $prodottiFiniti = ProdottoFinito::count();
        $giacenze = Giacenza::count();
        $ddt = Ddt::count();
        $fornitori = Fornitore::count();

        $this->line("Articoli: {$articoli}");
        $this->line("Prodotti finiti: {$prodottiFiniti}");
        $this->line("Giacenze: {$giacenze}");
        $this->line("DDT: {$ddt}");
        $this->line("Fornitori: {$fornitori}");

        $this->info("✅ Verifica completata!");
    }

    private function displaySummary()
    {
        $this->newLine();
        $this->info('📊 RIEPILOGO MIGRAZIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("Utenti migrati: {$this->stats['utenti_migrati']}");
        $this->line("Fornitori migrati: {$this->stats['fornitori_migrati']}");
        $this->line("Articoli migrati: {$this->stats['articoli_migrati']}");
        $this->line("Prodotti finiti migrati: {$this->stats['prodotti_finiti_migrati']}");
        $this->line("Componenti migrati: {$this->stats['componenti_migrati']}");
        $this->line("Giacenze migrate: {$this->stats['giacenze_migrate']}");
        $this->line("DDT migrati: {$this->stats['ddt_migrati']}");
        $this->line("Fatture migrate: {$this->stats['fatture_migrate']}");
        $this->line("Movimentazioni create: {$this->stats['movimentazioni_create']}");
        
        if ($this->stats['errori'] > 0) {
            $this->warn("Errori: {$this->stats['errori']}");
        }

        $this->info('🎯 MIGRAZIONE COMPLETATA!');
    }
}
