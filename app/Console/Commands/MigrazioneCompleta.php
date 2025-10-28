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

class MigrazioneCompleta extends Command
{
    protected $signature = 'migra:completa {--dry-run : Esegui senza modificare il database}';
    protected $description = 'Migrazione completa: TUTTI i dati dalla vista elenco_articoli_magazzino';

    private $stats = [
        'fornitori_creati' => 0,
        'articoli_migrati' => 0,
        'prodotti_finiti_migrati' => 0,
        'componenti_migrati' => 0,
        'giacenze_migrate' => 0,
        'ddt_migrati' => 0,
        'vetrine_migrate' => 0,
        'articoli_vetrine_migrate' => 0,
    ];

    private $fornitoriMap = []; // Mappa: nome fornitore => ID

    public function handle()
    {
        $this->info('🚀 MIGRAZIONE COMPLETA DALLA VISTA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('⚠️ MODALITÀ DRY-RUN: Nessuna modifica al database');
        }

        try {
            // 0. Migra users
            $this->migraUsers();
            
            // 1. Crea sedi da mag_magazzini
            $this->creaSediDaMagazzini();
            
            // 2. Crea categorie merceologiche
            $this->creaCategorie();
            
            // 3. Crea fornitori dalla vista (deduplicati)
            $this->creaFornitoriDaVista();
            
            // 4. Migra articoli normali (categoria != 9,22)
            $this->migraArticoliNormali();
            
            // 5. Migra prodotti finiti (categoria 9,22)
            $this->migraProdottiFiniti();
            
            // 6. Migra vetrine storiche
            $this->migraVetrine();
            
            // 7. Riepilogo
            $this->displaySummary();

        } catch (\Exception $e) {
            $this->error("❌ Errore: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function migraUsers()
    {
        $this->info('👥 [0/6] MIGRAZIONE USERS');
        
        if ($this->option('dry-run')) return;

        // Verifica se users esiste già
        $usersEsistenti = DB::table('users')->count();
        if ($usersEsistenti > 0) {
            $this->info("✅ Users già esistenti ({$usersEsistenti}), salto migrazione");
            return;
        }

        $users = DB::connection('mssql_prod')
            ->table('users')
            ->get();

        foreach ($users as $user) {
            DB::table('users')->insertOrIgnore([
                'id' => $user->id,
                'name' => $user->name ?? $user->username ?? 'User ' . $user->id,
                'email' => $user->email ?? 'user' . $user->id . '@athena.local',
                'password' => $user->password ?? bcrypt('password'),
                'email_verified_at' => now(),
                'created_at' => $user->created_at ?? now(),
                'updated_at' => $user->updated_at ?? now(),
            ]);
        }

        $this->info("✅ Migrati {$users->count()} users");
    }

    private function creaSediDaMagazzini()
    {
        $this->info('🏢 [1/6] CREAZIONE SEDI DA MAG_MAGAZZINI');
        
        if ($this->option('dry-run')) return;

        $magazzini = DB::connection('mssql_prod')
            ->table('mag_magazzini')
            ->get();

        // Le sedi sono già create dalla migrazione, salto se esistono
        $sediEsistenti = DB::table('sedi')->count();
        if ($sediEsistenti > 0) {
            $this->info("✅ Sedi già esistenti ({$sediEsistenti}), salto creazione");
            
            // Crea solo ubicazione
            DB::table('ubicazioni')->insertOrIgnore([
                'id' => 1,
                'sede_id' => 1,
                'scaffale' => 'Default',
                'ripiano' => '1',
                'box' => 'A',
                'posizione' => 'Default',
                'codice' => 'DEF-001',
                'descrizione' => 'Ubicazione di default',
                'capacita_massima' => 1000,
                'articoli_presenti' => 0,
                'attivo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }
        
        foreach ($magazzini as $mag) {
            DB::table('sedi')->insertOrIgnore([
                'id' => $mag->id,
                'codice' => $mag->codice ?? 'MAG' . $mag->id,
                'nome' => $mag->nome,
                'indirizzo' => $mag->indirizzo ?? null,
                'citta' => $mag->citta ?? 'Torino',
                'provincia' => $mag->provincia ?? 'TO',
                'cap' => $mag->cap ?? '10123',
                'tipo' => 'negozio',
                'attivo' => 1,
                'note' => 'Migrato da mag_magazzini',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crea ubicazione di default
        DB::table('ubicazioni')->insertOrIgnore([
            'id' => 1,
            'sede_id' => 1,
            'scaffale' => 'Default',
            'ripiano' => '1',
            'box' => 'A',
            'posizione' => 'Default',
            'codice' => 'DEF-001',
            'descrizione' => 'Ubicazione di default',
            'capacita_massima' => 1000,
            'articoli_presenti' => 0,
            'attivo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Create {$magazzini->count()} sedi e 1 ubicazione");
    }

    private function creaCategorie()
    {
        $this->info('📁 [2/6] CREAZIONE CATEGORIE MERCEOLOGICHE');
        
        if ($this->option('dry-run')) return;

        $categorie = DB::connection('mssql_prod')
            ->table('mag_magazzini')
            ->get();

        foreach ($categorie as $cat) {
            DB::table('categorie_merceologiche')->insertOrIgnore([
                'id' => $cat->id,
                'sede_id' => 1,
                'codice' => 'MAG' . $cat->id,
                'nome' => $cat->nome,
                'citta' => 'Torino',
                'provincia' => 'TO',
                'cap' => '10123',
                'tipo' => 'principale',
                'attivo' => 1,
                'note' => 'Migrato da MSSQL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("✅ Create {$categorie->count()} categorie merceologiche");
    }

    private function creaFornitoriDaVista()
    {
        $this->info('🏢 [3/6] CREAZIONE FORNITORI DALLA VISTA');
        
        // Prendi tutti i fornitori unici dalla vista
        $fornitoriUnici = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->select('fornitore')
            ->whereNotNull('fornitore')
            ->where('fornitore', '!=', '')
            ->distinct()
            ->get();

        $this->line("Fornitori unici trovati: {$fornitoriUnici->count()}");
        $progressBar = $this->output->createProgressBar($fornitoriUnici->count());
        $progressBar->start();

        foreach ($fornitoriUnici as $forn) {
            $nomeFornitore = $forn->fornitore;
            
            if (!$this->option('dry-run')) {
                // Verifica se esiste già
                $fornitoreEsistente = Fornitore::where('ragione_sociale', $nomeFornitore)->first();
                
                if (!$fornitoreEsistente) {
                    $nuovoFornitore = Fornitore::create([
                        'ragione_sociale' => $nomeFornitore,
                        'attivo' => 1,
                    ]);
                    $this->fornitoriMap[$nomeFornitore] = $nuovoFornitore->id;
                    $this->stats['fornitori_creati']++;
                } else {
                    $this->fornitoriMap[$nomeFornitore] = $fornitoreEsistente->id;
                }
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Creati {$this->stats['fornitori_creati']} fornitori");
    }

    private function migraArticoliNormali()
    {
        $this->info('📦 [4/6] MIGRAZIONE ARTICOLI NORMALI (categoria != 9,22)');
        
        // Prendi TUTTI gli articoli dalla vista (escludi 9,22)
        $articoli = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->whereNotIn('id_magazzino', [9, 22])
            ->orderBy('id')
            ->get();

        $this->line("Articoli trovati: {$articoli->count()}");
        
        // Gestisci duplicati con suffissi
        $articoliConSuffissi = $this->gestisciDuplicati($articoli);
        
        $progressBar = $this->output->createProgressBar($articoliConSuffissi->count());
        $progressBar->start();

        foreach ($articoliConSuffissi as $art) {
            if (!$this->option('dry-run')) {
                // Crea articolo con ID ORIGINALE usando insertOrIgnore per evitare duplicati
                DB::table('articoli')->insertOrIgnore([
                    'id' => $art->id, // ID ORIGINALE preservato!
                    'codice' => $art->codice_unico,
                    'descrizione' => $art->descrizione,
                    'categoria_merceologica_id' => $art->id_magazzino,
                    'sede_id' => $this->mapUbicazioneToSede($art->ubicazione_magazzino ?? 1), // Mapping corretto ubicazione → sede
                    'materiale' => $art->materiale ?? null,
                    'caratura' => $art->carati ?? null,
                    'prezzo_acquisto' => $art->costo_unitario ?? 0,
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
                
                $articolo = Articolo::find($art->id);

                // Crea giacenza solo se articolo esiste e giacenza non esiste
                if ($articolo && !DB::table('giacenze')->where('articolo_id', $articolo->id)->exists()) {
                    Giacenza::create([
                        'articolo_id' => $articolo->id,
                        'sede_id' => $this->mapUbicazioneToSede($art->ubicazione_magazzino ?? 1), // Mapping corretto ubicazione → sede
                        'quantita' => $art->qta ?? 1,
                        'quantita_residua' => $art->qta_residua ?? 1,
                        'costo_unitario' => $art->costo_unitario ?? 0,
                        'ubicazione_id' => 1,
                    ]);
                }

                // Gestisci DDT se numero_documento != '0' e articolo esiste
                if ($articolo) {
                    $this->gestisciDdt($articolo, $art);
                }

                $this->stats['articoli_migrati']++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['articoli_migrati']} articoli");
    }

    private function gestisciDdt($articolo, $art)
    {
        // Solo se numero_documento != '0'
        if (!$art->numero_documento || $art->numero_documento == '0') {
            return;
        }

        // Trova fornitore
        $fornitoreId = null;
        if (isset($art->fornitore) && $art->fornitore) {
            $fornitoreId = $this->fornitoriMap[$art->fornitore] ?? null;
        }

        // Se fornitore non trovato, crea fornitore "Sconosciuto"
        if (!$fornitoreId) {
            $fornitoreId = $this->creaFornitoreDefault();
        }

        // Trova o crea DDT
        $ddt = Ddt::where('numero', $art->numero_documento)
            ->where('data_documento', $art->data_documento)
            ->first();

        if (!$ddt) {
            // Usa insert diretto per preservare ID originale
            DB::table('ddt')->insert([
                'id' => $art->id_testata, // ID ORIGINALE della testata!
                'numero' => $art->numero_documento,
                'data_documento' => $art->data_documento,
                'anno' => $art->data_documento ? date('Y', strtotime($art->data_documento)) : date('Y'),
                'fornitore_id' => $fornitoreId,
                'sede_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ddt = Ddt::find($art->id_testata);
            $this->stats['ddt_migrati']++;
        }

        // Crea dettaglio DDT
        DdtDettaglio::create([
            'ddt_id' => $ddt->id,
            'articolo_id' => $articolo->id,
            'quantita' => $art->qta ?? 1,
            'prezzo_unitario' => $art->costo_unitario ?? 0,
            'totale' => ($art->costo_unitario ?? 0) * ($art->qta ?? 1),
        ]);
    }

    private function creaFornitoreDefault()
    {
        static $fornitoreDefaultId = null;
        
        if (!$fornitoreDefaultId) {
            $fornitore = Fornitore::firstOrCreate(
                ['ragione_sociale' => 'FORNITORE SCONOSCIUTO'],
                ['attivo' => 1]
            );
            $fornitoreDefaultId = $fornitore->id;
        }
        
        return $fornitoreDefaultId;
    }

    private function migraProdottiFiniti()
    {
        $this->info('🏭 [5/6] MIGRAZIONE PRODOTTI FINITI (categoria 9,22)');
        
        // Prendi prodotti finiti dalla vista
        $prodottiFiniti = DB::connection('mssql_prod')
            ->table('elenco_articoli_magazzino')
            ->whereIn('id_magazzino', [9, 22])
            ->orderBy('id_pf')
            ->get();

        $this->line("Prodotti finiti trovati: {$prodottiFiniti->count()}");
        $progressBar = $this->output->createProgressBar($prodottiFiniti->count());
        $progressBar->start();

        foreach ($prodottiFiniti as $pf) {
            if (!$this->option('dry-run')) {
                // Crea prodotto finito con ID ORIGINALE usando insert diretto
                DB::table('prodotti_finiti')->insert([
                    'id' => $pf->id_pf, // ID ORIGINALE del PF!
                    'codice' => $pf->id_magazzino . '-' . $pf->carico,
                    'descrizione' => $pf->descrizione,
                    'tipologia' => $pf->id_magazzino == 9 ? 'prodotto_finito' : 'semilavorato',
                    'magazzino_id' => $pf->id_magazzino,
                    'costo_totale' => $pf->valore_magazzino ?? 0,
                    'stato' => 'completato',
                    'data_completamento' => $pf->data_documento ?? now(),
                    'note' => "Migrato da MSSQL",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $prodottoFinito = ProdottoFinito::find($pf->id_pf);

                // Gestisci componenti
                $this->gestisciComponenti($prodottoFinito, $pf);

                $this->stats['prodotti_finiti_migrati']++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Migrati {$this->stats['prodotti_finiti_migrati']} prodotti finiti");
    }

    private function gestisciComponenti($prodottoFinito, $pf)
    {
        // Cerca componenti da mag_diba
        $componenti = DB::connection('mssql_prod')
            ->table('mag_diba')
            ->where('id_pf', $pf->id_pf)
            ->get();

        foreach ($componenti as $comp) {
            // Cerca articolo componente
            $articoloComponente = Articolo::find($comp->id_articolo);
            
            if ($articoloComponente) {
                // Verifica se componente già esiste (duplicati in mag_diba)
                $esistente = ComponenteProdotto::where('prodotto_finito_id', $prodottoFinito->id)
                    ->where('articolo_id', $articoloComponente->id)
                    ->first();
                
                if (!$esistente) {
                    ComponenteProdotto::create([
                        'prodotto_finito_id' => $prodottoFinito->id,
                        'articolo_id' => $articoloComponente->id,
                        'quantita' => 1, // mag_diba non ha quantita, usa 1
                        'costo_unitario' => $articoloComponente->prezzo_acquisto ?? 0,
                        'costo_totale' => $articoloComponente->prezzo_acquisto ?? 0,
                        'stato' => 'prelevato',
                        'prelevato_il' => now(),
                        'prelevato_da' => 1,
                    ]);
                    $this->stats['componenti_migrati']++;
                }
            }
        }
    }

    private function migraVetrine()
    {
        $this->info('🏪 [6/7] MIGRAZIONE VETRINE STORICHE');
        
        if ($this->option('dry-run')) {
            $this->info("⚠️ Modalità dry-run: salto migrazione vetrine");
            return;
        }

        // Chiama il comando di migrazione vetrine
        $exitCode = $this->call('migra:vetrine');
        
        if ($exitCode === 0) {
            // Conta le vetrine migrate per le statistiche
            $this->stats['vetrine_migrate'] = DB::table('vetrine')->count();
            $this->stats['articoli_vetrine_migrate'] = DB::table('articoli_vetrine')->count();
            
            $this->info("✅ Migrazione vetrine completata");
        } else {
            $this->warn("⚠️ Problemi durante la migrazione vetrine (exit code: {$exitCode})");
        }
    }

    private function displaySummary()
    {
        $this->newLine();
        $this->info('📊 RIEPILOGO MIGRAZIONE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("Fornitori creati: {$this->stats['fornitori_creati']}");
        $this->line("Articoli migrati: {$this->stats['articoli_migrati']}");
        $this->line("Prodotti finiti migrati: {$this->stats['prodotti_finiti_migrati']}");
        $this->line("Componenti migrati: {$this->stats['componenti_migrati']}");
        $this->line("DDT migrati: {$this->stats['ddt_migrati']}");
        $this->line("Vetrine migrate: {$this->stats['vetrine_migrate']}");
        $this->line("Articoli in vetrina migrati: {$this->stats['articoli_vetrine_migrate']}");
        $this->info('🎯 MIGRAZIONE COMPLETATA!');
    }

    private function gestisciDuplicati($articoli)
    {
        return collect($articoli)
            ->groupBy(function($art) {
                return $art->id_magazzino . '-' . $art->carico;
            })
            ->flatMap(function($group, $codiceBase) {
                if ($group->count() == 1) {
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
    
    /**
     * Mappa ubicazione_magazzino a sede_id corretto
     */
    private function mapUbicazioneToSede($ubicazione)
    {
        $mapping = [
            0 => 1, // Default/NULL → CAVOUR
            1 => 1, // CAVOUR
            2 => 3, // MONASTERO
            3 => 4, // MAZZINI
            4 => 2, // JOLLY
            5 => 5, // ROMA
        ];
        
        return $mapping[$ubicazione] ?? 1; // Default a CAVOUR
    }
}

