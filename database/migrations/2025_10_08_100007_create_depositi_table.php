<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle Conto Deposito
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_depositi, mag_registro_depositi, mag_ddt_depositi_*
 * 
 * Dominio: Deposito
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // DEPOSITI (Contratti conto deposito)
        // ═══════════════════════════════════════════════════════════
        Schema::create('depositi', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->unique()->comment('Codice deposito');
            $table->string('numero_ddt', 50)->nullable()->comment('Numero DDT associato');
            
            // Cliente
            $table->string('cliente_nome', 255);
            $table->string('cliente_cognome', 255);
            $table->string('cliente_telefono', 50)->nullable();
            $table->string('cliente_cellulare', 50)->nullable();
            $table->string('cliente_email', 255)->nullable();
            $table->text('cliente_indirizzo')->nullable();
            
            // Periodo deposito
            $table->date('data_inizio')->comment('Data inizio deposito');
            $table->date('data_scadenza')->comment('Data scadenza iniziale');
            $table->date('data_scadenza_effettiva')->nullable()->comment('Scadenza dopo proroghe');
            $table->integer('durata_giorni')->comment('Durata pattuita in giorni');
            $table->integer('proroghe_effettuate')->default(0)->comment('Numero proroghe');
            
            // Status
            $table->enum('stato', [
                'attivo',              // In corso
                'scaduto',             // Scaduto, in attesa rientro
                'prorogato',           // Prorogato
                'chiuso_restituzione', // Chiuso con restituzione merce
                'chiuso_vendita',      // Chiuso con vendita
                'annullato'            // Annullato
            ])->default('attivo');
            
            $table->date('data_chiusura')->nullable()->comment('Data chiusura deposito');
            
            // Importi (se previsti)
            $table->decimal('importo_deposito', 10, 2)->nullable()->comment('Importo depositato');
            $table->decimal('importo_vendita', 10, 2)->nullable()->comment('Importo vendita (se venduto)');
            
            // Magazzino origine
            $table->foreignId('magazzino_origine_id')
                  ->nullable()
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino di provenienza articoli');
            
            // Gestione
            $table->foreignId('gestito_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente responsabile');
            
            // File
            $table->string('ddt_pdf_path', 255)->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            $table->json('documenti')->nullable()->comment('Array paths documenti aggiuntivi');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('codice', 'idx_depositi_codice');
            $table->index('stato', 'idx_depositi_stato');
            $table->index('data_scadenza', 'idx_depositi_scadenza');
            $table->index('data_scadenza_effettiva', 'idx_depositi_scadenza_eff');
            $table->index(['cliente_cognome', 'cliente_nome'], 'idx_depositi_cliente');
            $table->index(['stato', 'data_scadenza'], 'idx_depositi_stato_scad');
            
            // Fulltext per ricerca clienti
            $table->fullText(['cliente_nome', 'cliente_cognome'], 'idx_depositi_fulltext');
        });
        
        // ═══════════════════════════════════════════════════════════
        // DEPOSITI ARTICOLI (pivot con metadata)
        // ═══════════════════════════════════════════════════════════
        Schema::create('depositi_articoli', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('deposito_id')
                  ->constrained('depositi')
                  ->cascadeOnDelete();
            
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->cascadeOnDelete();
            
            // Dati specifici articolo in deposito
            $table->date('data_inserimento')->comment('Data inserimento in deposito');
            $table->date('data_rientro')->nullable()->comment('Data rientro da deposito');
            
            $table->enum('tipo_rientro', [
                'restituzione',    // Cliente ha restituito
                'vendita',         // Cliente ha acquistato
                'scambio',         // Scambiato con altro
                'altro'
            ])->nullable();
            
            $table->decimal('prezzo_vendita', 10, 2)->nullable()->comment('Se venduto, prezzo');
            
            // Condition check
            $table->enum('condizione_uscita', [
                'ottima', 'buona', 'discreta', 'danneggiata'
            ])->nullable()->comment('Condizione al rientro');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indici
            $table->index('deposito_id', 'idx_dep_art_deposito');
            $table->index('articolo_id', 'idx_dep_art_articolo');
            $table->index('data_rientro', 'idx_dep_art_rientro');
            
            // Unicità (articolo può essere in un solo deposito alla volta)
            $table->unique(['deposito_id', 'articolo_id'], 'idx_dep_art_unique');
        });
        
        DB::statement("ALTER TABLE depositi COMMENT = 'Contratti Conto Deposito - Dominio: Deposito'");
        DB::statement("ALTER TABLE depositi_articoli COMMENT = 'Articoli in Conto Deposito - Dominio: Deposito'");
    }

    public function down()
    {
        Schema::dropIfExists('depositi_articoli');
        Schema::dropIfExists('depositi');
    }
};

