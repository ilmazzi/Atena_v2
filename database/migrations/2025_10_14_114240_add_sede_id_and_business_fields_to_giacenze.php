<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add sede_id and business fields to giacenze
 * 
 * AGGIUNGE:
 * 1. sede_id - Ubicazione fisica della giacenza (FK a sedi)
 * 2. quantita_residua - Quantità disponibile (decrementata da vendite)
 * 3. quantita_deposito - Quantità in conto deposito
 * 4. costo_unitario - Costo unitario acquisto
 * 
 * BUSINESS RULES (da memoria):
 * - qta = quantità originale carico (immutabile)
 * - qta_residua = disponibile (decrementata da scarichi)
 * - qta_deposito = in conto deposito
 * - Lo scarico decrementa SOLO qta_residua, NON salva data_scarico
 * 
 * COMPLIANCE:
 * - NO data_scarico nel DB (requisito cliente)
 * - Tracciamento via quantità, non date
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
        Schema::table('giacenze', function (Blueprint $table) {
            // ═══════════════════════════════════════════════════════════
            // SEDE FISICA
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('sede_id')
                  ->nullable() // Nullable durante migrazione
                  ->after('categoria_merceologica_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede fisica dove si trova la giacenza');
            
            // ═══════════════════════════════════════════════════════════
            // BUSINESS FIELDS (da memoria sistema)
            // ═══════════════════════════════════════════════════════════
            
            // quantita_residua: Disponibile dopo scarichi
            $table->integer('quantita_residua')
                  ->default(0)
                  ->after('quantita')
                  ->comment('Quantità disponibile (decrementata da vendite)');
            
            // quantita_deposito: In conto deposito
            $table->integer('quantita_deposito')
                  ->default(0)
                  ->after('quantita_residua')
                  ->comment('Quantità in conto deposito');
            
            // costo_unitario: Prezzo acquisto per unità
            $table->decimal('costo_unitario', 10, 2)
                  ->nullable()
                  ->after('quantita_deposito')
                  ->comment('Costo unitario acquisto');
            
            // ═══════════════════════════════════════════════════════════
            // INDICI
            // ═══════════════════════════════════════════════════════════
            $table->index('sede_id', 'idx_giacenze_sede');
            $table->index('quantita_residua', 'idx_giacenze_qta_residua');
        });
        
        // ═══════════════════════════════════════════════════════════
        // DATA MIGRATION: Inizializza quantita_residua = quantita
        // ═══════════════════════════════════════════════════════════
        DB::statement('UPDATE giacenze SET quantita_residua = quantita WHERE quantita_residua = 0');
        
        // Commento tabella aggiornato
        DB::statement("ALTER TABLE giacenze COMMENT = 'Giacenze articoli - 1:N con Articolo - Sede fisica + Business fields'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giacenze', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('uk_giacenza_articolo_sede');
            
            // Drop indices
            $table->dropIndex('idx_giacenze_sede');
            $table->dropIndex('idx_giacenze_qta_residua');
            
            // Drop foreign key
            $table->dropForeign(['sede_id']);
            
            // Drop columns
            $table->dropColumn([
                'sede_id',
                'quantita_residua',
                'quantita_deposito',
                'costo_unitario'
            ]);
        });
    }
};
