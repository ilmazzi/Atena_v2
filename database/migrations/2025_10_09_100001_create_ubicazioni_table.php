<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella ubicazioni (scaffali/box fisici)
 * 
 * Gerarchia: Sede > Scaffale > Ripiano > Box > Posizione
 * 
 * Esempio: "CAVOUR > Scaffale A > Ripiano 2 > Box 3"
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ubicazioni', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // RELAZIONE SEDE
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('sede_id')
                  ->constrained('sedi')
                  ->restrictOnDelete()
                  ->comment('Sede fisica di appartenenza');
            
            // ═══════════════════════════════════════════════════════════
            // GERARCHIA UBICAZIONE
            // ═══════════════════════════════════════════════════════════
            $table->string('scaffale', 50)->comment('Scaffale (es: A, B, 1, 2)');
            $table->string('ripiano', 50)->nullable()->comment('Ripiano (es: 1, 2, alto, basso)');
            $table->string('box', 50)->nullable()->comment('Box/Cassetto (es: Box-1, Cassetto-3)');
            $table->string('posizione', 100)->nullable()->comment('Posizione dettaglio (es: sinistra, centro)');
            
            // ═══════════════════════════════════════════════════════════
            // IDENTIFICAZIONE
            // ═══════════════════════════════════════════════════════════
            $table->string('codice', 100)->unique()->comment('Codice univoco (es: CAV-A-2-BOX3)');
            $table->text('descrizione')->nullable()->comment('Descrizione ubicazione');
            
            // ═══════════════════════════════════════════════════════════
            // CAPACITÀ E TRACCIAMENTO
            // ═══════════════════════════════════════════════════════════
            $table->integer('capacita_massima')->default(999)->comment('Numero massimo articoli');
            $table->integer('articoli_presenti')->default(0)->comment('Articoli attualmente presenti');
            
            // ═══════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════
            $table->boolean('attivo')->default(true)->comment('Ubicazione utilizzabile');
            
            // ═══════════════════════════════════════════════════════════
            // METADATA
            // ═══════════════════════════════════════════════════════════
            $table->text('note')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();
            $table->softDeletes();
            
            // ═══════════════════════════════════════════════════════════
            // INDICI
            // ═══════════════════════════════════════════════════════════
            $table->index('sede_id', 'idx_ubicazioni_sede');
            $table->index(['sede_id', 'scaffale'], 'idx_ubicazioni_sede_scaffale');
            $table->index('codice', 'idx_ubicazioni_codice');
            $table->index('attivo', 'idx_ubicazioni_attivo');
            
            // Unique constraint: una sola ubicazione per questa combinazione
            $table->unique(['sede_id', 'scaffale', 'ripiano', 'box', 'posizione'], 'uk_ubicazione_completa');
        });
        
        DB::statement("ALTER TABLE ubicazioni COMMENT = 'Ubicazioni fisiche articoli - Dominio: Shared'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ubicazioni');
    }
};

