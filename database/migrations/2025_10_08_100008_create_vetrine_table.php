<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle Vetrine
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_vetrine, mag_articoli_vetrine
 * 
 * Dominio: Vetrina
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // VETRINE
        // ═══════════════════════════════════════════════════════════
        Schema::create('vetrine', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->comment('Codice vetrina (es: VET-01)');
            $table->string('nome', 255)->comment('Nome vetrina');
            
            // Ubicazione
            $table->enum('ubicazione', ['mazzini', 'monastero', 'roma', 'altro'])
                  ->comment('Sede fisica vetrina');
            
            $table->string('ubicazione_specifica', 255)
                  ->nullable()
                  ->comment('Dettaglio ubicazione (es: "Piano terra, lato sinistro")');
            
            // Tipologia
            $table->string('tipologia', 100)
                  ->nullable()
                  ->comment('Categoria esposta (es: Anelli, Collane, Orologi)');
            
            // Status
            $table->boolean('attiva')->default(true)->comment('Vetrina in uso');
            $table->integer('capacita_massima')->nullable()->comment('Max articoli esponibili');
            
            // Display
            $table->integer('ordine_display')->default(0)->comment('Ordine visualizzazione');
            $table->string('colore_identificativo', 50)->nullable()->comment('Colore per UI');
            
            // Metadata
            $table->text('note')->nullable();
            $table->json('caratteristiche')->nullable()->comment('Caratteristiche specifiche vetrina');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->unique(['ubicazione', 'codice'], 'idx_vetrine_ubic_codice');
            $table->index('ubicazione', 'idx_vetrine_ubicazione');
            $table->index('attiva', 'idx_vetrine_attiva');
            $table->index('tipologia', 'idx_vetrine_tipologia');
            $table->index(['ubicazione', 'attiva'], 'idx_vetrine_ubic_attiva');
            $table->index('ordine_display', 'idx_vetrine_ordine');
        });
        
        // ═══════════════════════════════════════════════════════════
        // ARTICOLI VETRINE (Pivot table con metadata)
        // ═══════════════════════════════════════════════════════════
        Schema::create('articoli_vetrine', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->cascadeOnDelete();
            
            $table->foreignId('vetrina_id')
                  ->constrained('vetrine')
                  ->cascadeOnDelete();
            
            // Posizionamento
            $table->integer('posizione')->default(0)->comment('Posizione/ordine in vetrina');
            $table->string('ripiano', 50)->nullable()->comment('Ripiano/livello');
            
            // Prezzo vetrina (CRITICO - Cliente compliance)
            $table->decimal('prezzo_vetrina', 10, 2)->nullable()->comment('Prezzo vendita in vetrina (NON salvato in articoli!)');
            
            // Testo vetrina (descrizione personalizzata)
            $table->text('testo_vetrina')->nullable()->comment('Testo descrittivo personalizzato per esposizione');
            
            // Tracking
            $table->date('data_inserimento')->comment('Data esposizione in vetrina');
            $table->date('data_rimozione')->nullable()->comment('Data rimozione (se rimosso)');
            $table->integer('giorni_esposizione')->nullable()->comment('Giorni totali in vetrina');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Indici
            $table->index('articolo_id', 'idx_art_vet_articolo');
            $table->index('vetrina_id', 'idx_art_vet_vetrina');
            $table->index(['vetrina_id', 'posizione'], 'idx_art_vet_vet_pos');
            $table->index('data_inserimento', 'idx_art_vet_data_ins');
            
            // Un articolo può essere in una sola vetrina alla volta
            $table->unique(['articolo_id', 'vetrina_id'], 'idx_art_vet_unique');
        });
        
        DB::statement("ALTER TABLE vetrine COMMENT = 'Vetrine espositive - Dominio: Vetrina'");
        DB::statement("ALTER TABLE articoli_vetrine COMMENT = 'Articoli esposti in vetrine - Dominio: Vetrina'");
    }

    public function down()
    {
        Schema::dropIfExists('articoli_vetrine');
        Schema::dropIfExists('vetrine');
    }
};

