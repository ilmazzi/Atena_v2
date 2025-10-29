<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Aggiunge societa_id a sedi
 * 
 * Ogni sede appartiene a una società
 * - De Pascalis s.p.a. (ID: 1): Cavour, Jolly, Mazzini, Monastero
 * - Luigi De Pascalis (ID: 2): Roma
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verifica se la colonna esiste già (per migrazioni parzialmente eseguite)
        $hasColumn = Schema::hasColumn('sedi', 'societa_id');
        
        // Step 1: Aggiungi colonna nullable SENZA foreign key iniziale (solo se non esiste)
        if (!$hasColumn) {
            Schema::table('sedi', function (Blueprint $table) {
                $table->unsignedBigInteger('societa_id')
                      ->nullable()
                      ->after('tipo')
                      ->comment('Società di appartenenza della sede');
                
                $table->index('societa_id', 'idx_sedi_societa');
            });
        }
        
        // ═══════════════════════════════════════════════════════════
        // MAPPING SEDI → SOCIETÀ
        // ═══════════════════════════════════════════════════════════
        // Assumendo che le società siano state create dal seeder precedente
        // De Pascalis s.p.a. = ID 1, Luigi De Pascalis = ID 2
        
        // Mapping basato su nomi sedi (se codice cambia, verificare)
        DB::table('sedi')
            ->whereIn('codice', ['CAV', 'JOL', 'MAZ', 'MON']) // Cavour, Jolly, Mazzini, Monastero
            ->update(['societa_id' => 1]); // De Pascalis s.p.a.
        
        DB::table('sedi')
            ->where('codice', 'ROM') // Roma
            ->update(['societa_id' => 2]); // Luigi De Pascalis
        
        // Step 2: Droppa eventuali foreign key esistenti (se presenti)
        // Necessario per poter modificare la colonna
        try {
            Schema::table('sedi', function (Blueprint $table) {
                $table->dropForeign(['societa_id']);
            });
        } catch (\Exception $e) {
            // Foreign key non esiste, continua (ok se la migrazione è nuova)
        }
        
        // Step 3: Modifica colonna da nullable a NOT NULL
        Schema::table('sedi', function (Blueprint $table) {
            $table->unsignedBigInteger('societa_id')->nullable(false)->change();
        });
        
        // Step 4: Aggiungi foreign key constraint
        Schema::table('sedi', function (Blueprint $table) {
            $table->foreign('societa_id')
                  ->references('id')
                  ->on('societa')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('sedi', function (Blueprint $table) {
            $table->dropForeign(['societa_id']);
            $table->dropIndex('idx_sedi_societa');
            $table->dropColumn('societa_id');
        });
    }
};
