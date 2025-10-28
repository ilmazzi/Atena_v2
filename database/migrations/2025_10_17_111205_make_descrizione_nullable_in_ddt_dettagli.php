<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ddt_dettagli', function (Blueprint $table) {
            $table->string('descrizione', 500)->nullable()->change();
        });
        
        Schema::table('fatture_dettagli', function (Blueprint $table) {
            $table->string('descrizione', 500)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('ddt_dettagli', function (Blueprint $table) {
            $table->string('descrizione', 500)->nullable(false)->change();
        });
        
        Schema::table('fatture_dettagli', function (Blueprint $table) {
            $table->string('descrizione', 500)->nullable(false)->change();
        });
    }
};
