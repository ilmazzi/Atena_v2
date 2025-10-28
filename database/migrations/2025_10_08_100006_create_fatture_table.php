<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabelle Fatture
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_ddt_articoli_testate, mag_ddt_articoli_dettagli (SEPARATO da DDT!)
 * 
 * Dominio: Fatturazione
 * ADR: 004-database-schema-refactoring
 * 
 * IMPORTANTE: Ora separate da DDT (prima erano nella stessa tabella!)
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // FATTURE
        // ═══════════════════════════════════════════════════════════
        Schema::create('fatture', function (Blueprint $table) {
            $table->id();
            
            // Identificazione documento
            $table->string('numero', 50)->comment('Numero fattura');
            $table->date('data_documento')->comment('Data emissione fattura');
            $table->date('data_scadenza')->nullable()->comment('Data scadenza pagamento');
            $table->integer('anno')->comment('Anno documento');
            
            // Fornitore
            $table->foreignId('fornitore_id')
                  ->constrained('fornitori')
                  ->restrictOnDelete()
                  ->comment('Fornitore emittente');
            
            // Importi
            $table->decimal('imponibile', 10, 2)->default(0)->comment('Totale imponibile');
            $table->decimal('iva', 10, 2)->default(0)->comment('Totale IVA');
            $table->decimal('totale', 10, 2)->default(0)->comment('Totale fattura');
            $table->string('valuta', 3)->default('EUR');
            
            // Riferimenti
            $table->string('numero_ordine', 50)->nullable()->comment('Numero ordine di riferimento');
            $table->text('riferimenti')->nullable()->comment('Altri riferimenti');
            
            // Status
            $table->enum('stato', [
                'bozza',              // In creazione
                'emessa',             // Fattura emessa
                'ricevuta',           // Fattura ricevuta
                'caricata',           // Articoli caricati in magazzino
                'parzialmente_caricata',  // Solo alcuni articoli
                'pagata',             // Fattura pagata
                'annullata'           // Annullata
            ])->default('bozza');
            
            $table->date('data_carico')->nullable()->comment('Data carico articoli in magazzino');
            
            // Pagamento
            $table->enum('metodo_pagamento', [
                'bonifico', 'contanti', 'assegno', 'carta_credito', 
                'rimessa_diretta', 'altro'
            ])->nullable();
            
            $table->date('data_pagamento')->nullable();
            $table->decimal('importo_pagato', 10, 2)->nullable();
            
            // Magazzino destinazione
            $table->foreignId('magazzino_destinazione_id')
                  ->nullable()
                  ->constrained('magazzini')
                  ->restrictOnDelete();
            
            // File
            $table->string('file_pdf_path', 255)->nullable()->comment('Path PDF fattura originale');
            $table->string('file_xml_path', 255)->nullable()->comment('Path XML fattura elettronica');
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->unique(['numero', 'fornitore_id', 'anno'], 'idx_fatture_unique');
            $table->index('fornitore_id', 'idx_fatture_fornitore');
            $table->index('data_documento', 'idx_fatture_data');
            $table->index('data_scadenza', 'idx_fatture_scadenza');
            $table->index('stato', 'idx_fatture_stato');
            $table->index('data_pagamento', 'idx_fatture_pagamento');
            $table->index(['fornitore_id', 'stato'], 'idx_fatture_forn_stato');
        });
        
        // ═══════════════════════════════════════════════════════════
        // FATTURE DETTAGLI
        // ═══════════════════════════════════════════════════════════
        Schema::create('fatture_dettagli', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('fattura_id')
                  ->constrained('fatture')
                  ->cascadeOnDelete();
            
            $table->foreignId('articolo_id')
                  ->nullable()
                  ->constrained('articoli')
                  ->nullOnDelete()
                  ->comment('Articolo caricato (NULL se non ancora)');
            
            // Dati riga fattura
            $table->string('codice_articolo', 100)->nullable()->comment('Codice da fattura');
            $table->string('descrizione', 500)->comment('Descrizione da fattura');
            $table->integer('quantita')->default(1);
            $table->decimal('prezzo_unitario', 10, 2)->comment('Prezzo unitario');
            $table->decimal('sconto_percentuale', 5, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22.00);
            $table->decimal('totale_riga', 10, 2)->comment('Totale riga');
            
            // Status
            $table->boolean('caricato')->default(false);
            $table->date('data_carico_riga')->nullable();
            
            // Metadata
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            
            // Indici
            $table->index('fattura_id', 'idx_fatt_det_fattura');
            $table->index('articolo_id', 'idx_fatt_det_articolo');
            $table->index('caricato', 'idx_fatt_det_caricato');
        });
        
        DB::statement("ALTER TABLE fatture COMMENT = 'Fatture Fornitori - Dominio: Fatturazione (SEPARATE da DDT)'");
        DB::statement("ALTER TABLE fatture_dettagli COMMENT = 'Dettagli Fatture - Dominio: Fatturazione'");
    }

    public function down()
    {
        Schema::dropIfExists('fatture_dettagli');
        Schema::dropIfExists('fatture');
    }
};

