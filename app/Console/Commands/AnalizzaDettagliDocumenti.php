<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalizzaDettagliDocumenti extends Command
{
    protected $signature = 'documenti:analizza-dettagli';
    protected $description = 'Analizza il totale di articoli e pezzi nei dettagli documenti';

    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 ANALISI ARTICOLI NEI DETTAGLI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // DDT Dettagli
        $this->info('📦 <fg=cyan>DDT DETTAGLI</>');
        $ddtRigheTotali = DB::table('ddt_dettagli')->count();
        $ddtArticoliDistinti = DB::table('ddt_dettagli')->distinct('articolo_id')->count('articolo_id');
        $ddtPezziTotali = DB::table('ddt_dettagli')->sum('quantita');
        
        $this->line("   • Righe totali (articoli nelle righe): <comment>{$ddtRigheTotali}</comment>");
        $this->line("   • Articoli distinti (ID univoci): <info>{$ddtArticoliDistinti}</info>");
        $this->line("   • Pezzi totali (somma quantità): <fg=green>{$ddtPezziTotali}</>");
        $this->newLine();

        // Fatture Dettagli
        $this->info('🧾 <fg=cyan>FATTURE DETTAGLI</>');
        $fattureRigheTotali = DB::table('fatture_dettagli')->count();
        $fattureArticoliDistinti = DB::table('fatture_dettagli')->distinct('articolo_id')->count('articolo_id');
        $fatturePezziTotali = DB::table('fatture_dettagli')->sum('quantita');
        
        $this->line("   • Righe totali (articoli nelle righe): <comment>{$fattureRigheTotali}</comment>");
        $this->line("   • Articoli distinti (ID univoci): <info>{$fattureArticoliDistinti}</info>");
        $this->line("   • Pezzi totali (somma quantità): <fg=green>{$fatturePezziTotali}</>");
        $this->newLine();

        // Carico Dettagli
        $this->info('📋 <fg=cyan>CARICO DETTAGLI</>');
        $caricoRigheTotali = DB::table('carico_dettagli')->count();
        $caricoArticoliDistinti = DB::table('carico_dettagli')->distinct('articolo_id')->count('articolo_id');
        $caricoPezziTotali = DB::table('carico_dettagli')->sum('quantita');
        
        $this->line("   • Righe totali: <comment>{$caricoRigheTotali}</comment>");
        $this->line("   • Articoli distinti: <info>{$caricoArticoliDistinti}</info>");
        $this->line("   • Pezzi totali: <fg=green>{$caricoPezziTotali}</>");
        $this->newLine();

        // Totali Combinati
        $this->info('🎯 <fg=yellow>TOTALI COMBINATI (DDT + FATTURE)</>');
        $totaleRighe = $ddtRigheTotali + $fattureRigheTotali;
        $totalePezzi = $ddtPezziTotali + $fatturePezziTotali;
        
        $this->line("   • <fg=yellow>Righe totali (articoli):</> <comment>{$totaleRighe}</comment>");
        $this->line("   • <fg=yellow>Pezzi totali:</> <fg=green>{$totalePezzi}</>");
        $this->newLine();

        // Verifica coerenza
        $this->info('🔍 <fg=magenta>VERIFICA COERENZA</>');
        $ddtConDettagli = DB::table('ddt')->where('numero_articoli', '>', 0)->count();
        $ddtSenzaDettagli = DB::table('ddt')->where('numero_articoli', '=', 0)->orWhereNull('numero_articoli')->count();
        
        $this->line("   • DDT con articoli: <fg=green>{$ddtConDettagli}</>");
        $this->line("   • DDT senza articoli: <fg=red>{$ddtSenzaDettagli}</>");
        $this->newLine();

        $this->info('✅ Analisi completata!');
    }
}
