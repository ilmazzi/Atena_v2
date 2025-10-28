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
        Schema::table('articoli', function (Blueprint $table) {
            // EAN/Barcode (13 cifre standard, ma supporta anche EAN-8)
            $table->string('ean', 20)->nullable()->after('codice')->comment('Codice EAN/Barcode (13 cifre)');
            
            // Numero seriale (per articoli singoli come orologi)
            $table->string('numero_seriale', 50)->nullable()->after('ean')->comment('Numero seriale univoco');
            
            // Indice per ricerca veloce
            $table->index('ean');
            $table->index('numero_seriale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropIndex(['ean']);
            $table->dropIndex(['numero_seriale']);
            $table->dropColumn(['ean', 'numero_seriale']);
        });
    }
};
