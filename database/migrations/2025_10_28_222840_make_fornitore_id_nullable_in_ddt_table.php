<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Correzione DDT per Conti Deposito
 * 
 * Problema: Il campo fornitore_id è NOT NULL ma per i DDT di trasferimento 
 * deposito dovrebbe essere nullable (è un trasferimento interno)
 * 
 * Soluzioni:
 * 1. Rende nullable il campo fornitore_id
 * 2. Aggiunge campo tipo_documento per distinguere DDT normali da trasferimenti
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ddt', function (Blueprint $table) {
            // 1. Rimuovi il foreign key constraint temporaneamente
            $table->dropForeign(['fornitore_id']);
            
            // 2. Modifica la colonna per renderla nullable
            $table->unsignedBigInteger('fornitore_id')->nullable()->change();
            
            // 3. Aggiungi campo tipo_documento se non esiste già
            if (!Schema::hasColumn('ddt', 'tipo_documento')) {
                $table->enum('tipo_documento', [
                    'fornitore',              // DDT normale da fornitore
                    'trasferimento_deposito', // DDT trasferimento conto deposito
                    'reso_deposito'          // DDT reso conto deposito
                ])->default('fornitore')->after('anno');
            }
            
            // 4. Ricrea il foreign key constraint con nullable
            $table->foreign('fornitore_id')
                  ->references('id')
                  ->on('fornitori')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ddt', function (Blueprint $table) {
            // 1. Rimuovi foreign key
            $table->dropForeign(['fornitore_id']);
            
            // 2ripristin campo NOT NULL (attenzione: potrebbe fallire se ci sono record con NULL)
            $table->unsignedBigInteger('fornitore_id')->nullable(false)->change();
            
            // 3. Rimuovi campo tipo_documento se esiste
            if (Schema::hasColumn('ddt', 'tipo_documento')) {
                $table->dropColumn('tipo_documento');
            }
            
            // 4. Ricrea foreign key NOT NULL
            $table->foreign('fornitore_id')
                  ->references('id')
                  ->on('fornitori')
                  ->restrictOnDelete();
        });
    }
};
