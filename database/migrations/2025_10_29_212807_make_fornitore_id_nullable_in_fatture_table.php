<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rendere fornitore_id nullable per permettere vendite (non da fornitore)
     */
    public function up()
    {
        Schema::table('fatture', function (Blueprint $table) {
            // Rimuovi il foreign key constraint prima di modificare la colonna
            $table->dropForeign(['fornitore_id']);
        });
        
        Schema::table('fatture', function (Blueprint $table) {
            // Rendi fornitore_id nullable
            $table->unsignedBigInteger('fornitore_id')->nullable()->change();
        });
        
        Schema::table('fatture', function (Blueprint $table) {
            // Re-inserisci il foreign key constraint
            $table->foreign('fornitore_id')
                  ->references('id')
                  ->on('fornitori')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('fatture', function (Blueprint $table) {
            // Rimuovi il foreign key constraint
            $table->dropForeign(['fornitore_id']);
        });
        
        Schema::table('fatture', function (Blueprint $table) {
            // Rendi fornitore_id NOT NULL (attenzione: potrebbe fallire se ci sono record con NULL)
            $table->unsignedBigInteger('fornitore_id')->nullable(false)->change();
        });
        
        Schema::table('fatture', function (Blueprint $table) {
            // Re-inserisci il foreign key constraint
            $table->foreign('fornitore_id')
                  ->references('id')
                  ->on('fornitori')
                  ->restrictOnDelete();
        });
    }
};
