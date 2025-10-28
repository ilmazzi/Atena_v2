<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella articoli ottimizzata
 * 
 * Parte del refactor DDD - Schema database ottimizzato
 * Sostituisce: mag_articoli (vecchio schema)
 * 
 * Dominio: Magazzino (Core)
 * ADR: 004-database-schema-refactoring
 * 
 * ⚠️ REQUISITO CRITICO: NO data_scarico, NO data_vendita
 *    Solo stato ENUM e flag booleani consentiti.
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
        Schema::create('articoli', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // IDENTIFICAZIONE
            // ═══════════════════════════════════════════════════════════
            $table->string('codice', 100)->unique()->comment('Barcode/QR code univoco');
            $table->string('descrizione', 500)->comment('Descrizione articolo');
            $table->text('descrizione_estesa')->nullable()->comment('Dettagli estesi');
            
            // ═══════════════════════════════════════════════════════════
            // FOREIGN KEYS
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('magazzino_id')
                  ->constrained('magazzini')
                  ->restrictOnDelete()
                  ->comment('Magazzino corrente');
            
            $table->foreignId('fornitore_id')
                  ->nullable()
                  ->constrained('fornitori')
                  ->nullOnDelete()
                  ->comment('Fornitore originale');
            
            // ═══════════════════════════════════════════════════════════
            // DATI TECNICI
            // ═══════════════════════════════════════════════════════════
            $table->decimal('peso_lordo', 10, 2)->nullable()->comment('Peso lordo in grammi');
            $table->decimal('peso_netto', 10, 2)->nullable()->comment('Peso netto in grammi');
            $table->string('titolo', 50)->nullable()->comment('Titolo metallo (es: 750‰)');
            $table->string('caratura', 50)->nullable()->comment('Caratura (es: 18kt)');
            $table->string('materiale', 100)->nullable()->comment('Materiale principale');
            $table->string('colore', 100)->nullable()->comment('Colore');
            
            // ═══════════════════════════════════════════════════════════
            // PREZZI
            // ═══════════════════════════════════════════════════════════
            // ⚠️ REQUISITO CLIENTE: Solo prezzo acquisto consentito!
            // ❌ NO prezzo_vendita (va solo su etichette stampate)
            // ❌ NO margine_percentuale
            $table->decimal('prezzo_acquisto', 10, 2)->nullable()->comment('Prezzo acquisto (unico prezzo salvato)');
            
            // ═══════════════════════════════════════════════════════════
            // STATO ARTICOLO
            // ═══════════════════════════════════════════════════════════
            // ⚠️ NOTA CRITICA: Solo STATO, MAI data_scarico!
            $table->enum('stato', [
                'disponibile',      // In magazzino, disponibile
                'riservato',        // Riservato per cliente
                'venduto',          // Venduto (NO data vendita!)
                'in_lavorazione',   // In riparazione/modifica
                'in_deposito',      // In conto deposito
                'danneggiato',      // Danneggiato
                'in_transito'       // In movimentazione
            ])->default('disponibile')->comment('Stato corrente articolo - NO date scarico!');
            
            // ═══════════════════════════════════════════════════════════
            // ORIGINE/CARICO
            // ═══════════════════════════════════════════════════════════
            $table->enum('tipo_carico', ['ddt', 'fattura', 'manuale', 'produzione_interna'])
                  ->nullable()
                  ->comment('Tipo documento carico');
            
            $table->string('numero_documento_carico', 100)
                  ->nullable()
                  ->comment('Numero DDT/Fattura carico');
            
            $table->date('data_carico')
                  ->nullable()
                  ->comment('✅ Data CARICO in magazzino (ingresso) - consentita');
            
            // ❌ VIETATO: data_scarico, data_vendita, scaricato_at, venduto_at
            // ✅ SOLO flag booleani consentiti:
            
            // ═══════════════════════════════════════════════════════════
            // FLAGS (NO DATE!)
            // ═══════════════════════════════════════════════════════════
            $table->boolean('in_vetrina')->default(false)->comment('Articolo esposto in vetrina');
            $table->boolean('inventariato')->default(false)->comment('Verificato in ultimo inventario');
            $table->boolean('visibile_catalogo')->default(true)->comment('Visibile in catalogo');
            
            // ═══════════════════════════════════════════════════════════
            // METADATA
            // ═══════════════════════════════════════════════════════════
            $table->text('note')->nullable();
            $table->string('foto_principale', 255)->nullable()->comment('Path foto principale');
            $table->json('foto_aggiuntive')->nullable()->comment('Array paths foto aggiuntive');
            $table->json('caratteristiche')->nullable()->comment('Dati aggiuntivi specifici (JSON)');
            
            // ═══════════════════════════════════════════════════════════
            // TIMESTAMPS (Laravel Standard)
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();      // created_at, updated_at
            $table->softDeletes();     // deleted_at
            
            // ═══════════════════════════════════════════════════════════
            // INDICI OTTIMIZZATI
            // ═══════════════════════════════════════════════════════════
            
            // Single column indices
            $table->index('magazzino_id', 'idx_articoli_magazzino');
            $table->index('fornitore_id', 'idx_articoli_fornitore');
            $table->index('stato', 'idx_articoli_stato');
            $table->index('in_vetrina', 'idx_articoli_vetrina');
            $table->index('data_carico', 'idx_articoli_data_carico');
            
            // Composite indices (query comuni)
            $table->index(['magazzino_id', 'stato'], 'idx_articoli_mag_stato');
            $table->index(['magazzino_id', 'in_vetrina'], 'idx_articoli_mag_vetrina');
            $table->index(['tipo_carico', 'numero_documento_carico'], 'idx_articoli_carico_doc');
            $table->index(['stato', 'in_vetrina', 'visibile_catalogo'], 'idx_articoli_display');
            
            // Fulltext search (per autocomplete e ricerche)
            $table->fullText(['codice', 'descrizione'], 'idx_articoli_fulltext');
        });
        
        // Commento tabella
        DB::statement("ALTER TABLE articoli COMMENT = 'Articoli magazzino - Core Domain - NO data_scarico (requisito cliente)'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articoli');
    }
};

