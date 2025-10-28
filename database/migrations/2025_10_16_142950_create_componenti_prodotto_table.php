<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella Componenti Prodotto
 * 
 * Traccia i componenti (articoli) utilizzati per assemblare un prodotto finito
 * Ogni riga rappresenta un componente specifico con quantità e costi al momento dell'assemblaggio
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('componenti_prodotto', function (Blueprint $table) {
            $table->id();
            
            // Relazione con prodotto finito
            $table->foreignId('prodotto_finito_id')
                  ->constrained('prodotti_finiti')
                  ->cascadeOnDelete()
                  ->comment('Prodotto finito di appartenenza');
            
            // Relazione con articolo componente
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->restrictOnDelete()
                  ->comment('Articolo usato come componente');
            
            // Dati componente
            $table->integer('quantita')
                  ->default(1)
                  ->comment('Quantità di questo componente utilizzata');
            
            $table->decimal('costo_unitario', 10, 2)
                  ->nullable()
                  ->comment('Costo unitario componente al momento assemblaggio');
            
            $table->decimal('costo_totale', 10, 2)
                  ->nullable()
                  ->comment('Costo totale = quantita * costo_unitario');
            
            // Tracking prelievo/utilizzo
            $table->enum('stato', [
                'prelevato',    // Componente prelevato dal magazzino
                'utilizzato',   // Componente utilizzato nell'assemblaggio
                'restituito',   // Componente restituito (assemblaggio annullato)
                'scartato'      // Componente danneggiato/scartato
            ])->default('utilizzato');
            
            $table->timestamp('prelevato_il')->nullable();
            $table->foreignId('prelevato_da')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indici per performance
            $table->index('prodotto_finito_id', 'idx_comp_prodotto');
            $table->index('articolo_id', 'idx_comp_articolo');
            $table->index('stato', 'idx_comp_stato');
            
            // Unique constraint: stesso articolo non può essere aggiunto 2 volte allo stesso prodotto
            // Se serve 2 pezzi dello stesso articolo, si aumenta la quantità
            $table->unique(['prodotto_finito_id', 'articolo_id'], 'uq_prodotto_articolo');
        });
        
        DB::statement("ALTER TABLE componenti_prodotto COMMENT = 'Componenti dei Prodotti Finiti - Distinta Base'");
    }

    public function down()
    {
        Schema::dropIfExists('componenti_prodotto');
    }
};
