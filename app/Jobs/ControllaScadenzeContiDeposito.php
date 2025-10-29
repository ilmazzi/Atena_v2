<?php

namespace App\Jobs;

use App\Models\ContoDeposito;
use App\Models\User;
use App\Notifications\ContoDepositoScadenzaNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * ControllaScadenzeContiDeposito - Job per controllo automatico scadenze
 * 
 * Controlla giornalmente i conti deposito e invia notifiche per:
 * - Depositi in scadenza (30, 15, 7, 3, 1 giorni)
 * - Depositi scaduti (ogni giorno fino alla chiusura)
 */
class ControllaScadenzeContiDeposito implements ShouldQueue
{
    use Queueable;

    // Intervalli di notifica (giorni prima della scadenza)
    private const INTERVALLI_SCADENZA = [30, 15, 7, 3, 1];

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔍 Inizio controllo scadenze conti deposito');

        try {
            $risultati = [
                'depositi_in_scadenza' => 0,
                'depositi_scaduti' => 0,
                'notifiche_inviate' => 0,
                'errori' => 0
            ];

            // 1. Controlla depositi in scadenza
            $risultati['depositi_in_scadenza'] = $this->controllaDepositiInScadenza();

            // 2. Controlla depositi scaduti
            $risultati['depositi_scaduti'] = $this->controllaDepositiScaduti();

            // 3. Log risultati
            Log::info('✅ Controllo scadenze completato', $risultati);

        } catch (\Exception $e) {
            Log::error('❌ Errore durante controllo scadenze: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Controlla depositi in scadenza nei prossimi giorni
     */
    private function controllaDepositiInScadenza(): int
    {
        $depositiTrovati = 0;

        foreach (self::INTERVALLI_SCADENZA as $giorni) {
            $dataTarget = now()->addDays($giorni)->toDateString();
            
            $depositi = ContoDeposito::where('data_scadenza', $dataTarget)
                ->where('stato', 'attivo')
                ->with(['sedeMittente', 'sedeDestinataria'])
                ->get();

            foreach ($depositi as $deposito) {
                $this->inviaNotificaScadenza($deposito, 'scadenza');
                $depositiTrovati++;
            }

            if ($depositi->count() > 0) {
                Log::info("📅 Trovati {$depositi->count()} depositi in scadenza tra {$giorni} giorni");
            }
        }

        return $depositiTrovati;
    }

    /**
     * Controlla depositi già scaduti
     */
    private function controllaDepositiScaduti(): int
    {
        $depositi = ContoDeposito::where('data_scadenza', '<', now()->toDateString())
            ->whereIn('stato', ['attivo', 'scaduto'])
            ->with(['sedeMittente', 'sedeDestinataria'])
            ->get();

        foreach ($depositi as $deposito) {
            // Aggiorna stato se necessario
            if ($deposito->stato === 'attivo') {
                $deposito->update(['stato' => 'scaduto']);
            }

            // Invia notifica ogni settimana per depositi scaduti
            $giorniScaduto = Carbon::parse($deposito->data_scadenza)->diffInDays(now());
            if ($giorniScaduto % 7 === 0 || $giorniScaduto <= 7) {
                $this->inviaNotificaScadenza($deposito, 'scaduto');
            }
        }

        if ($depositi->count() > 0) {
            Log::warning("⚠️ Trovati {$depositi->count()} depositi scaduti");
        }

        return $depositi->count();
    }

    /**
     * Invia notifica di scadenza agli utenti competenti
     */
    private function inviaNotificaScadenza(ContoDeposito $deposito, string $tipo): void
    {
        try {
            // Trova utenti da notificare
            $utenti = $this->trovaUtentiDaNotificare($deposito);

            if ($utenti->isEmpty()) {
                Log::warning("⚠️ Nessun utente trovato per notificare deposito {$deposito->codice}");
                return;
            }

            // Invia notifica
            foreach ($utenti as $utente) {
                $utente->notify(new ContoDepositoScadenzaNotification($deposito, $tipo));
            }

            Log::info("📧 Notifica {$tipo} inviata per deposito {$deposito->codice} a {$utenti->count()} utenti");

        } catch (\Exception $e) {
            Log::error("❌ Errore invio notifica per deposito {$deposito->codice}: " . $e->getMessage());
        }
    }

    /**
     * Trova utenti da notificare per un deposito
     */
    private function trovaUtentiDaNotificare(ContoDeposito $deposito): \Illuminate\Support\Collection
    {
        // Strategia: notifica tutti gli admin + utenti che possono accedere alle sedi coinvolte
        return User::where(function ($query) use ($deposito) {
            // Admin (senza restrizioni)
            $query->whereNull('sedi_permesse')
                  ->orWhere('sedi_permesse', '[]')
                  // Oppure utenti con accesso alle sedi coinvolte
                  ->orWhereJsonContains('sedi_permesse', $deposito->sede_mittente_id)
                  ->orWhereJsonContains('sedi_permesse', $deposito->sede_destinataria_id);
        })->get();
    }
}
