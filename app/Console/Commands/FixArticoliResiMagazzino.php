<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Articolo;
use App\Models\CategoriaMerceologica;
use App\Models\MovimentoDeposito;
use Illuminate\Support\Facades\DB;

/**
 * Comando per correggere articoli resi che sono ancora nel magazzino conto deposito
 * 
 * Quando un articolo viene reso da un deposito inter-società, deve tornare
 * al magazzino originale. Questo comando corregge gli articoli già resi
 * che sono ancora nel magazzino CD invece che nel magazzino originale.
 */
class FixArticoliResiMagazzino extends Command
{
    protected $signature = 'fix:articoli-resi-magazzino {--dry-run : Mostra solo cosa verrebbe corretto senza applicare modifiche}';
    protected $description = 'Corregge articoli resi da depositi inter-società che sono ancora nel magazzino conto deposito';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  MODALITÀ DRY-RUN: Nessuna modifica verrà applicata');
        }

        $this->info('🔍 Cerca articoli resi ancora nel magazzino conto deposito...');
        $this->newLine();

        // Trova tutti i magazzini conto deposito (codice che inizia con "CD-")
        $magazziniCD = CategoriaMerceologica::where('codice', 'LIKE', 'CD-%')->pluck('id')->toArray();
        
        if (empty($magazziniCD)) {
            $this->info('✅ Nessun magazzino conto deposito trovato. Nulla da correggere.');
            return 0;
        }

        // Trova articoli che:
        // 1. Sono in un magazzino CD (categoria_merceologica_id in magazziniCD)
        // 2. Hanno quantita_in_deposito = 0 OPPURE conto_deposito_corrente_id IS NULL
        $articoliDaCorreggere = Articolo::whereIn('categoria_merceologica_id', $magazziniCD)
            ->where(function($query) {
                $query->where('quantita_in_deposito', 0)
                      ->orWhereNull('conto_deposito_corrente_id');
            })
            ->with(['categoriaMerceologica', 'sede'])
            ->get();

        if ($articoliDaCorreggere->isEmpty()) {
            $this->info('✅ Nessun articolo da correggere trovato.');
            return 0;
        }

        $this->info("Trovati {$articoliDaCorreggere->count()} articoli da correggere:");
        $this->newLine();

        $corretti = 0;
        $errore = 0;

        foreach ($articoliDaCorreggere as $articolo) {
            $this->line("📦 Articolo: {$articolo->codice}");
            $this->line("   Magazzino attuale: {$articolo->categoriaMerceologica->nome} (ID: {$articolo->categoria_merceologica_id})");
            
            // Cerca il movimento di invio più recente per questo articolo
            $movimentoInvio = MovimentoDeposito::where('articolo_id', $articolo->id)
                ->where('tipo_movimento', 'invio')
                ->with('contoDeposito.sedeMittente')
                ->latest('data_movimento')
                ->first();

            $magazzinoOriginaleId = null;

            if ($movimentoInvio) {
                $contoDeposito = $movimentoInvio->contoDeposito;
                
                // Prova a recuperare da dettagli movimento
                if (isset($movimentoInvio->dettagli['magazzino_originale_id'])) {
                    $magazzinoOriginaleId = $movimentoInvio->dettagli['magazzino_originale_id'];
                    $this->line("   ✓ Magazzino originale trovato nei dettagli movimento: ID {$magazzinoOriginaleId}");
                } elseif ($contoDeposito && $contoDeposito->sedeMittente) {
                    // Fallback: cerca magazzino nella sede mittente
                    $sedeMittente = $contoDeposito->sedeMittente;
                    $magazzinoOriginale = $sedeMittente->categorieMerceologiche()
                        ->where('attivo', true)
                        ->where('codice', 'NOT LIKE', 'CD-%')
                        ->orderBy('id')
                        ->first();
                    
                    if ($magazzinoOriginale) {
                        $magazzinoOriginaleId = $magazzinoOriginale->id;
                        $this->line("   ✓ Magazzino originale trovato dalla sede mittente: {$magazzinoOriginale->nome} (ID: {$magazzinoOriginaleId})");
                    } else {
                        $this->warn("   ⚠️  Nessun magazzino trovato nella sede mittente");
                    }
                }
            } else {
                // Se non c'è movimento, prova a cercare dalla sede dell'articolo
                if ($articolo->sede) {
                    $magazzinoOriginale = $articolo->sede->categorieMerceologiche()
                        ->where('attivo', true)
                        ->where('codice', 'NOT LIKE', 'CD-%')
                        ->orderBy('id')
                        ->first();
                    
                    if ($magazzinoOriginale) {
                        $magazzinoOriginaleId = $magazzinoOriginale->id;
                        $this->line("   ✓ Magazzino originale trovato dalla sede articolo: {$magazzinoOriginale->nome} (ID: {$magazzinoOriginaleId})");
                    }
                }
            }

            if ($magazzinoOriginaleId) {
                if (!$dryRun) {
                    $articolo->categoria_merceologica_id = $magazzinoOriginaleId;
                    $articolo->save();
                    $corretti++;
                    $this->info("   ✅ Corretto! Ora nel magazzino ID: {$magazzinoOriginaleId}");
                } else {
                    $corretti++;
                    $this->info("   ✅ Verrebbe corretto al magazzino ID: {$magazzinoOriginaleId}");
                }
            } else {
                $errore++;
                $this->error("   ❌ Impossibile trovare magazzino originale per questo articolo");
            }
            
            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("📊 RISULTATI (DRY-RUN):");
            $this->info("   ✅ Verrebbero corretti: {$corretti}");
            $this->info("   ❌ Errori: {$errore}");
            $this->newLine();
            $this->info("Esegui senza --dry-run per applicare le correzioni.");
        } else {
            $this->info("📊 RISULTATI:");
            $this->info("   ✅ Articoli corretti: {$corretti}");
            if ($errore > 0) {
                $this->warn("   ❌ Articoli non corretti: {$errore}");
            }
        }

        return 0;
    }
}
