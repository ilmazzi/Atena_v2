<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Crea magazzini "Conto Deposito" per ogni società
 * 
 * Questi magazzini speciali sono usati per visualizzare articoli in conto deposito
 * ricevuti da altre società. Ogni società ha un magazzino "Conto Deposito"
 * nella sua sede principale (prima sede della società).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Recupera le società
        $societa = DB::table('societa')->where('attivo', true)->get();
        
        foreach ($societa as $soc) {
            // Trova la prima sede attiva della società (sede principale)
            $sedePrincipale = DB::table('sedi')
                ->where('societa_id', $soc->id)
                ->where('attivo', true)
                ->orderBy('id')
                ->first();
            
            if (!$sedePrincipale) {
                \Log::warning("Società {$soc->codice} non ha sedi attive - saltata creazione magazzino CD");
                continue;
            }
            
            // Verifica se il magazzino CD esiste già
            $existing = DB::table('categorie_merceologiche')
                ->where('sede_id', $sedePrincipale->id)
                ->where('codice', "CD-{$soc->codice}")
                ->first();
            
            if ($existing) {
                \Log::info("Magazzino Conto Deposito per {$soc->codice} già esistente");
                continue;
            }
            
            // Crea il magazzino Conto Deposito
            // Usa un ID alto per evitare conflitti (es: 9000 + societa_id)
            $magazzinoId = 9000 + $soc->id;
            
            DB::table('categorie_merceologiche')->insert([
                'id' => $magazzinoId,
                'sede_id' => $sedePrincipale->id,
                'codice' => "CD-{$soc->codice}",
                'nome' => "Conto Deposito - {$soc->ragione_sociale}",
                'indirizzo' => $sedePrincipale->indirizzo ?? null,
                'citta' => $sedePrincipale->citta ?? null,
                'provincia' => $sedePrincipale->provincia ?? null,
                'cap' => $sedePrincipale->cap ?? null,
                'note' => "Magazzino speciale per gestione articoli in conto deposito ricevuti da altre società. Società: {$soc->ragione_sociale}",
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            \Log::info("✅ Creato magazzino Conto Deposito: CD-{$soc->codice} per {$soc->ragione_sociale}");
        }
    }

    public function down(): void
    {
        // Elimina i magazzini Conto Deposito (identificati dal codice che inizia con "CD-")
        DB::table('categorie_merceologiche')
            ->where('codice', 'LIKE', 'CD-%')
            ->delete();
    }
};
