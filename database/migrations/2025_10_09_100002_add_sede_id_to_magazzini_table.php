<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Aggiunge sede_id a tabella magazzini
 * 
 * Collega ogni magazzino (categoria merceologica) alla sua sede fisica
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
        Schema::table('magazzini', function (Blueprint $table) {
            $table->foreignId('sede_id')
                  ->nullable() // Nullable per ora, per migrazione graduale
                  ->after('id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede fisica principale del magazzino');
            
            $table->index('sede_id', 'idx_magazzini_sede');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('magazzini', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropIndex('idx_magazzini_sede');
            $table->dropColumn('sede_id');
        });
    }
};

