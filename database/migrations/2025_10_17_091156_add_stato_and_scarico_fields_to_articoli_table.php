<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articoli', function (Blueprint $table) {
            // Aggiungi campo stato (SENZA data_scarico)
            $table->enum('stato_articolo', [
                'disponibile',
                'in_prodotto_finito',
                'scaricato',
                'scaricato_in_pf'
            ])->default('disponibile')->after('stato');
            
            // Campo per collegare a eventuale scarico (opzionale, per tracciabilità interna)
            $table->unsignedBigInteger('scarico_id')->nullable()->after('stato_articolo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropColumn(['stato_articolo', 'scarico_id']);
        });
    }
};
