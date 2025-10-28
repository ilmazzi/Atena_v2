<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella Prodotti Finiti / Semilavorati
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_prodotti_finiti
 * 
 * Dominio: Magazzino
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('prodotti_finiti', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 100)->unique()->comment('Codice prodotto finito');
            $table->string('descrizione', 500);
            
            // Classificazione
            $table->enum('tipologia', [
                'semilavorato',
                'prodotto_finito',
                'componente',
                'altro'
            ])->default('semilavorato');
            
            // Magazzino
            $table->foreignId('magazzino_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete();
            
            // Dati tecnici
            $table->decimal('peso_totale', 10, 2)->nullable();
            $table->string('materiale_principale', 100)->nullable();
            $table->string('caratura', 50)->nullable();
            
            // Composizione (se assemblato da componenti)
            $table->json('componenti')->nullable()->comment('Array componenti utilizzati');
            $table->json('lavorazioni')->nullable()->comment('Lavorazioni effettuate');
            
            // Costi
            // ⚠️ REQUISITO CLIENTE: Solo costi, NO prezzo vendita
            // ❌ NO prezzo_vendita (va solo su etichette)
            $table->decimal('costo_materiali', 10, 2)->nullable();
            $table->decimal('costo_lavorazione', 10, 2)->nullable();
            $table->decimal('costo_totale', 10, 2)->nullable();
            
            // Status
            $table->enum('stato', [
                'in_lavorazione',
                'completato',
                'venduto',
                'scartato'
            ])->default('in_lavorazione');
            
            // Tracking
            $table->date('data_inizio_lavorazione')->nullable();
            $table->date('data_completamento')->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            $table->string('foto_path', 255)->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('magazzino_id', 'idx_pf_magazzino');
            $table->index('stato', 'idx_pf_stato');
            $table->index('tipologia', 'idx_pf_tipologia');
            $table->fullText(['codice', 'descrizione'], 'idx_pf_fulltext');
        });
        
        // ═══════════════════════════════════════════════════════════
        // DIBA (se ancora necessario - da valutare)
        // ═══════════════════════════════════════════════════════════
        Schema::create('diba', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('prodotto_finito_id')
                  ->nullable()
                  ->constrained('prodotti_finiti')
                  ->cascadeOnDelete();
            
            // Dati DIBA specifici
            $table->string('codice_diba', 100)->unique()->nullable();
            $table->json('dati')->nullable()->comment('Dati specifici DIBA (JSON)');
            
            // Timestamps
            $table->timestamps();
            
            $table->index('prodotto_finito_id', 'idx_diba_pf');
        });
        
        DB::statement("ALTER TABLE prodotti_finiti COMMENT = 'Prodotti Finiti/Semilavorati - Dominio: Magazzino'");
        DB::statement("ALTER TABLE diba COMMENT = 'DIBA Prodotti - Dominio: Magazzino'");
    }

    public function down()
    {
        Schema::dropIfExists('diba');
        Schema::dropIfExists('prodotti_finiti');
    }
};

