<?php

namespace App\Console\Commands;

use App\Models\Articolo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalizzaDiscrepanzaArticoli extends Command
{
    protected $signature = 'articoli:analizza-discrepanza';
    protected $description = 'Analizza la discrepanza tra articoli totali e articoli nei dettagli';

    public function handle()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔍 ANALISI DISCREPANZA ARTICOLI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // 1. Articoli totali
        $articoliTotali = Articolo::count();
        $this->info("📦 ARTICOLI TOTALI NEL SISTEMA: <fg=yellow>{$articoliTotali}</>");
        $this->newLine();

        // 2. Articoli nei dettagli
        $this->info('📋 ARTICOLI NEI DETTAGLI:');
        
        $articoliInDdtDettagli = DB::table('ddt_dettagli')
            ->distinct('articolo_id')
            ->count('articolo_id');
        $this->line("   • In ddt_dettagli: <comment>{$articoliInDdtDettagli}</comment>");
        
        $articoliInFattureDettagli = DB::table('fatture_dettagli')
            ->distinct('articolo_id')
            ->count('articolo_id');
        $this->line("   • In fatture_dettagli: <comment>{$articoliInFattureDettagli}</comment>");
        
        $articoliInCaricoDettagli = DB::table('carico_dettagli')
            ->distinct('articolo_id')
            ->count('articolo_id');
        $this->line("   • In carico_dettagli: <comment>{$articoliInCaricoDettagli}</comment>");
        
        $this->newLine();

        // 3. Articoli SENZA dettagli
        $articoliIdInDettagli = DB::table('ddt_dettagli')
            ->distinct()
            ->pluck('articolo_id')
            ->toArray();
        
        $articoliSenzaDettagli = Articolo::whereNotIn('id', $articoliIdInDettagli)->count();
        $this->warn("⚠️  ARTICOLI SENZA DETTAGLI DDT: {$articoliSenzaDettagli}");
        $this->newLine();

        // 4. Analisi per campo numero_documento_carico
        $this->info('📄 ANALISI PER CAMPO numero_documento_carico:');
        
        $articoliConNumeroDocumento = Articolo::whereNotNull('numero_documento_carico')->count();
        $this->line("   • Articoli con numero_documento_carico: <comment>{$articoliConNumeroDocumento}</comment>");
        
        $articoliSenzaNumeroDocumento = Articolo::whereNull('numero_documento_carico')->count();
        $this->line("   • Articoli senza numero_documento_carico: <comment>{$articoliSenzaNumeroDocumento}</comment>");
        $this->newLine();

        // 5. Top numeri documento
        $topDocumenti = Articolo::select('numero_documento_carico', DB::raw('COUNT(*) as count'))
            ->whereNotNull('numero_documento_carico')
            ->groupBy('numero_documento_carico')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        
        $this->info('🔝 TOP 10 DOCUMENTI CON PIÙ ARTICOLI:');
        foreach ($topDocumenti as $doc) {
            $this->line("   • {$doc->numero_documento_carico}: <fg=green>{$doc->count}</> articoli");
        }
        $this->newLine();

        // 6. Verifica articoli nei DDT
        $this->info('🔗 ARTICOLI COLLEGATI AI DDT:');
        
        // Articoli che hanno numero_documento_carico ma NON sono in ddt_dettagli
        $articoliOrfani = Articolo::whereNotNull('numero_documento_carico')
            ->whereNotIn('id', $articoliIdInDettagli)
            ->count();
        
        $this->warn("   ⚠️  Articoli con numero_documento_carico ma SENZA riga in ddt_dettagli: <fg=red>{$articoliOrfani}</>");
        
        if ($articoliOrfani > 0) {
            $this->line("   💡 Questi articoli potrebbero essere stati creati ma mai collegati ai dettagli DDT");
        }
        $this->newLine();

        // 7. Riepilogo discrepanza
        $discrepanza = $articoliTotali - $articoliInDdtDettagli;
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RIEPILOGO DISCREPANZA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("   Articoli totali: <fg=yellow>{$articoliTotali}</>");
        $this->line("   Articoli in ddt_dettagli (distinti): <comment>{$articoliInDdtDettagli}</comment>");
        $this->line("   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("   ⚠️  DISCREPANZA: <fg=red>{$discrepanza}</> articoli");
        $this->newLine();
        
        if ($articoliOrfani > 0) {
            $this->warn("💡 Probabilmente questi {$discrepanza} articoli sono stati:");
            $this->line("   - Creati manualmente senza documento");
            $this->line("   - Creati ma il salvataggio del dettaglio DDT è fallito");
            $this->line("   - Importati da sistemi precedenti");
            $this->line("   - Creati da processi OCR incompleti");
        }
        
        $this->newLine();
        $this->info('✅ Analisi completata!');
    }
}
