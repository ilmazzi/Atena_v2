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
        Schema::create('inventario_scansioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessione_id')->constrained('inventario_sessioni');
            $table->foreignId('articolo_id')->constrained('articoli');
            $table->enum('azione', ['trovato', 'eliminato']); // Azione eseguita sull'articolo
            $table->integer('quantita_trovata')->nullable(); // Quantità trovata durante scansione
            $table->integer('quantita_sistema'); // Quantità nel sistema
            $table->integer('differenza')->default(0); // Differenza tra trovato e sistema
            $table->text('note')->nullable(); // Note aggiuntive
            $table->timestamp('data_scansione');
            $table->timestamps();
            
            // Indici per performance
            $table->index('sessione_id');
            $table->index('articolo_id');
            $table->index('azione');
            $table->index('data_scansione');
            
            // Vincolo unico: un articolo può essere scansionato una sola volta per sessione
            $table->unique(['sessione_id', 'articolo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_scansioni');
    }
};