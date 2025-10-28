<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('giacenze', function (Blueprint $table) {
            $table->integer('quantita_iniziale')
                  ->after('quantita')
                  ->default(1)
                  ->comment('Quantità iniziale al carico');
        });
        
        // Popola il campo con i valori attuali di quantita per gli articoli esistenti
        \Illuminate\Support\Facades\DB::statement('UPDATE giacenze SET quantita_iniziale = quantita');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giacenze', function (Blueprint $table) {
            $table->dropColumn('quantita_iniziale');
        });
    }
};
