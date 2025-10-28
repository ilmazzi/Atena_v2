<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Aggiungi campi prodotto finito alla tabella articoli
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('articoli', function (Blueprint $table) {
            // Link a prodotto finito (se questo articolo È un prodotto finito)
            $table->foreignId('prodotto_finito_id')
                  ->after('categoria_merceologica_id')
                  ->nullable()
                  ->constrained('prodotti_finiti')
                  ->nullOnDelete()
                  ->comment('Se valorizzato, questo articolo è un prodotto finito assemblato');
            
            // Tracking assemblaggio
            $table->timestamp('assemblato_il')
                  ->after('prodotto_finito_id')
                  ->nullable()
                  ->comment('Data/ora assemblaggio prodotto finito');
            
            $table->foreignId('assemblato_da')
                  ->after('assemblato_il')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha assemblato');
            
            // Indici
            $table->index('prodotto_finito_id', 'idx_art_prodotto_finito');
            $table->index('assemblato_da', 'idx_art_assemblato_da');
        });
    }

    public function down()
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropForeign(['prodotto_finito_id']);
            $table->dropForeign(['assemblato_da']);
            
            $table->dropColumn([
                'prodotto_finito_id',
                'assemblato_il',
                'assemblato_da',
            ]);
        });
    }
};
