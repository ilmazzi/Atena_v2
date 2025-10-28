<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle movimentazioni interne
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_movimentazioni_testata, mag_movimentazioni_dettaglio
 * 
 * Dominio: Magazzino
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // MOVIMENTAZIONI (Testata)
        // ═══════════════════════════════════════════════════════════
        Schema::create('movimentazioni', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('numero_documento', 50)->unique()->comment('Numero progressivo DDT movimentazione');
            
            // Magazzini (partenza → destinazione)
            $table->foreignId('magazzino_partenza_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino di partenza');
            
            $table->foreignId('magazzino_destinazione_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino di destinazione');
            
            // Dati documento
            $table->date('data_movimentazione')->comment('Data esecuzione movimentazione');
            $table->date('data_prevista')->nullable()->comment('Data prevista arrivo');
            
            // Status workflow
            $table->enum('stato', [
                'bozza',           // Creata ma non confermata
                'confermata',      // Confermata, in transito
                'completata',      // Ricevuta da destinazione
                'annullata'        // Annullata
            ])->default('bozza');
            
            // Chi gestisce
            $table->foreignId('creata_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha creato movimentazione');
            
            $table->foreignId('confermata_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha confermato');
            
            $table->timestamp('confermata_at')->nullable();
            $table->timestamp('completata_at')->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            $table->text('causale')->nullable()->comment('Causale movimentazione');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('magazzino_partenza_id', 'idx_mov_partenza');
            $table->index('magazzino_destinazione_id', 'idx_mov_destinazione');
            $table->index('data_movimentazione', 'idx_mov_data');
            $table->index('stato', 'idx_mov_stato');
            $table->index(['stato', 'data_movimentazione'], 'idx_mov_stato_data');
        });
        
        // ═══════════════════════════════════════════════════════════
        // MOVIMENTAZIONI DETTAGLI (righe articoli)
        // ═══════════════════════════════════════════════════════════
        Schema::create('movimentazioni_dettagli', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('movimentazione_id')
                  ->constrained('movimentazioni')
                  ->cascadeOnDelete()
                  ->comment('Movimentazione di appartenenza');
            
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->cascadeOnDelete()
                  ->comment('Articolo movimentato');
            
            // Dati riga
            $table->integer('quantita')->default(1)->comment('Quantità movimentata');
            $table->string('note', 500)->nullable();
            
            // Timestamps (solo created_at, immutabile dopo creazione)
            $table->timestamp('created_at')->nullable();
            
            // Indici
            $table->index('movimentazione_id', 'idx_mov_det_movimentazione');
            $table->index('articolo_id', 'idx_mov_det_articolo');
            
            // Prevenire duplicati
            $table->unique(['movimentazione_id', 'articolo_id'], 'idx_mov_det_unique');
        });
        
        DB::statement("ALTER TABLE movimentazioni COMMENT = 'Movimentazioni interne - Dominio: Magazzino'");
        DB::statement("ALTER TABLE movimentazioni_dettagli COMMENT = 'Dettagli movimentazioni - Dominio: Magazzino'");
    }

    public function down()
    {
        Schema::dropIfExists('movimentazioni_dettagli');
        Schema::dropIfExists('movimentazioni');
    }
};

