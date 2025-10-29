<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabella per fatture di VENDITA ai clienti (diversa dalle fatture di acquisto dai fornitori)
     */
    public function up()
    {
        Schema::create('fatture_vendita', function (Blueprint $table) {
            $table->id();
            
            // Identificazione documento
            $table->string('numero', 50)->comment('Numero fattura vendita');
            $table->date('data_documento')->comment('Data emissione fattura');
            $table->integer('anno')->comment('Anno documento');
            
            // Cliente
            $table->string('cliente_nome', 100)->comment('Nome cliente');
            $table->string('cliente_cognome', 100)->comment('Cognome cliente');
            $table->string('cliente_telefono', 20)->nullable()->comment('Telefono cliente');
            $table->string('cliente_email', 100)->nullable()->comment('Email cliente');
            
            // Importi
            $table->decimal('totale', 10, 2)->default(0)->comment('Totale fattura');
            $table->decimal('imponibile', 10, 2)->default(0)->comment('Totale imponibile');
            $table->decimal('iva', 10, 2)->default(0)->comment('Totale IVA');
            
            // Relazioni
            $table->foreignId('sede_id')
                  ->nullable()
                  ->constrained('sedi')
                  ->nullOnDelete()
                  ->comment('Sede che effettua la vendita');
            
            $table->unsignedBigInteger('conto_deposito_id')
                  ->nullable()
                  ->comment('Conto deposito da cui proviene la vendita');
            
            $table->unsignedBigInteger('ddt_invio_id')
                  ->nullable()
                  ->comment('DDT di invio originale del deposito');
            
            // Dati aggiuntivi
            $table->integer('quantita_totale')->default(0)->comment('Quantità totale articoli venduti');
            $table->integer('numero_articoli')->default(0)->comment('Numero di articoli/PF diversi venduti');
            
            // Metadata
            $table->text('note')->nullable()->comment('Note aggiuntive');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('numero', 'idx_fatt_vendita_numero');
            $table->index('anno', 'idx_fatt_vendita_anno');
            $table->index('data_documento', 'idx_fatt_vendita_data');
            $table->index('conto_deposito_id', 'idx_fatt_vendita_deposito');
            $table->index('sede_id', 'idx_fatt_vendita_sede');
            $table->unique(['numero', 'sede_id', 'anno'], 'idx_fatt_vendita_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('fatture_vendita');
    }
};
