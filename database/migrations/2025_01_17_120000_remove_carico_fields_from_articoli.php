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
        // Verifica se la tabella esiste prima di modificarla
        if (Schema::hasTable('articoli')) {
            Schema::table('articoli', function (Blueprint $table) {
                // Prima rimuovi la foreign key constraint se esiste
                if (Schema::hasColumn('articoli', 'fornitore_id')) {
                    $table->dropForeign(['fornitore_id']);
                }
                
                // Poi rimuovi i campi di carico che non servono più
                $columns = ['tipo_carico', 'numero_documento_carico', 'data_carico', 'fornitore_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('articoli', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articoli', function (Blueprint $table) {
            $table->string('tipo_carico')->nullable();
            $table->string('numero_documento_carico')->nullable();
            $table->date('data_carico')->nullable();
            $table->unsignedBigInteger('fornitore_id')->nullable();
        });
    }
};
