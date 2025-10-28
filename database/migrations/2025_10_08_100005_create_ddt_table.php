<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle DDT (Documenti Di Trasporto)
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_ddt_articoli_testate, mag_ddt_articoli_dettagli (SEPARATO da Fatture!)
 * 
 * Dominio: Fatturazione
 * ADR: 004-database-schema-refactoring
 * 
 * IMPORTANTE: Separato da tabella Fatture (prima erano insieme!)
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // DDT (Documenti Di Trasporto)
        // ═══════════════════════════════════════════════════════════
        Schema::create('ddt', function (Blueprint $table) {
            $table->id();
            
            // Identificazione documento
            $table->string('numero', 50)->comment('Numero DDT');
            $table->date('data_documento')->comment('Data emissione DDT');
            $table->integer('anno')->comment('Anno documento');
            
            // Fornitore
            $table->foreignId('fornitore_id')
                  ->constrained('fornitori')
                  ->restrictOnDelete()
                  ->comment('Fornitore mittente');
            
            // Dati trasporto
            $table->string('causale', 255)->nullable()->comment('Causale trasporto');
            $table->integer('numero_colli')->nullable()->comment('Numero colli');
            $table->decimal('peso_totale', 10, 2)->nullable()->comment('Peso totale kg');
            $table->string('vettore', 255)->nullable()->comment('Vettore trasporto');
            $table->string('numero_tracking', 100)->nullable()->comment('Tracking spedizione');
            
            // Workflow
            $table->enum('stato', [
                'bozza',              // In creazione
                'confermato',         // DDT confermato
                'ricevuto',           // Merce ricevuta
                'caricato',           // Articoli caricati in magazzino
                'parzialmente_caricato',  // Solo alcuni articoli caricati
                'annullato'           // DDT annullato
            ])->default('bozza');
            
            $table->date('data_ricezione')->nullable()->comment('Data ricezione merce');
            $table->date('data_carico')->nullable()->comment('Data carico in magazzino');
            
            // Magazzino destinazione
            $table->foreignId('magazzino_destinazione_id')
                  ->nullable()
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino destinazione carico');
            
            // Chi gestisce
            $table->foreignId('caricato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha fatto il carico');
            
            // File
            $table->string('file_pdf_path', 255)->nullable()->comment('Path PDF DDT');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            // NOTA: Non UNIQUE perché nella produzione ci sono duplicati (descrizioni usate come numero)
            $table->index(['numero', 'fornitore_id', 'anno'], 'idx_ddt_numero_forn_anno');
            $table->index('fornitore_id', 'idx_ddt_fornitore');
            $table->index('data_documento', 'idx_ddt_data');
            $table->index('stato', 'idx_ddt_stato');
            $table->index(['fornitore_id', 'data_documento'], 'idx_ddt_forn_data');
            $table->index('magazzino_destinazione_id', 'idx_ddt_mag_dest');
        });
        
        // ═══════════════════════════════════════════════════════════
        // DDT DETTAGLI (righe)
        // ═══════════════════════════════════════════════════════════
        Schema::create('ddt_dettagli', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('ddt_id')
                  ->constrained('ddt')
                  ->cascadeOnDelete();
            
            $table->foreignId('articolo_id')
                  ->nullable()  // NULL finché non caricato
                  ->constrained('articoli')
                  ->nullOnDelete()
                  ->comment('Articolo caricato (NULL se non ancora caricato)');
            
            // Dati riga DDT
            $table->string('descrizione', 500)->comment('Descrizione articolo da DDT');
            $table->integer('quantita')->default(1);
            $table->decimal('prezzo_unitario', 10, 2)->nullable()->comment('Prezzo unitario se presente');
            $table->decimal('peso', 10, 2)->nullable()->comment('Peso dichiarato');
            
            // Status riga
            $table->boolean('caricato')->default(false)->comment('Riga caricata in magazzino');
            $table->date('data_carico_riga')->nullable()->comment('Data carico singola riga');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            
            // Indici
            $table->index('ddt_id', 'idx_ddt_det_ddt');
            $table->index('articolo_id', 'idx_ddt_det_articolo');
            $table->index('caricato', 'idx_ddt_det_caricato');
        });
        
        DB::statement("ALTER TABLE ddt COMMENT = 'DDT Fornitori - Dominio: Fatturazione'");
        DB::statement("ALTER TABLE ddt_dettagli COMMENT = 'Dettagli DDT - Dominio: Fatturazione'");
    }

    public function down()
    {
        Schema::dropIfExists('ddt_dettagli');
        Schema::dropIfExists('ddt');
    }
};

