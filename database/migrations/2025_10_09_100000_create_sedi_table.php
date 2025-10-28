<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella sedi (luoghi fisici)
 * 
 * Separa il concetto di SEDE FISICA da MAGAZZINO
 * 
 * Sedi:
 * - LECCO: CAVOUR, JOLLY
 * - BELLAGIO: MONASTERO, MAZZINI
 * - ROMA: ROMA
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
        Schema::create('sedi', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // IDENTIFICAZIONE
            // ═══════════════════════════════════════════════════════════
            $table->string('codice', 50)->unique()->comment('Codice identificativo sede (es: CAV, JOL)');
            $table->string('nome', 255)->comment('Nome sede (es: CAVOUR, JOLLY)');
            
            // ═══════════════════════════════════════════════════════════
            // UBICAZIONE FISICA
            // ═══════════════════════════════════════════════════════════
            $table->string('indirizzo', 255)->nullable()->comment('Indirizzo completo');
            $table->string('citta', 100)->comment('Città (LECCO, BELLAGIO, ROMA)');
            $table->string('provincia', 2)->nullable()->comment('Sigla provincia');
            $table->string('cap', 10)->nullable()->comment('CAP');
            
            // ═══════════════════════════════════════════════════════════
            // CONTATTI
            // ═══════════════════════════════════════════════════════════
            $table->string('telefono', 50)->nullable();
            $table->string('email', 255)->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // TIPOLOGIA
            // ═══════════════════════════════════════════════════════════
            $table->enum('tipo', [
                'negozio',           // Punto vendita
                'deposito',          // Solo deposito
                'laboratorio',       // Laboratorio/riparazione
                'ufficio'            // Uffici amministrativi
            ])->default('negozio')->comment('Tipologia sede');
            
            // ═══════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════
            $table->boolean('attivo')->default(true)->comment('Sede operativa');
            
            // ═══════════════════════════════════════════════════════════
            // METADATA
            // ═══════════════════════════════════════════════════════════
            $table->text('note')->nullable();
            $table->json('orari')->nullable()->comment('Orari apertura (JSON)');
            
            // ═══════════════════════════════════════════════════════════
            // TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();
            $table->softDeletes();
            
            // ═══════════════════════════════════════════════════════════
            // INDICI
            // ═══════════════════════════════════════════════════════════
            $table->index('codice', 'idx_sedi_codice');
            $table->index('citta', 'idx_sedi_citta');
            $table->index('attivo', 'idx_sedi_attivo');
        });
        
        DB::statement("ALTER TABLE sedi COMMENT = 'Sedi fisiche - Dominio: Shared'");
        
        // ═══════════════════════════════════════════════════════════
        // SEED: Inserisci le 5 sedi
        // ═══════════════════════════════════════════════════════════
        DB::table('sedi')->insert([
            [
                'codice' => 'CAV',
                'nome' => 'CAVOUR',
                'citta' => 'LECCO',
                'provincia' => 'LC',
                'tipo' => 'negozio',
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codice' => 'JOL',
                'nome' => 'JOLLY',
                'citta' => 'LECCO',
                'provincia' => 'LC',
                'tipo' => 'negozio',
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codice' => 'MON',
                'nome' => 'MONASTERO',
                'citta' => 'BELLAGIO',
                'provincia' => 'CO',
                'tipo' => 'negozio',
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codice' => 'MAZ',
                'nome' => 'MAZZINI',
                'citta' => 'BELLAGIO',
                'provincia' => 'CO',
                'tipo' => 'negozio',
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codice' => 'ROM',
                'nome' => 'ROMA',
                'citta' => 'ROMA',
                'provincia' => 'RM',
                'tipo' => 'negozio',
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sedi');
    }
};

