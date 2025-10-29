<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabella per storico costi articoli (tracciamento cambi di prezzo acquisto)
     */
    public function up()
    {
        Schema::create('articolo_storico_costi', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('articolo_id')
                  ->constrained('articoli')
                  ->cascadeOnDelete();
            
            $table->decimal('costo_precedente', 10, 2)->nullable()
                  ->comment('Costo prima della modifica');
            
            $table->decimal('costo_nuovo', 10, 2)
                  ->comment('Nuovo costo');
            
            $table->foreignId('fattura_id')
                  ->nullable()
                  ->constrained('fatture')
                  ->nullOnDelete()
                  ->comment('Fattura di riferimento (se associata)');
            
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha modificato');
            
            $table->text('note')->nullable()
                  ->comment('Note sulla modifica');
            
            $table->timestamps();
            
            $table->index('articolo_id', 'idx_storico_costi_articolo');
            $table->index('created_at', 'idx_storico_costi_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('articolo_storico_costi');
    }
};

