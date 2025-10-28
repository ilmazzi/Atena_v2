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
        Schema::table('vetrine', function (Blueprint $table) {
            // Controlla se la colonna magazzino_id esiste prima di rimuoverla
            if (Schema::hasColumn('vetrine', 'magazzino_id')) {
                // Prova a rimuovere il foreign key se esiste
                try {
                    $table->dropForeign(['magazzino_id']);
                } catch (\Exception $e) {
                    // Ignora se il foreign key non esiste
                }
                $table->dropColumn('magazzino_id');
            }
            
            // Aggiungi tipologia vetrina se non esiste già
            if (!Schema::hasColumn('vetrine', 'tipologia')) {
                $table->enum('tipologia', ['gioielleria', 'orologeria'])->after('nome')->default('gioielleria');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vetrine', function (Blueprint $table) {
            // Rimuovi tipologia
            $table->dropColumn('tipologia');
            
            // Ripristina magazzino_id
            $table->foreignId('magazzino_id')->after('nome')->constrained('categorie_merceologiche');
        });
    }
};