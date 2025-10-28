<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella magazzini ottimizzata
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_magazzini (vecchio schema)
 * 
 * Dominio: Magazzino
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
        Schema::create('magazzini', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->unique()->comment('Codice identificativo magazzino');
            $table->string('nome', 255)->comment('Nome magazzino (es: Mazzini, Monastero)');
            
            // Ubicazione fisica
            $table->string('indirizzo', 255)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 10)->nullable();
            
            // Tipologia
            $table->enum('tipo', ['principale', 'secondario', 'temporaneo', 'deposito_esterno'])
                  ->default('principale')
                  ->comment('Tipologia magazzino');
            
            // Status
            $table->boolean('attivo')->default(true)->comment('Magazzino operativo');
            
            // Dati aggiuntivi
            $table->text('note')->nullable();
            $table->json('configurazione')->nullable()->comment('Configurazioni specifiche (JSON)');
            
            // Timestamps (Laravel standard)
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('codice', 'idx_magazzini_codice');
            $table->index('attivo', 'idx_magazzini_attivo');
            $table->index('tipo', 'idx_magazzini_tipo');
            $table->index(['attivo', 'tipo'], 'idx_magazzini_attivo_tipo');
        });
        
        // Commento tabella
        DB::statement("ALTER TABLE magazzini COMMENT = 'Magazzini/Sedi fisiche - Dominio: Magazzino'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('magazzini');
    }
};

