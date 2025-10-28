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
            $table->text('ultimo_testo_vetrina')->nullable()->after('note')->comment('Ultimo testo vetrina utilizzato per questo articolo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->dropColumn('ultimo_testo_vetrina');
        });
    }
};
