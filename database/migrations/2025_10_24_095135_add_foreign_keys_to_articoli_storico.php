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
        Schema::table('articoli_storico', function (Blueprint $table) {
            $table->foreign('sessione_inventario_id')->references('id')->on('inventario_sessioni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articoli_storico', function (Blueprint $table) {
            $table->dropForeign(['sessione_inventario_id']);
        });
    }
};