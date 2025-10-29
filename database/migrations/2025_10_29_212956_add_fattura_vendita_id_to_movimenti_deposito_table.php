<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aggiunge fattura_vendita_id per collegare movimenti alle fatture di vendita (non di acquisto!)
     */
    public function up()
    {
        Schema::table('movimenti_deposito', function (Blueprint $table) {
            $table->unsignedBigInteger('fattura_vendita_id')
                  ->nullable()
                  ->after('fattura_id')
                  ->comment('Fattura di vendita (diversa da fattura_id che è per acquisti)');
            
            $table->foreign('fattura_vendita_id')
                  ->references('id')
                  ->on('fatture_vendita')
                  ->onDelete('set null');
            
            $table->index('fattura_vendita_id', 'idx_mov_dep_fatt_vendita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('movimenti_deposito', function (Blueprint $table) {
            $table->dropForeign(['fattura_vendita_id']);
            $table->dropIndex('idx_mov_dep_fatt_vendita');
            $table->dropColumn('fattura_vendita_id');
        });
    }
};
