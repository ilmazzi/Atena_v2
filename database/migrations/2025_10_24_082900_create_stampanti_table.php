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
        Schema::create('stampanti', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // "Cavour", "Monastero", "Jolly", "Roma"
            $table->string('ip_address');
            $table->string('port')->default('9100');
            $table->string('modello'); // "ZT230", "ZT420", "ZT620"
            $table->json('categorie_permesse'); // [1,2,3,4,5,6,7,8,9]
            $table->json('sedi_permesse'); // [1,2,3,4,5]
            $table->boolean('attiva')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stampanti');
    }
};
