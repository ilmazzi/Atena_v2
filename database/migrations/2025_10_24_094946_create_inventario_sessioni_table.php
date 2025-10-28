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
        Schema::create('inventario_sessioni', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Nome della sessione inventario
            $table->foreignId('sede_id')->constrained('sedi');
            $table->json('categorie_permesse')->nullable(); // Categorie da inventariare
            $table->timestamp('data_inizio');
            $table->timestamp('data_fine')->nullable();
            $table->enum('stato', ['attiva', 'chiusa', 'annullata'])->default('attiva');
            $table->foreignId('utente_id')->constrained('users');
            $table->text('note')->nullable();
            
            // Statistiche sessione
            $table->integer('articoli_totali')->default(0); // Totale articoli da inventariare
            $table->integer('articoli_trovati')->default(0); // Articoli trovati durante scansione
            $table->integer('articoli_eliminati')->default(0); // Articoli eliminati (non trovati)
            $table->decimal('valore_eliminato', 10, 2)->default(0); // Valore totale articoli eliminati
            
            $table->timestamps();
            
            // Indici per performance
            $table->index('stato');
            $table->index('sede_id');
            $table->index('data_inizio');
            $table->index('utente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_sessioni');
    }
};