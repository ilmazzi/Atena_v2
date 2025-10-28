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
        Schema::create('articoli_storico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('articolo_id_originale'); // ID originale dell'articolo
            $table->string('codice'); // Codice articolo per ricerca
            $table->string('descrizione');
            $table->json('dati_completi'); // Tutti i dati dell'articolo al momento eliminazione
            $table->json('relazioni_storico'); // Giacenze, DDT, relazioni al momento eliminazione
            $table->string('motivo_eliminazione'); // inventario, vendita, etc.
            $table->unsignedBigInteger('sessione_inventario_id')->nullable();
            $table->foreignId('utente_id')->constrained('users');
            $table->timestamp('data_eliminazione');
            $table->timestamps();
            
            // Indici per performance
            $table->index('articolo_id_originale');
            $table->index('codice');
            $table->index('data_eliminazione');
            $table->index('motivo_eliminazione');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articoli_storico');
    }
};