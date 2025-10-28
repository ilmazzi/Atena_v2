<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle Inventari
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_scansioni_inventario (parziale)
 * 
 * Dominio: Inventario
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // INVENTARI (Campagne inventario)
        // ═══════════════════════════════════════════════════════════
        Schema::create('inventari', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->unique()->comment('Codice campagna inventario');
            $table->string('descrizione', 255)->comment('Descrizione inventario');
            
            // Scope
            $table->foreignId('magazzino_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino inventariato');
            
            // Periodo
            $table->date('data_inizio')->comment('Data inizio inventario');
            $table->date('data_fine')->nullable()->comment('Data completamento');
            
            // Status
            $table->enum('stato', [
                'pianificato',     // Pianificato ma non iniziato
                'in_corso',        // In esecuzione
                'completato',      // Completato
                'sospeso',         // Temporaneamente sospeso
                'annullato'        // Annullato
            ])->default('pianificato');
            
            // Statistiche (calcolate)
            $table->integer('totale_articoli')->default(0)->comment('Totale articoli da inventariare');
            $table->integer('articoli_scansionati')->default(0)->comment('Articoli già scansionati');
            $table->integer('articoli_ok')->default(0)->comment('Articoli senza discrepanze');
            $table->integer('articoli_discrepanza')->default(0)->comment('Articoli con differenze');
            $table->integer('articoli_non_trovati')->default(0)->comment('Articoli non trovati');
            
            // Gestione
            $table->foreignId('responsabile_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente responsabile inventario');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('magazzino_id', 'idx_inventari_magazzino');
            $table->index('stato', 'idx_inventari_stato');
            $table->index('data_inizio', 'idx_inventari_data_inizio');
            $table->index(['magazzino_id', 'stato'], 'idx_inventari_mag_stato');
            $table->index(['data_inizio', 'data_fine'], 'idx_inventari_periodo');
        });
        
        // ═══════════════════════════════════════════════════════════
        // INVENTARI SCANSIONI (dettaglio scansioni)
        // ═══════════════════════════════════════════════════════════
        Schema::create('inventari_scansioni', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('inventario_id')
                  ->constrained('inventari')
                  ->cascadeOnDelete();
            
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->cascadeOnDelete();
            
            // Esito scansione
            $table->enum('esito', [
                'ok',               // Trovato, tutto OK
                'discrepanza',      // Trovato ma con differenze
                'non_trovato',      // Non trovato fisicamente
                'extra'             // Trovato ma non in sistema
            ])->comment('Risultato scansione');
            
            // Dettagli discrepanza
            $table->string('tipo_discrepanza', 100)
                  ->nullable()
                  ->comment('Tipo discrepanza rilevata');
            
            $table->text('note_discrepanza')->nullable();
            
            // Foto evidenza
            $table->string('foto_path', 255)->nullable()->comment('Foto articolo durante inventario');
            $table->json('foto_multiple')->nullable()->comment('Multiple foto se necessario');
            
            // Chi ha scansionato
            $table->foreignId('scansionato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamp('scansionato_at')->nullable()->comment('Timestamp scansione preciso');
            
            // Rettifica (se applicata)
            $table->boolean('rettificato')->default(false)->comment('Giacenza rettificata');
            $table->timestamp('rettificato_at')->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            
            // Indici
            $table->index('inventario_id', 'idx_inv_scan_inventario');
            $table->index('articolo_id', 'idx_inv_scan_articolo');
            $table->index('esito', 'idx_inv_scan_esito');
            $table->index('scansionato_at', 'idx_inv_scan_data');
            $table->index(['inventario_id', 'esito'], 'idx_inv_scan_inv_esito');
            
            // Evitare scan duplicati
            $table->unique(['inventario_id', 'articolo_id'], 'idx_inv_scan_unique');
        });
        
        DB::statement("ALTER TABLE inventari COMMENT = 'Campagne Inventario - Dominio: Inventario'");
        DB::statement("ALTER TABLE inventari_scansioni COMMENT = 'Scansioni Inventario - Dominio: Inventario'");
    }

    public function down()
    {
        Schema::dropIfExists('inventari_scansioni');
        Schema::dropIfExists('inventari');
    }
};

