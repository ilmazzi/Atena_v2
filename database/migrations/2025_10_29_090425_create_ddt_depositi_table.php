<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabella DDT Depositi
 * 
 * Tabella dedicata per DDT specifici per conti deposito
 * Separata da ddt (acquisti) per seguire principi DDD
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se la tabella esiste già (creata manualmente)
        if (Schema::hasTable('ddt_depositi')) {
            return; // Tabella già esistente, salta la creazione
        }

        Schema::create('ddt_depositi', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('numero', 50)->unique()->comment('Numero DDT (DEP-YYYY-NNNN)');
            $table->date('data_documento')->comment('Data documento');
            $table->year('anno')->comment('Anno documento');
            
            // Riferimento conto deposito
            $table->foreignId('conto_deposito_id')
                  ->nullable()
                  ->constrained('conti_deposito')
                  ->nullOnDelete()
                  ->comment('Conto deposito di riferimento');
            
            // Tipo DDT
            $table->enum('tipo', ['invio', 'reso', 'rimando'])->comment('Tipo DDT deposito');
            
            // Sedi
            $table->foreignId('sede_mittente_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede mittente');
            
            $table->foreignId('sede_destinataria_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede destinataria');
            
            // Stato workflow
            $table->enum('stato', ['creato', 'stampato', 'in_transito', 'ricevuto', 'confermato', 'chiuso'])
                  ->default('creato')
                  ->comment('Stato del DDT');
            
            // Date workflow
            $table->timestamp('data_stampa')->nullable()->comment('Data stampa DDT');
            $table->timestamp('data_spedizione')->nullable()->comment('Data spedizione');
            $table->timestamp('data_ricezione')->nullable()->comment('Data ricezione');
            $table->timestamp('data_conferma')->nullable()->comment('Data conferma ricezione');
            
            // Dati trasporto
            $table->string('causale', 255)->nullable()->comment('Causale trasporto');
            $table->integer('numero_colli')->nullable()->comment('Numero colli');
            $table->string('corriere', 255)->nullable()->comment('Corriere');
            $table->string('numero_tracking', 255)->nullable()->comment('Numero tracking');
            
            // Valori
            $table->decimal('valore_dichiarato', 12, 2)->default(0)->comment('Valore totale dichiarato');
            $table->integer('articoli_totali')->default(0)->comment('Totale articoli/PF nel DDT');
            
            // User tracking
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
            
            // Metadata
            $table->text('note')->nullable()->comment('Note aggiuntive');
            $table->json('configurazione')->nullable()->comment('Configurazioni specifiche');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('numero', 'idx_ddt_dep_numero');
            $table->index('anno', 'idx_ddt_dep_anno');
            $table->index('conto_deposito_id', 'idx_ddt_dep_conto_deposito');
            $table->index('tipo', 'idx_ddt_dep_tipo');
            $table->index('stato', 'idx_ddt_dep_stato');
            $table->index('sede_mittente_id', 'idx_ddt_dep_sede_mittente');
            $table->index('sede_destinataria_id', 'idx_ddt_dep_sede_destinataria');
            $table->index(['conto_deposito_id', 'tipo'], 'idx_ddt_dep_conto_tipo');
            $table->index(['stato', 'data_documento'], 'idx_ddt_dep_stato_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ddt_depositi');
    }
};
