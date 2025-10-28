<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Refactor giacenze per usare ubicazione_id
 * 
 * Sostituisce i campi testuali (scaffale, box, posizione)
 * con FK a tabella ubicazioni normalizzata
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
            // Aggiungi nuova colonna ubicazione_id
            $table->foreignId('ubicazione_id')
                  ->nullable() // Nullable per migrazione graduale
                  ->after('magazzino_id')
                  ->constrained('ubicazioni')
                  ->nullOnDelete() // Se ubicazione eliminata, set NULL (non bloccare)
                  ->comment('Ubicazione fisica specifica (scaffale/box)');
            
            $table->index('ubicazione_id', 'idx_giacenze_ubicazione');
        });
        
        // Nota: NON droppiamo subito scaffale/box/posizione
        // Lo faremo dopo la migrazione dati completa
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giacenze', function (Blueprint $table) {
            $table->dropForeign(['ubicazione_id']);
            $table->dropIndex('idx_giacenze_ubicazione');
            $table->dropColumn('ubicazione_id');
        });
    }
};

