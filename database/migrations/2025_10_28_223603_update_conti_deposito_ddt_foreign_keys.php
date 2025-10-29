<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Aggiorna foreign key da ddt a ddt_depositi
 * 
 * Le foreign key in conti_deposito devono puntare a ddt_depositi
 * invece che a ddt (tabella diversa)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica che la tabella ddt_depositi esista prima di procedere
        if (!Schema::hasTable('ddt_depositi')) {
            throw new \Exception('La tabella ddt_depositi non esiste. Eseguire prima la migrazione per creare ddt_depositi.');
        }

        // Ottieni i nomi reali delle foreign key dal database
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'conti_deposito' 
            AND REFERENCED_TABLE_NAME = 'ddt'
            AND COLUMN_NAME IN ('ddt_invio_id', 'ddt_reso_id', 'ddt_rimando_id')
        ");

        // Rimuovi vecchie foreign key (usa i nomi reali dal database)
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE conti_deposito DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Ignora errori se la foreign key non esiste
                \Log::warning("Errore rimozione FK {$fk->CONSTRAINT_NAME}: " . $e->getMessage());
            }
        }

        // Aggiungi nuove foreign key verso ddt_depositi usando SQL diretto
        try {
            DB::statement('ALTER TABLE conti_deposito ADD CONSTRAINT conti_deposito_ddt_invio_id_foreign FOREIGN KEY (ddt_invio_id) REFERENCES ddt_depositi(id) ON DELETE SET NULL');
        } catch (\Exception $e) {
            \Log::warning("Errore creazione FK ddt_invio_id: " . $e->getMessage());
        }

        try {
            DB::statement('ALTER TABLE conti_deposito ADD CONSTRAINT conti_deposito_ddt_reso_id_foreign FOREIGN KEY (ddt_reso_id) REFERENCES ddt_depositi(id) ON DELETE SET NULL');
        } catch (\Exception $e) {
            \Log::warning("Errore creazione FK ddt_reso_id: " . $e->getMessage());
        }

        try {
            DB::statement('ALTER TABLE conti_deposito ADD CONSTRAINT conti_deposito_ddt_rimando_id_foreign FOREIGN KEY (ddt_rimando_id) REFERENCES ddt_depositi(id) ON DELETE SET NULL');
        } catch (\Exception $e) {
            \Log::warning("Errore creazione FK ddt_rimando_id: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conti_deposito', function (Blueprint $table) {
            // Rimuovi foreign key verso ddt_depositi
            $table->dropForeign(['ddt_invio_id']);
            $table->dropForeign(['ddt_reso_id']);
            $table->dropForeign(['ddt_rimando_id']);
        });

        Schema::table('conti_deposito', function (Blueprint $table) {
            // Ripristina foreign key verso ddt
            $table->foreign('ddt_invio_id')
                  ->references('id')
                  ->on('ddt')
                  ->onDelete('set null')
                  ->change();
            
            $table->foreign('ddt_reso_id')
                  ->references('id')
                  ->on('ddt')
                  ->onDelete('set null')
                  ->change();
            
            $table->foreign('ddt_rimando_id')
                  ->references('id')
                  ->on('ddt')
                  ->onDelete('set null')
                  ->change();
        });
    }
};
