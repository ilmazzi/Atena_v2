<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimenti_deposito', function (Blueprint $table) {
            $table->enum('tipo_item', ['articolo', 'prodotto_finito'])
                  ->nullable()
                  ->after('prodotto_finito_id')
                  ->comment('Tipo di item: articolo o prodotto_finito');
        });
        
        // Popola il campo per i movimenti esistenti
        DB::statement("
            UPDATE movimenti_deposito 
            SET tipo_item = CASE 
                WHEN articolo_id IS NOT NULL THEN 'articolo'
                WHEN prodotto_finito_id IS NOT NULL THEN 'prodotto_finito'
                ELSE NULL
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimenti_deposito', function (Blueprint $table) {
            $table->dropColumn('tipo_item');
        });
    }
};
