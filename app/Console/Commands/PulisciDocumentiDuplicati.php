<?php

namespace App\Console\Commands;

use App\Models\Ddt;
use App\Models\Fattura;
use App\Models\DdtDettaglio;
use App\Models\FatturaDettaglio;
use App\Models\CaricoDettaglio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PulisciDocumentiDuplicati extends Command
{
    protected $signature = 'documenti:pulisci-duplicati 
                            {--tipo=all : Tipo di documento da pulire (ddt|fatture|all)}
                            {--dry-run : Mostra solo cosa verrebbe fatto senza applicare modifiche}';

    protected $description = 'Pulisce i documenti duplicati unendo i dettagli e mantenendo solo una testata';

    public function handle()
    {
        $tipo = $this->option('tipo');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 MODALITÀ DRY-RUN: Nessuna modifica verrà applicata al database');
            $this->newLine();
        }

        if ($tipo === 'all' || $tipo === 'ddt') {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📋 ANALISI DDT DUPLICATI');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->pulisciDdt($dryRun);
        }

        if ($tipo === 'all' || $tipo === 'fatture') {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📄 ANALISI FATTURE DUPLICATE');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->pulisciFatture($dryRun);
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('✅ Analisi completata. Rimuovi --dry-run per applicare le modifiche.');
        } else {
            $this->info('✅ Pulizia completata!');
        }
    }

    private function pulisciDdt($dryRun = false)
    {
        // Trova DDT duplicati (stesso numero, anno, fornitore)
        $duplicati = DB::select("
            SELECT 
                numero, 
                anno, 
                fornitore_id, 
                COUNT(*) as totale,
                GROUP_CONCAT(id ORDER BY created_at DESC) as ids,
                GROUP_CONCAT(created_at ORDER BY created_at DESC) as date_creazione
            FROM ddt
            GROUP BY numero, anno, fornitore_id
            HAVING COUNT(*) > 1
            ORDER BY totale DESC
        ");

        if (empty($duplicati)) {
            $this->info('✅ Nessun DDT duplicato trovato!');
            return;
        }

        $this->warn("⚠️  Trovati " . count($duplicati) . " gruppi di DDT duplicati");
        $this->newLine();

        $totaleDuplicatiDaEliminare = 0;
        $totaleDettagliDaSpostare = 0;

        foreach ($duplicati as $gruppo) {
            $ids = explode(',', $gruppo->ids);
            
            // PRIORITÀ: Mantieni il DDT con fornitore reale (non NULL e non "NON INSERITO")
            $ddtCandidati = Ddt::whereIn('id', $ids)
                ->with('fornitore')
                ->orderByRaw('CASE 
                    WHEN fornitore_id IS NOT NULL THEN 0 
                    ELSE 1 
                END')
                ->orderBy('created_at', 'DESC')
                ->get();
            
            $ddtDaMantenere = $ddtCandidati->first();
            $idDaMantenere = $ddtDaMantenere->id;
            $idsDaEliminare = array_diff($ids, [$idDaMantenere]);
            
            $fornitore = $ddtDaMantenere->fornitore->ragione_sociale ?? 'NON INSERITO';

            $this->line("📦 DDT n. <info>{$gruppo->numero}/{$gruppo->anno}</info> - Fornitore: <comment>{$fornitore}</comment>");
            $this->line("   Duplicati: <fg=yellow>{$gruppo->totale}</> | Da mantenere ID: <fg=green>{$idDaMantenere}</> | Da eliminare: <fg=red>" . implode(', ', $idsDaEliminare) . "</>");

            // Conta dettagli da spostare
            $dettagliDaSpostare = 0;
            foreach ($idsDaEliminare as $idDaEliminare) {
                $countDdt = DdtDettaglio::where('ddt_id', $idDaEliminare)->count();
                $countCarico = CaricoDettaglio::where('ddt_id', $idDaEliminare)->count();
                $dettagliDaSpostare += $countDdt + $countCarico;
            }

            if ($dettagliDaSpostare > 0) {
                $this->line("   📝 Dettagli da spostare: <fg=cyan>{$dettagliDaSpostare}</>");
            }

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    // Sposta tutti i dettagli al DDT da mantenere
                    foreach ($idsDaEliminare as $idDaEliminare) {
                        // Aggiorna ddt_dettagli (usando query raw per evitare updated_at)
                        DB::table('ddt_dettagli')
                            ->where('ddt_id', $idDaEliminare)
                            ->update(['ddt_id' => $idDaMantenere]);

                        // Aggiorna carico_dettagli
                        CaricoDettaglio::where('ddt_id', $idDaEliminare)
                            ->update(['ddt_id' => $idDaMantenere]);

                        // Elimina il DDT duplicato
                        Ddt::where('id', $idDaEliminare)->delete();
                    }

                    // Aggiorna conteggi nel DDT da mantenere
                    $numeroArticoli = DdtDettaglio::where('ddt_id', $idDaMantenere)->count();
                    $quantitaTotale = DdtDettaglio::where('ddt_id', $idDaMantenere)->sum('quantita');

                    Ddt::where('id', $idDaMantenere)->update([
                        'numero_articoli' => $numeroArticoli,
                        'quantita_totale' => $quantitaTotale,
                    ]);

                    DB::commit();
                    $this->line("   <fg=green>✓</> Unificato con successo!");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("   ✗ Errore: " . $e->getMessage());
                }
            }

            $totaleDuplicatiDaEliminare += count($idsDaEliminare);
            $totaleDettagliDaSpostare += $dettagliDaSpostare;
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 RIEPILOGO DDT:");
        $this->line("   • Gruppi duplicati trovati: <comment>" . count($duplicati) . "</comment>");
        $this->line("   • Documenti da eliminare: <fg=red>{$totaleDuplicatiDaEliminare}</>");
        $this->line("   • Dettagli da spostare: <fg=cyan>{$totaleDettagliDaSpostare}</>");
    }

    private function pulisciFatture($dryRun = false)
    {
        // Trova Fatture duplicate (stesso numero, anno, fornitore)
        $duplicati = DB::select("
            SELECT 
                numero, 
                anno, 
                fornitore_id, 
                COUNT(*) as totale,
                GROUP_CONCAT(id ORDER BY created_at DESC) as ids,
                GROUP_CONCAT(created_at ORDER BY created_at DESC) as date_creazione
            FROM fatture
            GROUP BY numero, anno, fornitore_id
            HAVING COUNT(*) > 1
            ORDER BY totale DESC
        ");

        if (empty($duplicati)) {
            $this->info('✅ Nessuna Fattura duplicata trovata!');
            return;
        }

        $this->warn("⚠️  Trovati " . count($duplicati) . " gruppi di Fatture duplicate");
        $this->newLine();

        $totaleDuplicatiDaEliminare = 0;
        $totaleDettagliDaSpostare = 0;

        foreach ($duplicati as $gruppo) {
            $ids = explode(',', $gruppo->ids);
            
            // PRIORITÀ: Mantieni la Fattura con fornitore reale (non NULL e non "NON INSERITO")
            $fattureCandidati = Fattura::whereIn('id', $ids)
                ->with('fornitore')
                ->orderByRaw('CASE 
                    WHEN fornitore_id IS NOT NULL THEN 0 
                    ELSE 1 
                END')
                ->orderBy('created_at', 'DESC')
                ->get();
            
            $fatturaDaMantenere = $fattureCandidati->first();
            $idDaMantenere = $fatturaDaMantenere->id;
            $idsDaEliminare = array_diff($ids, [$idDaMantenere]);
            
            $fornitore = $fatturaDaMantenere->fornitore->ragione_sociale ?? 'NON INSERITO';

            $this->line("🧾 Fattura n. <info>{$gruppo->numero}/{$gruppo->anno}</info> - Fornitore: <comment>{$fornitore}</comment>");
            $this->line("   Duplicati: <fg=yellow>{$gruppo->totale}</> | Da mantenere ID: <fg=green>{$idDaMantenere}</> | Da eliminare: <fg=red>" . implode(', ', $idsDaEliminare) . "</>");

            // Conta dettagli da spostare
            $dettagliDaSpostare = 0;
            foreach ($idsDaEliminare as $idDaEliminare) {
                $countFattura = FatturaDettaglio::where('fattura_id', $idDaEliminare)->count();
                $countCarico = CaricoDettaglio::where('fattura_id', $idDaEliminare)->count();
                $dettagliDaSpostare += $countFattura + $countCarico;
            }

            if ($dettagliDaSpostare > 0) {
                $this->line("   📝 Dettagli da spostare: <fg=cyan>{$dettagliDaSpostare}</>");
            }

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    // Sposta tutti i dettagli alla Fattura da mantenere
                    foreach ($idsDaEliminare as $idDaEliminare) {
                        // Aggiorna fatture_dettagli (usando query raw per evitare updated_at)
                        DB::table('fatture_dettagli')
                            ->where('fattura_id', $idDaEliminare)
                            ->update(['fattura_id' => $idDaMantenere]);

                        // Aggiorna carico_dettagli
                        CaricoDettaglio::where('fattura_id', $idDaEliminare)
                            ->update(['fattura_id' => $idDaMantenere]);

                        // Elimina la Fattura duplicata
                        Fattura::where('id', $idDaEliminare)->delete();
                    }

                    // Aggiorna conteggi nella Fattura da mantenere
                    $numeroArticoli = FatturaDettaglio::where('fattura_id', $idDaMantenere)->count();
                    $quantitaTotale = FatturaDettaglio::where('fattura_id', $idDaMantenere)->sum('quantita');

                    Fattura::where('id', $idDaMantenere)->update([
                        'numero_articoli' => $numeroArticoli,
                        'quantita_totale' => $quantitaTotale,
                    ]);

                    DB::commit();
                    $this->line("   <fg=green>✓</> Unificato con successo!");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("   ✗ Errore: " . $e->getMessage());
                }
            }

            $totaleDuplicatiDaEliminare += count($idsDaEliminare);
            $totaleDettagliDaSpostare += $dettagliDaSpostare;
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 RIEPILOGO FATTURE:");
        $this->line("   • Gruppi duplicati trovati: <comment>" . count($duplicati) . "</comment>");
        $this->line("   • Documenti da eliminare: <fg=red>{$totaleDuplicatiDaEliminare}</>");
        $this->line("   • Dettagli da spostare: <fg=cyan>{$totaleDettagliDaSpostare}</>");
    }
}
