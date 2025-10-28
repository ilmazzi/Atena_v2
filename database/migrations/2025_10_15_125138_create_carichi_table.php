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
        Schema::create('carichi', function (Blueprint $table) {
            $table->id();
            
            // Tipo carico
            $table->enum('tipo', ['manuale', 'ocr'])->default('manuale')->comment('Tipo di carico: manuale o da OCR');
            
            // Riferimenti documento OCR (se carico da OCR)
            $table->foreignId('ocr_document_id')->nullable()->constrained('ocr_documents')->onDelete('set null');
            
            // Riferimenti fornitore
            $table->foreignId('fornitore_id')->nullable()->constrained('fornitori')->onDelete('set null');
            
            // Dati documento
            $table->string('numero_documento', 50)->nullable()->comment('Numero DDT o Fattura');
            $table->date('data_documento')->nullable()->comment('Data DDT o Fattura');
            $table->enum('tipo_documento', ['ddt', 'fattura'])->default('ddt');
            
            // Importi (opzionali, più rilevanti per fatture)
            $table->decimal('importo_totale', 10, 2)->nullable();
            $table->string('partita_iva', 20)->nullable();
            
            // Quantità e articoli
            $table->integer('quantita_totale')->default(0)->comment('Numero totale pezzi caricati');
            $table->integer('numero_articoli')->default(0)->comment('Numero righe articoli');
            
            // Stato carico
            $table->enum('stato', ['bozza', 'validato', 'completato', 'annullato'])->default('bozza');
            
            // Utente che ha fatto il carico
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Note
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('tipo');
            $table->index('tipo_documento');
            $table->index('stato');
            $table->index('data_documento');
            $table->index('fornitore_id');
            $table->index('created_at');
        });
        
        // Tabella dettagli carico (righe articoli)
        Schema::create('carico_dettagli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carico_id')->constrained('carichi')->onDelete('cascade');
            
            // Riferimento articolo (nullable se l'articolo non esiste ancora)
            // FK rimossa: mag_articoli non esiste in sviluppo, sarà aggiunta in produzione
            $table->unsignedBigInteger('articolo_id')->nullable();
            
            // Dati estratti (da OCR o inseriti manualmente)
            $table->string('codice_articolo', 100)->comment('Codice articolo estratto');
            $table->text('descrizione')->nullable();
            $table->integer('quantita')->default(1);
            $table->string('numero_seriale', 50)->nullable();
            $table->string('ean', 20)->nullable();
            
            // Prezzi (opzionali)
            $table->decimal('prezzo_unitario', 10, 2)->nullable();
            $table->decimal('prezzo_totale', 10, 2)->nullable();
            
            // Stato riga
            $table->boolean('verificato')->default(false)->comment('Articolo verificato dall\'utente');
            $table->boolean('creato_nuovo')->default(false)->comment('È stato creato un nuovo articolo');
            
            $table->timestamps();
            
            // Indici
            $table->index('carico_id');
            $table->index('articolo_id');
            $table->index('codice_articolo');
            $table->index('verificato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carico_dettagli');
        Schema::dropIfExists('carichi');
    }
};
