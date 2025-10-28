<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Sistema Conti Deposito
 * 
 * Gestisce l'invio di articoli e prodotti finiti tra sedi
 * con tracking temporale (1 anno) e gestione quantità parziali
 */
return new class extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════
        // CONTI DEPOSITO - Tabella principale
        // ═══════════════════════════════════════════════════════════
        Schema::create('conti_deposito', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 50)->unique()->comment('Codice deposito (CD-YYYY-NNNN)');
            
            // Sedi coinvolte
            $table->foreignId('sede_mittente_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede che invia gli articoli');
            
            $table->foreignId('sede_destinataria_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede che riceve gli articoli');
            
            // Tracking temporale
            $table->date('data_invio')->comment('Data invio articoli');
            $table->date('data_scadenza')->comment('Data scadenza (data_invio + 1 anno)');
            
            // Stato del deposito
            $table->enum('stato', [
                'attivo',      // Deposito in corso
                'scaduto',     // Scaduto ma non ancora rientrato
                'chiuso',      // Completamente rientrato
                'parziale'     // Parzialmente venduto/rientrato
            ])->default('attivo');
            
            // Collegamenti DDT
            $table->foreignId('ddt_invio_id')
                  ->nullable()
                  ->constrained('ddt')
                  ->nullOnDelete()
                  ->comment('DDT di invio articoli');
            
            $table->foreignId('ddt_reso_id')
                  ->nullable()
                  ->constrained('ddt')
                  ->nullOnDelete()
                  ->comment('DDT di reso articoli');
            
            $table->foreignId('ddt_rimando_id')
                  ->nullable()
                  ->constrained('ddt')
                  ->nullOnDelete()
                  ->comment('DDT per rimandare dopo reso');
            
            // Riferimento deposito precedente (per rinnovi)
            $table->foreignId('deposito_precedente_id')
                  ->nullable()
                  ->constrained('conti_deposito')
                  ->nullOnDelete()
                  ->comment('Deposito originale se questo è un rinnovo');
            
            // Valori economici
            $table->decimal('valore_totale_invio', 12, 2)->default(0)->comment('Valore totale articoli inviati');
            $table->decimal('valore_venduto', 12, 2)->default(0)->comment('Valore articoli venduti');
            $table->decimal('valore_rientrato', 12, 2)->default(0)->comment('Valore articoli rientrati');
            
            // Contatori
            $table->integer('articoli_inviati')->default(0)->comment('Numero articoli/PF inviati');
            $table->integer('articoli_venduti')->default(0)->comment('Numero articoli/PF venduti');
            $table->integer('articoli_rientrati')->default(0)->comment('Numero articoli/PF rientrati');
            
            // Metadata
            $table->text('note')->nullable();
            $table->json('configurazione')->nullable()->comment('Configurazioni specifiche del deposito');
            
            // User tracking
            $table->foreignId('creato_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha creato il deposito');
            
            $table->foreignId('chiuso_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha chiuso il deposito');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('sede_mittente_id', 'idx_cd_sede_mittente');
            $table->index('sede_destinataria_id', 'idx_cd_sede_destinataria');
            $table->index('data_scadenza', 'idx_cd_data_scadenza');
            $table->index('stato', 'idx_cd_stato');
            $table->index(['sede_mittente_id', 'sede_destinataria_id'], 'idx_cd_sedi');
            $table->index(['data_invio', 'stato'], 'idx_cd_data_stato');
            
            // Nota: Constraint sede mittente != destinataria gestito a livello applicativo
        });
        
        // ═══════════════════════════════════════════════════════════
        // MOVIMENTI DEPOSITO - Tracking dettagliato
        // ═══════════════════════════════════════════════════════════
        Schema::create('movimenti_deposito', function (Blueprint $table) {
            $table->id();
            
            // Riferimento al conto deposito
            $table->foreignId('conto_deposito_id')
                  ->constrained('conti_deposito')
                  ->cascadeOnDelete();
            
            // Articolo o Prodotto Finito (uno dei due)
            $table->foreignId('articolo_id')
                  ->nullable()
                  ->constrained('articoli')
                  ->cascadeOnDelete()
                  ->comment('Articolo coinvolto nel movimento');
            
            $table->foreignId('prodotto_finito_id')
                  ->nullable()
                  ->constrained('prodotti_finiti')
                  ->cascadeOnDelete()
                  ->comment('Prodotto finito coinvolto nel movimento');
            
            // Tipo movimento
            $table->enum('tipo_movimento', [
                'invio',       // Invio iniziale
                'vendita',     // Vendita dalla sede destinataria
                'reso',        // Reso alla sede mittente
                'rimando'      // Nuovo invio dopo reso
            ]);
            
            // Quantità e valori
            $table->integer('quantita')->comment('Quantità coinvolta nel movimento');
            $table->decimal('costo_unitario', 10, 2)->comment('Costo unitario articolo/PF');
            $table->decimal('costo_totale', 12, 2)->comment('Costo totale (costo_unitario * quantita)');
            
            // Data movimento
            $table->timestamp('data_movimento')->comment('Quando è avvenuto il movimento');
            
            // Collegamenti documenti
            $table->foreignId('ddt_id')
                  ->nullable()
                  ->constrained('ddt')
                  ->nullOnDelete()
                  ->comment('DDT associato (invio/reso/rimando)');
            
            $table->foreignId('fattura_id')
                  ->nullable()
                  ->constrained('fatture')
                  ->nullOnDelete()
                  ->comment('Fattura associata (se vendita)');
            
            // Metadata
            $table->text('note')->nullable();
            $table->json('dettagli')->nullable()->comment('Dettagli specifici del movimento');
            
            // User tracking
            $table->foreignId('eseguito_da')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Utente che ha eseguito il movimento');
            
            // Timestamps
            $table->timestamps();
            
            // Indici
            $table->index('conto_deposito_id', 'idx_md_conto_deposito');
            $table->index('articolo_id', 'idx_md_articolo');
            $table->index('prodotto_finito_id', 'idx_md_prodotto_finito');
            $table->index('tipo_movimento', 'idx_md_tipo_movimento');
            $table->index('data_movimento', 'idx_md_data_movimento');
            $table->index(['conto_deposito_id', 'tipo_movimento'], 'idx_md_conto_tipo');
            
            // Nota: Constraint articolo_id XOR prodotto_finito_id gestito a livello applicativo
        });
        
        // ═══════════════════════════════════════════════════════════
        // AGGIORNAMENTO TABELLA ARTICOLI
        // ═══════════════════════════════════════════════════════════
        Schema::table('articoli', function (Blueprint $table) {
            // Campo per tracking rapido del deposito corrente
            $table->foreignId('conto_deposito_corrente_id')
                  ->nullable()
                  ->after('stato_articolo')
                  ->constrained('conti_deposito')
                  ->nullOnDelete()
                  ->comment('Conto deposito attuale (se in deposito)');
            
            $table->integer('quantita_in_deposito')
                  ->default(0)
                  ->after('conto_deposito_corrente_id')
                  ->comment('Quantità attualmente in conto deposito');
            
            // Indice per performance
            $table->index('conto_deposito_corrente_id', 'idx_art_conto_deposito');
            $table->index(['conto_deposito_corrente_id', 'quantita_in_deposito'], 'idx_art_deposito_qta');
        });
        
        // ═══════════════════════════════════════════════════════════
        // AGGIORNAMENTO TABELLA PRODOTTI FINITI
        // ═══════════════════════════════════════════════════════════
        Schema::table('prodotti_finiti', function (Blueprint $table) {
            // Campo per tracking deposito
            $table->foreignId('conto_deposito_corrente_id')
                  ->nullable()
                  ->after('stato')
                  ->constrained('conti_deposito')
                  ->nullOnDelete()
                  ->comment('Conto deposito attuale (se in deposito)');
            
            $table->boolean('in_conto_deposito')
                  ->default(false)
                  ->after('conto_deposito_corrente_id')
                  ->comment('Se il PF è attualmente in conto deposito');
            
            // Indice per performance
            $table->index('conto_deposito_corrente_id', 'idx_pf_conto_deposito');
            $table->index('in_conto_deposito', 'idx_pf_in_deposito');
        });
        
        // Commenti tabelle
        DB::statement("ALTER TABLE conti_deposito COMMENT = 'Conti deposito tra sedi - Sistema gestione articoli temporanei'");
        DB::statement("ALTER TABLE movimenti_deposito COMMENT = 'Movimenti dettagliati articoli/PF nei conti deposito'");
    }

    public function down()
    {
        // Rimuovi colonne aggiunte
        Schema::table('prodotti_finiti', function (Blueprint $table) {
            $table->dropForeign(['conto_deposito_corrente_id']);
            $table->dropColumn(['conto_deposito_corrente_id', 'in_conto_deposito']);
        });
        
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropForeign(['conto_deposito_corrente_id']);
            $table->dropColumn(['conto_deposito_corrente_id', 'quantita_in_deposito']);
        });
        
        // Elimina tabelle
        Schema::dropIfExists('movimenti_deposito');
        Schema::dropIfExists('conti_deposito');
    }
};