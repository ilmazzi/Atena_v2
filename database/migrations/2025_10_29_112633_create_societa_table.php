<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella Società
 * 
 * Gestisce le diverse società che condividono Athena v2
 * - De Pascalis s.p.a. (DP)
 * - Luigi De Pascalis (LDP)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('societa', function (Blueprint $table) {
            $table->id();
            
            // Identificazione
            $table->string('codice', 10)->unique()->comment('Codice società (DP, LDP)');
            $table->string('ragione_sociale', 255)->comment('Ragione sociale completa');
            $table->string('partita_iva', 20)->nullable()->comment('Partita IVA');
            $table->string('codice_fiscale', 20)->nullable()->comment('Codice fiscale');
            
            // Indirizzo
            $table->string('indirizzo', 255)->nullable();
            $table->string('citta', 100)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 10)->nullable();
            
            // Contatti
            $table->string('telefono', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pec', 255)->nullable()->comment('PEC aziendale');
            
            // Notifiche
            $table->json('email_notifiche')->nullable()->comment('Array email per notifiche conti deposito');
            
            // Configurazione
            $table->json('configurazione')->nullable()->comment('Config JSON: prefisso numerazione, etc.');
            
            // Status
            $table->boolean('attivo')->default(true);
            
            // Metadata
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index('codice', 'idx_societa_codice');
            $table->index('attivo', 'idx_societa_attivo');
        });
        
        DB::statement("ALTER TABLE societa COMMENT = 'Società che utilizzano Athena v2 - Multi-tenant'");
        
        // ═══════════════════════════════════════════════════════════
        // SEED: Inserisci le 2 società
        // ═══════════════════════════════════════════════════════════
        DB::table('societa')->insert([
            [
                'codice' => 'DP',
                'ragione_sociale' => 'De Pascalis s.p.a.',
                'attivo' => true,
                'configurazione' => json_encode([
                    'prefisso_ddt' => 'DEP-DP',
                    'prefisso_reso' => 'RES-DP',
                    'prefisso_conto_deposito' => 'CD-DP',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codice' => 'LDP',
                'ragione_sociale' => 'Luigi De Pascalis',
                'attivo' => true,
                'configurazione' => json_encode([
                    'prefisso_ddt' => 'DEP-LDP',
                    'prefisso_reso' => 'RES-LDP',
                    'prefisso_conto_deposito' => 'CD-LDP',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('societa');
    }
};
