<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Rimuove le vecchie colonne magazzino_id
 * 
 * Ora che abbiamo categoria_merceologica_id, possiamo rimuovere le vecchie colonne
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
        // Rimuovi vecchie foreign key e colonne
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropForeign(['magazzino_id']);
            $table->dropColumn('magazzino_id');
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->dropForeign(['magazzino_id']);
            $table->dropColumn('magazzino_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Ricrea le vecchie colonne
        Schema::table('articoli', function (Blueprint $table) {
            $table->unsignedBigInteger('magazzino_id')->nullable();
            $table->foreign('magazzino_id')
                  ->references('id')
                  ->on('categorie_merceologiche')
                  ->restrictOnDelete();
        });
        
        Schema::table('giacenze', function (Blueprint $table) {
            $table->unsignedBigInteger('magazzino_id')->nullable();
            $table->foreign('magazzino_id')
                  ->references('id')
                  ->on('categorie_merceologiche')
                  ->restrictOnDelete();
        });
    }
};
