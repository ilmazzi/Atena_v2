<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Aggiornamento tabella prodotti_finiti
 * 
 * Aggiunge campi per tracking completo e user-friendly
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('prodotti_finiti', function (Blueprint $table) {
            // Campi già esistenti nella migration originale:
            // - id, codice, descrizione, tipologia, magazzino_id
            // - peso_totale, materiale_principale, caratura
            // - componenti (JSON), lavorazioni (JSON)
            // - costo_materiali, costo_lavorazione, costo_totale
            // - stato, data_inizio_lavorazione, data_completamento
            // - note, foto_path
            // - timestamps, soft_deletes
            
            // NUOVI CAMPI per robustezza:
            
            // User tracking
            $table->foreignId('creato_da')
                  ->after('note')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha creato il prodotto finito');
            
            $table->foreignId('assemblato_da')
                  ->after('creato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha assemblato il prodotto');
            
            // Articolo risultante (l'articolo finale in magazzino)
            $table->foreignId('articolo_risultante_id')
                  ->after('assemblato_da')
                  ->nullable()
                  ->constrained('articoli')
                  ->nullOnDelete()
                  ->comment('Articolo finale creato in magazzino categoria 9');
            
            // Dati gioielleria (calcolati automaticamente dai componenti)
            $table->string('oro_totale', 100)->nullable()->after('caratura');
            $table->string('brillanti_totali', 100)->nullable()->after('oro_totale');
            $table->string('pietre_totali', 100)->nullable()->after('brillanti_totali');
            
            // Prezzo vendita (solo su etichetta, non in DB come richiesto)
            // Ma serve tracciare se è stato venduto
            $table->timestamp('venduto_il')->nullable()->after('data_completamento');
            $table->foreignId('venduto_a_cliente_id')
                  ->nullable()
                  ->after('venduto_il')
                  ->comment('Cliente che ha acquistato (se implementato CRM)');
            
            // Indici aggiuntivi
            $table->index('creato_da', 'idx_pf_creato_da');
            $table->index('assemblato_da', 'idx_pf_assemblato_da');
            $table->index('articolo_risultante_id', 'idx_pf_articolo_risultante');
            $table->index('venduto_il', 'idx_pf_venduto_il');
        });
    }

    public function down()
    {
        Schema::table('prodotti_finiti', function (Blueprint $table) {
            $table->dropForeign(['creato_da']);
            $table->dropForeign(['assemblato_da']);
            $table->dropForeign(['articolo_risultante_id']);
            
            $table->dropColumn([
                'creato_da',
                'assemblato_da',
                'articolo_risultante_id',
                'oro_totale',
                'brillanti_totali',
                'pietre_totali',
                'venduto_il',
                'venduto_a_cliente_id',
            ]);
        });
    }
};
