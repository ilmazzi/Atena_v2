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
        Schema::table('carichi', function (Blueprint $table) {
            // Sede dove viene caricata la merce
            $table->foreignId('sede_id')
                  ->nullable()
                  ->after('fornitore_id')
                  ->constrained('sedi')
                  ->nullOnDelete()
                  ->comment('Sede destinazione carico');
            
            // Categoria merceologica (magazzino) dove va caricata la merce
            $table->foreignId('categoria_id')
                  ->nullable()
                  ->after('sede_id')
                  ->constrained('categorie_merceologiche')
                  ->nullOnDelete()
                  ->comment('Categoria merceologica destinazione');
            
            // Riferimenti a DDT/Fattura create
            $table->foreignId('ddt_id')
                  ->nullable()
                  ->after('ocr_document_id')
                  ->constrained('ddt')
                  ->nullOnDelete()
                  ->comment('DDT creato da questo carico');
            
            $table->foreignId('fattura_id')
                  ->nullable()
                  ->after('ddt_id')
                  ->constrained('fatture')
                  ->nullOnDelete()
                  ->comment('Fattura creata da questo carico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carichi', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['ddt_id']);
            $table->dropForeign(['fattura_id']);
            
            $table->dropColumn(['sede_id', 'categoria_id', 'ddt_id', 'fattura_id']);
        });
    }
};
