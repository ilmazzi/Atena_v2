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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('stampante_default_id')->nullable()->constrained('stampanti');
            $table->json('categorie_permesse')->nullable(); // [1,2,3,4,5,6,7,8,9]
            $table->json('sedi_permesse')->nullable(); // [1,2,3,4,5]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['stampante_default_id']);
            $table->dropColumn(['stampante_default_id', 'categorie_permesse', 'sedi_permesse']);
        });
    }
};
