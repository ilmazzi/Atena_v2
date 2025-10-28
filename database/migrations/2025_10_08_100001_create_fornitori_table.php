<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella fornitori ottimizzata
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_fornitori (vecchio schema)
 * 
 * Dominio: Fatturazione
 * ADR: 004-database-schema-refactoring
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
        Schema::create('fornitori', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->unique()->nullable()->comment('Codice fornitore');
            $table->string('ragione_sociale', 255)->comment('Ragione sociale fornitore');
            
            // Dati fiscali
            $table->string('partita_iva', 20)->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            $table->string('codice_sdi', 10)->nullable()->comment('Codice SDI fatturazione elettronica');
            
            // Indirizzo
            $table->string('indirizzo', 255)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 10)->nullable();
            $table->string('nazione', 2)->default('IT');
            
            // Contatti
            $table->string('telefono', 50)->nullable();
            $table->string('cellulare', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pec', 255)->nullable()->comment('Email PEC');
            $table->string('sito_web', 255)->nullable();
            
            // Dati commerciali
            $table->boolean('attivo')->default(true);
            $table->enum('categoria', ['metalli', 'pietre', 'accessori', 'servizi', 'altro'])
                  ->nullable()
                  ->comment('Categoria merceologica');
            
            // Condizioni pagamento
            $table->integer('giorni_pagamento')->nullable()->comment('Giorni per pagamento (es: 30, 60)');
            $table->string('iban', 34)->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            $table->decimal('rating', 3, 2)->nullable()->comment('Valutazione 0-5');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('ragione_sociale', 'idx_fornitori_ragione');
            $table->index('attivo', 'idx_fornitori_attivo');
            $table->index('categoria', 'idx_fornitori_categoria');
            $table->index(['attivo', 'categoria'], 'idx_fornitori_attivo_categoria');
            
            // Fulltext search
            $table->fullText(['ragione_sociale', 'citta'], 'idx_fornitori_fulltext');
        });
        
        DB::statement("ALTER TABLE fornitori COMMENT = 'Fornitori merce - Dominio: Fatturazione'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fornitori');
    }
};

