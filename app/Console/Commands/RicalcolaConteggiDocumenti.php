<?php

namespace App\Console\Commands;

use App\Models\Ddt;
use App\Models\Fattura;
use App\Models\DdtDettaglio;
use App\Models\FatturaDettaglio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RicalcolaConteggiDocumenti extends Command
{
    protected $signature = 'documenti:ricalcola-conteggi 
                            {--tipo=all : Tipo di documento (ddt|fatture|all)}';

    protected $description = 'Ricalcola numero_articoli e quantita_totale per tutti i documenti';

    public function handle()
    {
        $tipo = $this->option('tipo');

        if ($tipo === 'all' || $tipo === 'ddt') {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📦 RICALCOLO CONTEGGI DDT');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->ricalcolaDdt();
        }

        if ($tipo === 'all' || $tipo === 'fatture') {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🧾 RICALCOLO CONTEGGI FATTURE');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->ricalcolaFatture();
        }

        $this->newLine();
        $this->info('✅ Ricalcolo completato!');
    }

    private function ricalcolaDdt()
    {
        $ddt = Ddt::all();
        $this->info("Trovati {$ddt->count()} DDT da processare...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($ddt->count());
        $progressBar->start();

        $aggiornati = 0;

        foreach ($ddt as $documento) {
            // Conta articoli unici dai dettagli
            $numeroArticoli = DdtDettaglio::where('ddt_id', $documento->id)
                ->distinct('articolo_id')
                ->count('articolo_id');
            
            // Somma quantità totale
            $quantitaTotale = DdtDettaglio::where('ddt_id', $documento->id)
                ->sum('quantita');

            // Aggiorna solo se i valori sono diversi
            if ($documento->numero_articoli != $numeroArticoli || $documento->quantita_totale != $quantitaTotale) {
                DB::table('ddt')
                    ->where('id', $documento->id)
                    ->update([
                        'numero_articoli' => $numeroArticoli,
                        'quantita_totale' => $quantitaTotale,
                    ]);
                $aggiornati++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Aggiornati {$aggiornati} DDT su {$ddt->count()}");
    }

    private function ricalcolaFatture()
    {
        $fatture = Fattura::all();
        $this->info("Trovate {$fatture->count()} Fatture da processare...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($fatture->count());
        $progressBar->start();

        $aggiornati = 0;

        foreach ($fatture as $documento) {
            // Conta articoli unici dai dettagli
            $numeroArticoli = FatturaDettaglio::where('fattura_id', $documento->id)
                ->distinct('articolo_id')
                ->count('articolo_id');
            
            // Somma quantità totale
            $quantitaTotale = FatturaDettaglio::where('fattura_id', $documento->id)
                ->sum('quantita');

            // Aggiorna solo se i valori sono diversi
            if ($documento->numero_articoli != $numeroArticoli || $documento->quantita_totale != $quantitaTotale) {
                DB::table('fatture')
                    ->where('id', $documento->id)
                    ->update([
                        'numero_articoli' => $numeroArticoli,
                        'quantita_totale' => $quantitaTotale,
                    ]);
                $aggiornati++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Aggiornate {$aggiornati} Fatture su {$fatture->count()}");
    }
}
