<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PulisciDatabase extends Command
{
    protected $signature = 'db:pulisci {--confirm : Conferma la pulizia}';
    protected $description = 'Pulisce il database per preparare la migrazione completa';

    public function handle()
    {
        if (!$this->option('confirm')) {
            $this->error('❌ ATTENZIONE: Questo comando eliminerà TUTTI i dati!');
            $this->warn('⚠️  Per procedere, esegui: php artisan db:pulisci --confirm');
            return 1;
        }
        
        $this->info('🧹 PULIZIA DATABASE COMPLETA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        try {
            DB::beginTransaction();
            
            // 1. Pulisci tabelle dipendenti (in ordine per FK)
            $this->info('📊 Pulizia tabelle dipendenti...');
            
            // Componenti e PF
            $componenti = DB::table('componenti_prodotto')->delete();
            $this->info("✅ Eliminati {$componenti} componenti");
            
            $pf = DB::table('prodotti_finiti')->delete();
            $this->info("✅ Eliminati {$pf} prodotti finiti");
            
            // Dettagli documenti
            $ddtDettagli = DB::table('ddt_dettagli')->delete();
            $this->info("✅ Eliminati {$ddtDettagli} dettagli DDT");
            
            $fattureDettagli = DB::table('fatture_dettagli')->delete();
            $this->info("✅ Eliminati {$fattureDettagli} dettagli fatture");
            
            $caricoDettagli = DB::table('carico_dettagli')->delete();
            $this->info("✅ Eliminati {$caricoDettagli} dettagli carico");
            
            // Giacenze
            $giacenze = DB::table('giacenze')->delete();
            $this->info("✅ Eliminate {$giacenze} giacenze");
            
            // 2. Pulisci tabelle principali
            $this->info('📦 Pulizia tabelle principali...');
            
            // Documenti
            $ddt = DB::table('ddt')->delete();
            $this->info("✅ Eliminati {$ddt} DDT");
            
            $fatture = DB::table('fatture')->delete();
            $this->info("✅ Eliminate {$fatture} fatture");
            
            // Articoli
            $articoli = DB::table('articoli')->delete();
            $this->info("✅ Eliminati {$articoli} articoli");
            
            // 3. Pulisci tabelle di supporto
            $this->info('🔧 Pulizia tabelle di supporto...');
            
            $articoliVetrine = DB::table('articoli_vetrine')->delete();
            $this->info("✅ Eliminate {$articoliVetrine} relazioni vetrine");
            
            $ubicazioni = DB::table('ubicazioni')->delete();
            $this->info("✅ Eliminate {$ubicazioni} ubicazioni");
            
            // 4. Reset auto-increment
            $this->info('🔄 Reset auto-increment...');
            
            DB::statement('ALTER TABLE articoli AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE ddt AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE fatture AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE giacenze AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE prodotti_finiti AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE componenti_prodotto AUTO_INCREMENT = 1');
            
            $this->info('✅ Auto-increment resettati');
            
            DB::commit();
            
            $this->newLine();
            $this->info('✅ PULIZIA COMPLETATA!');
            $this->info('Il database è ora pronto per la migrazione completa.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Errore durante la pulizia: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}




