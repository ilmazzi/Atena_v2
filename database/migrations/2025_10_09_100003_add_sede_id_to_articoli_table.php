<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Aggiunge sede_id a tabella articoli
 * 
 * Traccia dove si trova FISICAMENTE l'articolo in questo momento
 * Può essere diverso dalla sede del magazzino (movimentazioni)
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
        Schema::table('articoli', function (Blueprint $table) {
            $table->foreignId('sede_id')
                  ->nullable() // Nullable per migrazione graduale
                  ->after('magazzino_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede fisica corrente dell\'articolo');
            
            $table->index('sede_id', 'idx_articoli_sede');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropIndex('idx_articoli_sede');
            $table->dropColumn('sede_id');
        });
    }
};

