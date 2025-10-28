<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle CRM
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: cli_clienti, crm_* (naming inconsistente)
 * 
 * Dominio: CRM
 * ADR: 004-database-schema-refactoring
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // CRM CONTATTI
        // ═══════════════════════════════════════════════════════════
        Schema::create('crm_contatti', function (Blueprint $table) {
            $table->id();
            
            // Anagrafica
            $table->string('nome', 255);
            $table->string('cognome', 255);
            $table->string('azienda', 255)->nullable();
            
            // Dati fiscali (se cliente business)
            $table->string('partita_iva', 20)->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            
            // Contatti
            $table->string('telefono', 50)->nullable();
            $table->string('cellulare', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pec', 255)->nullable();
            
            // Indirizzo
            $table->text('indirizzo')->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 10)->nullable();
            $table->string('nazione', 2)->default('IT');
            
            // CRM Status
            $table->enum('stato', [
                'lead',            // Nuovo contatto
                'qualificato',     // Lead qualificato
                'in_trattativa',   // Negoziazione in corso
                'cliente',         // Convertito in cliente
                'cliente_inattivo', // Cliente non più attivo
                'perso',           // Opportunità persa
                'sospeso'          // Temporaneamente sospeso
            ])->default('lead');
            
            $table->enum('priorita', ['bassa', 'media', 'alta', 'urgente'])
                  ->default('media');
            
            // Origine
            $table->string('origine', 100)
                  ->nullable()
                  ->comment('Come ci ha conosciuto (Passaparola, Web, Facebook...)');
            
            // Follow-up
            $table->date('prossimo_contatto_at')->nullable()->comment('Data prossimo follow-up');
            $table->date('ultima_interazione_at')->nullable()->comment('Data ultima comunicazione');
            
            // Interessi
            $table->text('interessi')->nullable()->comment('Interessi/preferenze cliente');
            $table->json('tags')->nullable()->comment('Tags per categorizzazione');
            
            // Assegnazione
            $table->foreignId('assegnato_a')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Venditore/responsabile assegnato');
            
            // Privacy/GDPR
            $table->boolean('consenso_marketing')->default(false);
            $table->boolean('consenso_terze_parti')->default(false);
            $table->date('data_consenso')->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            $table->decimal('valore_stimato', 10, 2)->nullable()->comment('Valore potenziale');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('stato', 'idx_crm_stato');
            $table->index('priorita', 'idx_crm_priorita');
            $table->index('prossimo_contatto_at', 'idx_crm_follow_up');
            $table->index('assegnato_a', 'idx_crm_assegnato');
            $table->index(['stato', 'priorita'], 'idx_crm_stato_priorita');
            $table->index(['assegnato_a', 'stato'], 'idx_crm_assign_stato');
            
            // Fulltext search
            $table->fullText(['nome', 'cognome', 'azienda', 'email'], 'idx_crm_fulltext');
        });
        
        // ═══════════════════════════════════════════════════════════
        // CRM INTERAZIONI (storico comunicazioni)
        // ═══════════════════════════════════════════════════════════
        Schema::create('crm_interazioni', function (Blueprint $table) {
            $table->id();
            
            // Relazione
            $table->foreignId('contatto_id')
                  ->constrained('crm_contatti')
                  ->cascadeOnDelete();
            
            // Tipo interazione
            $table->enum('tipo', [
                'telefono',
                'email',
                'whatsapp',
                'incontro',
                'visita_negozio',
                'altro'
            ])->comment('Canale comunicazione');
            
            $table->datetime('data_interazione')->comment('Data e ora interazione');
            
            // Contenuto
            $table->string('oggetto', 255)->nullable()->comment('Oggetto comunicazione');
            $table->text('descrizione')->nullable()->comment('Dettagli interazione');
            
            // Esito
            $table->enum('esito', [
                'positivo',        // Interesse confermato
                'neutro',          // Informativo
                'negativo',        // Non interessato
                'da_ricontattare'  // Ricontattare in futuro
            ])->nullable();
            
            // Prossimi step
            $table->date('prossimo_follow_up')->nullable()->comment('Prossimo contatto programmato');
            $table->text('azioni_da_fare')->nullable()->comment('Todo per prossima interazione');
            
            // Chi ha gestito
            $table->foreignId('utente_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha gestito interazione');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indici
            $table->index('contatto_id', 'idx_crm_int_contatto');
            $table->index('data_interazione', 'idx_crm_int_data');
            $table->index('tipo', 'idx_crm_int_tipo');
            $table->index('esito', 'idx_crm_int_esito');
            $table->index(['contatto_id', 'data_interazione'], 'idx_crm_int_cont_data');
        });
        
        // ═══════════════════════════════════════════════════════════
        // CRM TIPI CONTATTO (lookup table)
        // ═══════════════════════════════════════════════════════════
        Schema::create('crm_tipi_contatto', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('descrizione', 255)->nullable();
            $table->string('icona', 50)->nullable()->comment('Icona UI');
            $table->boolean('attivo')->default(true);
            $table->integer('ordine')->default(0);
            
            $table->timestamps();
        });
        
        DB::statement("ALTER TABLE crm_contatti COMMENT = 'Contatti CRM - Dominio: CRM'");
        DB::statement("ALTER TABLE crm_interazioni COMMENT = 'Storico Interazioni - Dominio: CRM'");
        DB::statement("ALTER TABLE crm_tipi_contatto COMMENT = 'Tipi Contatto (Lookup) - Dominio: CRM'");
    }

    public function down()
    {
        Schema::dropIfExists('crm_interazioni');
        Schema::dropIfExists('crm_tipi_contatto');
        Schema::dropIfExists('crm_contatti');
    }
};

