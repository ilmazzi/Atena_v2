<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Tabella Notifiche
 * 
 * Sistema di notifiche per conti deposito:
 * - Notifiche quando ci sono resi
 * - Notifiche quando ci sono vendite
 * - Notifiche per scadenze in arrivo
 * - Notifiche per depositi scaduti
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifiche', function (Blueprint $table) {
            $table->id();
            
            // Tipo notifica
            $table->string('tipo', 50)->comment('tipo: reso, vendita, scadenza, deposito_scaduto');
            
            // Destinatario
            $table->foreignId('societa_id')
                  ->nullable()
                  ->constrained('societa')
                  ->restrictOnDelete()
                  ->comment('Società destinataria della notifica');
            
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete()
                  ->comment('Utente specifico destinatario (opzionale)');
            
            // Collegamento a conto deposito
            $table->foreignId('conto_deposito_id')
                  ->nullable()
                  ->constrained('conti_deposito')
                  ->restrictOnDelete()
                  ->comment('Conto deposito di riferimento');
            
            // Collegamento a movimento (reso/vendita)
            $table->foreignId('movimento_deposito_id')
                  ->nullable()
                  ->constrained('movimenti_deposito')
                  ->restrictOnDelete()
                  ->comment('Movimento di riferimento (reso/vendita)');
            
            // Contenuto
            $table->string('titolo', 255)->comment('Titolo notifica');
            $table->text('messaggio')->comment('Messaggio completo');
            $table->json('dati_aggiuntivi')->nullable()->comment('Dati aggiuntivi (JSON): articoli, importi, etc.');
            
            // Stato
            $table->boolean('letta')->default(false)->comment('Notifica letta');
            $table->timestamp('letta_il')->nullable()->comment('Data lettura');
            
            // Email
            $table->boolean('email_inviata')->default(false)->comment('Email inviata con successo');
            $table->timestamp('email_inviata_il')->nullable()->comment('Data invio email');
            $table->text('email_errore')->nullable()->comment('Errore invio email (se fallito)');
            
            // Timestamps
            $table->timestamps();
            
            // Indici
            $table->index('tipo', 'idx_notifiche_tipo');
            $table->index('societa_id', 'idx_notifiche_societa');
            $table->index('user_id', 'idx_notifiche_user');
            $table->index('letta', 'idx_notifiche_letta');
            $table->index(['societa_id', 'letta'], 'idx_notifiche_societa_letta');
            $table->index('created_at', 'idx_notifiche_created');
        });
        
        DB::statement("ALTER TABLE notifiche COMMENT = 'Notifiche sistema conti deposito - Email e dashboard'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notifiche');
    }
};
