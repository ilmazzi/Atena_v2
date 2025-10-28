<?php

namespace App\Console\Commands;

use App\Models\Articolo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InvestigaArticoliOrfani extends Command
{
    protected $signature = 'articoli:investiga-orfani {--limit=20 : Numero di esempi da mostrare}';
    protected $description = 'Investiga gli articoli orfani per capire la struttura dei dati';

    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔍 INVESTIGAZIONE ARTICOLI ORFANI');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Trova articoli orfani
        $articoliIdInDettagli = DB::table('ddt_dettagli')
            ->distinct()
            ->pluck('articolo_id')
            ->toArray();

        $articoliOrfani = Articolo::whereNotNull('numero_documento_carico')
            ->whereNotIn('id', $articoliIdInDettagli)
            ->with('ddtDettaglio.ddt.fornitore')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $this->info("📋 Mostrando {$limit} esempi dei {$articoliOrfani->count()} articoli orfani più recenti:");
        $this->newLine();

        foreach ($articoliOrfani as $art) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("ID: <comment>{$art->id}</comment> | Codice: <info>{$art->codice}</info>");
            $this->line("numero_documento_carico: <fg=yellow>\"{$art->numero_documento_carico}\"</>");
            $this->line("Fornitore ID: {$art->fornitore_id} - " . ($art->fornitore->ragione_sociale ?? 'N/A'));
            $this->line("Descrizione: " . substr($art->descrizione, 0, 60));
            $this->line("Data creazione: {$art->created_at}");
            
            // Cerca DDT simili
            $ddtSimili = DB::table('ddt')
                ->where('numero', 'like', '%' . trim($art->numero_documento_carico) . '%')
                ->limit(3)
                ->get(['id', 'numero', 'anno', 'fornitore_id', 'data_documento']);
            
            if ($ddtSimili->isNotEmpty()) {
                $this->line("   🔗 <fg=green>DDT Simili trovati:</>");
                foreach ($ddtSimili as $ddt) {
                    $this->line("      - ID: {$ddt->id} | Numero: \"{$ddt->numero}\" | Anno: {$ddt->anno} | Fornitore: {$ddt->fornitore_id}");
                }
            } else {
                $this->line("   ❌ <fg=red>Nessun DDT simile trovato</>");
            }
        }

        $this->newLine();
        
        // Analisi valori numero_documento_carico
        $this->info('📊 ANALISI VALORI numero_documento_carico (orfani):');
        $this->newLine();
        
        $valoriUnici = Articolo::whereNotNull('numero_documento_carico')
            ->whereNotIn('id', $articoliIdInDettagli)
            ->select('numero_documento_carico', DB::raw('COUNT(*) as count'))
            ->groupBy('numero_documento_carico')
            ->orderByDesc('count')
            ->limit(15)
            ->get();
        
        $this->table(
            ['Valore numero_documento_carico', 'Articoli'],
            $valoriUnici->map(fn($v) => [
                substr($v->numero_documento_carico, 0, 50),
                $v->count
            ])
        );

        $this->newLine();
        $this->info('✅ Investigazione completata!');
    }
}
