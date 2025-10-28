<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella giacenze ottimizzata
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_articoli_giacenze (vecchio schema)
 * 
 * Dominio: Magazzino
 * ADR: 004-database-schema-refactoring
 * 
 * Pattern: One-to-One con articoli (ogni articolo ha UNA giacenza)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('giacenze', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // RELAZIONI (One-to-One con articolo)
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('articolo_id')
                  ->unique()  // One-to-one OBBLIGATORIA
                  ->constrained('articoli')
                  ->cascadeOnDelete()
                  ->comment('Articolo (relazione 1:1 - un articolo ha UNA giacenza)');
            
            $table->foreignId('magazzino_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino corrente (denormalizzato per performance)');
            
            // ═══════════════════════════════════════════════════════════
            // QUANTITÀ (sempre 1 per gioielli, ma future-proof)
            // ═══════════════════════════════════════════════════════════
            $table->integer('quantita')->default(1)->comment('Quantità (tipicamente 1 per gioielli)');
            $table->integer('quantita_minima')->default(0)->comment('Soglia minima (alert)');
            $table->integer('quantita_riservata')->default(0)->comment('Quantità riservata per ordini');
            
            // ═══════════════════════════════════════════════════════════
            // UBICAZIONE FISICA
            // ═══════════════════════════════════════════════════════════
            $table->string('scaffale', 50)->nullable()->comment('Scaffale/ripiano');
            $table->string('box', 50)->nullable()->comment('Box/contenitore');
            $table->string('posizione', 100)->nullable()->comment('Posizione specifica');
            
            // ═══════════════════════════════════════════════════════════
            // TRACKING MOVIMENTI
            // ═══════════════════════════════════════════════════════════
            $table->timestamp('ultimo_inventario_at')->nullable()->comment('Ultimo inventario fisico');
            $table->timestamp('ultimo_movimento_at')->nullable()->comment('Ultima movimentazione');
            $table->timestamp('ultima_verifica_at')->nullable()->comment('Ultima verifica giacenza');
            
            // ═══════════════════════════════════════════════════════════
            // METADATA
            // ═══════════════════════════════════════════════════════════
            $table->text('note')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();
            // NO soft deletes (cascade da articoli)
            
            // ═══════════════════════════════════════════════════════════
            // INDICI
            // ═══════════════════════════════════════════════════════════
            $table->index('magazzino_id', 'idx_giacenze_magazzino');
            $table->index('quantita', 'idx_giacenze_quantita');
            $table->index(['magazzino_id', 'quantita'], 'idx_giacenze_mag_qty');
            $table->index('scaffale', 'idx_giacenze_scaffale');
            $table->index('ultimo_inventario_at', 'idx_giacenze_inventario');
        });
        
        DB::statement("ALTER TABLE giacenze COMMENT = 'Giacenze articoli - Dominio: Magazzino'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('giacenze');
    }
};

