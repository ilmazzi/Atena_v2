<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: DDT Deposito - Documenti specifici per Conti Deposito
 * 
 * Separazione domini: DDT acquisti vs DDT trasferimenti deposito
 * 
 * Dominio: Deposito
 * Responsabilità: Gestione documenti trasferimento tra sedi per conti deposito
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ddt_depositi', function (Blueprint $table) {
            $table->id();
            
            // ==========================================
            // IDENTIFICAZIONE DOCUMENTO
            // ==========================================
            $table->string('numero', 50)->unique()->comment('Numero DDT deposito (DEP-YYYY-NNNN)');
            $table->date('data_documento')->comment('Data emissione DDT');
            $table->integer('anno')->comment('Anno documento');
            
            // ==========================================
            // COLLEGAMENTO CONTO DEPOSITO
            // ==========================================
            $table->foreignId('conto_deposito_id')
                  ->constrained('conti_deposito')
                  ->restrictOnDelete()
                  ->comment('Conto deposito di riferimento');
            
            // ==========================================
            // TIPO E DIREZIONE
            // ==========================================
            $table->enum('tipo', [
                'invio',    // DDT per invio articoli in deposito
                'reso',     // DDT per reso articoli da deposito
                'rimando'   // DDT per rimando dopo reso
            ])->comment('Tipo di movimento');
            
            // ==========================================
            // SEDI COINVOLTE
            // ==========================================
            $table->foreignId('sede_mittente_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede che invia');
            
            $table->foreignId('sede_destinataria_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede che riceve');
            
            // ==========================================
            // WORKFLOW SPECIFICO DEPOSITO
            // ==========================================
            $table->enum('stato', [
                'creato',     // DDT creato
                'stampato',   // DDT stampato e pronto per spedizione
                'in_transito', // Merce in transito
                'ricevuto',   // Merce ricevuta dalla sede destinataria
                'confermato', // Ricezione confermata
                'chiuso'      // DDT completato
            ])->default('creato');
            
            // ==========================================
            // DATE TRACKING
            // ==========================================
            $table->datetime('data_stampa')->nullable()->comment('Quando è stato stampato');
            $table->datetime('data_spedizione')->nullable()->comment('Quando è stato spedito');
            $table->datetime('data_ricezione')->nullable()->comment('Quando è stato ricevuto');
            $table->datetime('data_conferma')->nullable()->comment('Quando è stato confermato');
            
            // ==========================================
            // INFORMAZIONI TRASPORTO
            // ==========================================
            $table->string('causale', 255)->default('Conto deposito')->comment('Causale trasporto');
            $table->integer('numero_colli')->nullable()->comment('Numero colli spediti');
            $table->string('corriere', 255)->nullable()->comment('Corriere utilizzato');
            $table->string('numero_tracking', 100)->nullable()->comment('Numero di tracking');
            
            // ==========================================
            // VALORI ECONOMICI
            // ==========================================
            $table->decimal('valore_dichiarato', 12, 2)->default(0)->comment('Valore dichiarato per trasporto');
            $table->integer('articoli_totali')->default(0)->comment('Numero totale articoli nel DDT');
            
            // ==========================================
            // USER TRACKING
            // ==========================================
            $table->foreignId('creato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha creato il DDT');
            
            $table->foreignId('confermato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha confermato la ricezione');
            
            // ==========================================
            // METADATA
            // ==========================================
            $table->text('note')->nullable()->comment('Note aggiuntive');
            $table->json('configurazione')->nullable()->comment('Configurazioni specifiche');
            
            // ==========================================
            // TIMESTAMPS
            // ==========================================
            $table->timestamps();
            $table->softDeletes();
            
            // ==========================================
            // INDICI PER PERFORMANCE
            // ==========================================
            $table->index('conto_deposito_id', 'idx_ddt_dep_conto');
            $table->index(['tipo', 'stato'], 'idx_ddt_dep_tipo_stato');
            $table->index('sede_mittente_id', 'idx_ddt_dep_mitt');
            $table->index('sede_destinataria_id', 'idx_ddt_dep_dest');
            $table->index('data_documento', 'idx_ddt_dep_data');
            $table->index(['anno', 'numero'], 'idx_ddt_dep_anno_num');
        });
        
        // ==========================================
        // DDT DEPOSITI DETTAGLI
        // ==========================================
        Schema::create('ddt_depositi_dettagli', function (Blueprint $table) {
            $table->id();
            
            // Relazione con DDT deposito
            $table->foreignId('ddt_deposito_id')
                  ->constrained('ddt_depositi')
                  ->cascadeOnDelete();
            
            // Item (articolo o prodotto finito)
            $table->foreignId('articolo_id')
                  ->nullable()
                  ->constrained('articoli')
                  ->restrictOnDelete()
                  ->comment('Articolo nel DDT (NULL se prodotto finito)');
            
            $table->foreignId('prodotto_finito_id')
                  ->nullable()
                  ->constrained('prodotti_finiti')
                  ->restrictOnDelete()
                  ->comment('Prodotto finito nel DDT (NULL se articolo)');
            
            // Dati della riga
            $table->string('codice_item', 100)->comment('Codice dell\'articolo/PF');
            $table->string('descrizione', 500)->comment('Descrizione dell\'articolo/PF');
            $table->integer('quantita')->default(1)->comment('Quantità trasferita');
            $table->decimal('valore_unitario', 10, 2)->default(0)->comment('Valore unitario dichiarato');
            $table->decimal('valore_totale', 12, 2)->default(0)->comment('Valore totale riga');
            
            // Status riga
            $table->boolean('confermato')->default(false)->comment('Riga confermata dal destinatario');
            $table->integer('quantita_ricevuta')->nullable()->comment('Quantità effettivamente ricevuta');
            $table->text('note_riga')->nullable()->comment('Note specifiche per questa riga');
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            
            // Indici
            $table->index('ddt_deposito_id', 'idx_ddt_det_deposito');
            $table->index('articolo_id', 'idx_ddt_det_articolo');
            $table->index('prodotto_finito_id', 'idx_ddt_det_pf');
            $table->index('confermato', 'idx_ddt_det_confermato');
        });
        
        // ==========================================
        // COMMENTI TABELLE
        // ==========================================
        DB::statement("ALTER TABLE ddt_depositi COMMENT = 'DDT specifici per Conti Deposito - Dominio: Deposito'");
        DB::statement("ALTER TABLE ddt_depositi_dettagli COMMENT = 'Dettagli DDT Deposito - Dominio: Deposito'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ddt_depositi_dettagli');
        Schema::dropIfExists('ddt_depositi');
    }
};
