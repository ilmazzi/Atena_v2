<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Rinomina tabella magazzini → categorie_merceologiche
 * 
 * Per chiarezza concettuale:
 * - MAGAZZINO = Luogo fisico (CAVOUR, JOLLY, MONASTERO...)
 * - CATEGORIA MERCEOLOGICA = Tipo prodotto (Sveglie, Orologi, Gioielli...)
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
        // Step 1: Rinomina la tabella
        Schema::rename('magazzini', 'categorie_merceologiche');
        
        // Step 2: Aggiungi nuove colonne
        Schema::table('articoli', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_merceologica_id')->nullable()->after('id');
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_merceologica_id')->nullable()->after('articolo_id');
        });
        
        // Step 3: Copia i dati dalle vecchie colonne
        DB::statement('UPDATE articoli SET categoria_merceologica_id = magazzino_id WHERE magazzino_id IS NOT NULL');
        DB::statement('UPDATE giacenze SET categoria_merceologica_id = magazzino_id WHERE magazzino_id IS NOT NULL');
        
        // Step 4: Crea le nuove foreign key
        Schema::table('articoli', function (Blueprint $table) {
            $table->foreign('categoria_merceologica_id')
                  ->references('id')
                  ->on('categorie_merceologiche')
                  ->restrictOnDelete();
            $table->index('categoria_merceologica_id', 'idx_articoli_categoria');
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->foreign('categoria_merceologica_id')
                  ->references('id')
                  ->on('categorie_merceologiche')
                  ->restrictOnDelete();
            $table->index('categoria_merceologica_id', 'idx_giacenze_categoria');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Ripristina gli indici
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropIndex('idx_articoli_categoria');
            $table->index('categoria_merceologica_id', 'idx_articoli_magazzino');
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->dropIndex('idx_giacenze_categoria');
            $table->index('categoria_merceologica_id', 'idx_giacenze_magazzino');
        });
        
        // Rimuovi le foreign key
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropForeign(['categoria_merceologica_id']);
            $table->renameColumn('categoria_merceologica_id', 'magazzino_id');
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->dropForeign(['categoria_merceologica_id']);
            $table->renameColumn('categoria_merceologica_id', 'magazzino_id');
        });
        
        // Ricrea le foreign key originali
        Schema::table('articoli', function (Blueprint $table) {
            $table->foreign('magazzino_id')
                  ->references('id')
                  ->on('magazzini')
                  ->restrictOnDelete();
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->foreign('magazzino_id')
                  ->references('id')
                  ->on('magazzini')
                  ->restrictOnDelete();
        });
        
        // Ripristina il nome della tabella
        Schema::rename('categorie_merceologiche', 'magazzini');
    }
};
