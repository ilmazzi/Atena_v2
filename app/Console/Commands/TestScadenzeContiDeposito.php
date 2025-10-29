<?php

namespace App\Console\Commands;

use App\Jobs\ControllaScadenzeContiDeposito;
use App\Models\ContoDeposito;
use App\Models\User;
use App\Notifications\ContoDepositoScadenzaNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * TestScadenzeContiDeposito - Command per testare sistema notifiche
 * 
 * Permette di testare il sistema di notifiche per scadenze depositi
 * senza dover aspettare le scadenze reali
 */
class TestScadenzeContiDeposito extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'depositi:test-scadenze 
                            {--run : Esegue effettivamente il job di controllo}
                            {--notify=all : Invia notifica test (all|admin|user_id)}
                            {--deposito= : ID deposito specifico per test}
                            {--tipo=scadenza : Tipo notifica (scadenza|scaduto)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa il sistema di notifiche per scadenze conti deposito';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 TESTING SISTEMA NOTIFICHE CONTI DEPOSITO');
        $this->newLine();

        // Opzione: esegui job reale
        if ($this->option('run')) {
            return $this->eseguiJobReale();
        }

        // Opzione: invia notifica test
        if ($this->option('notify') !== 'all' || $this->option('deposito')) {
            return $this->inviaNotificaTest();
        }

        // Default: mostra situazione attuale
        return $this->mostraSituazioneAttuale();
    }

    /**
     * Esegue il job reale di controllo scadenze
     */
    private function eseguiJobReale(): int
    {
        $this->warn('⚡ Eseguendo job reale ControllaScadenzeContiDeposito...');
        
        try {
            $job = new ControllaScadenzeContiDeposito();
            $job->handle();
            
            $this->info('✅ Job completato con successo!');
            $this->info('📧 Controlla i log per dettagli sulle notifiche inviate');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Errore durante esecuzione job: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Invia una notifica di test
     */
    private function inviaNotificaTest(): int
    {
        // Trova deposito per test
        $depositoId = $this->option('deposito');
        $deposito = $depositoId 
            ? ContoDeposito::find($depositoId)
            : ContoDeposito::with(['sedeMittente', 'sedeDestinataria'])->first();

        if (!$deposito) {
            $this->error('❌ Nessun deposito trovato per il test');
            return Command::FAILURE;
        }

        // Trova utenti da notificare
        $notifyOption = $this->option('notify');
        $utenti = collect();

        if (is_numeric($notifyOption)) {
            $utente = User::find($notifyOption);
            if ($utente) {
                $utenti->push($utente);
            }
        } elseif ($notifyOption === 'admin') {
            $utenti = User::whereNull('sedi_permesse')->orWhere('sedi_permesse', '[]')->get();
        } else {
            $utenti = User::take(1)->get(); // Solo primo utente per test
        }

        if ($utenti->isEmpty()) {
            $this->error('❌ Nessun utente trovato per il test');
            return Command::FAILURE;
        }

        // Invia notifica
        $tipo = $this->option('tipo');
        $this->info("📧 Inviando notifica di test...");
        $this->line("   Deposito: {$deposito->codice}");
        $this->line("   Tipo: {$tipo}");
        $this->line("   Utenti: {$utenti->count()}");

        try {
            foreach ($utenti as $utente) {
                $utente->notify(new ContoDepositoScadenzaNotification($deposito, $tipo));
                $this->line("   ✓ Notifica inviata a: {$utente->name} ({$utente->email})");
            }

            $this->newLine();
            $this->info('✅ Notifiche di test inviate con successo!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Errore durante invio notifica: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Mostra la situazione attuale dei depositi
     */
    private function mostraSituazioneAttuale(): int
    {
        $this->info('📊 SITUAZIONE ATTUALE CONTI DEPOSITO');
        $this->newLine();

        // Statistiche generali
        $attivi = ContoDeposito::where('stato', 'attivo')->count();
        $scaduti = ContoDeposito::where('stato', 'scaduto')->count();
        $chiusi = ContoDeposito::where('stato', 'chiuso')->count();

        $this->table(['Stato', 'Quantità'], [
            ['Attivi', $attivi],
            ['Scaduti', $scaduti],
            ['Chiusi', $chiusi],
        ]);

        // Depositi in scadenza prossima
        $this->newLine();
        $this->info('⏰ DEPOSITI IN SCADENZA (prossimi 30 giorni)');

        $depositiInScadenza = ContoDeposito::where('data_scadenza', '>=', now())
            ->where('data_scadenza', '<=', now()->addDays(30))
            ->where('stato', 'attivo')
            ->with(['sedeMittente', 'sedeDestinataria'])
            ->orderBy('data_scadenza')
            ->get();

        if ($depositiInScadenza->isEmpty()) {
            $this->line('   Nessun deposito in scadenza nei prossimi 30 giorni ✅');
        } else {
            $rows = [];
            foreach ($depositiInScadenza as $deposito) {
                $giorni = Carbon::parse($deposito->data_scadenza)->diffInDays(now());
                $rows[] = [
                    $deposito->codice,
                    $deposito->sedeMittente->nome ?? 'N/A',
                    $deposito->sedeDestinataria->nome ?? 'N/A',
                    $deposito->data_scadenza->format('d/m/Y'),
                    "{$giorni} giorni",
                    '€' . number_format($deposito->valore_totale_invio, 2, ',', '.')
                ];
            }

            $this->table([
                'Codice', 'Sede Mittente', 'Sede Destinataria', 
                'Data Scadenza', 'Giorni Rimanenti', 'Valore'
            ], $rows);
        }

        // Depositi scaduti
        $this->newLine();
        $this->warn('🚨 DEPOSITI SCADUTI');

        $depositiScaduti = ContoDeposito::where('data_scadenza', '<', now())
            ->whereIn('stato', ['attivo', 'scaduto'])
            ->with(['sedeMittente', 'sedeDestinataria'])
            ->orderBy('data_scadenza')
            ->get();

        if ($depositiScaduti->isEmpty()) {
            $this->line('   Nessun deposito scaduto ✅');
        } else {
            $rows = [];
            foreach ($depositiScaduti as $deposito) {
                $giorni = Carbon::parse($deposito->data_scadenza)->diffInDays(now());
                $rows[] = [
                    $deposito->codice,
                    $deposito->sedeMittente->nome ?? 'N/A',
                    $deposito->sedeDestinataria->nome ?? 'N/A',
                    $deposito->data_scadenza->format('d/m/Y'),
                    "Scaduto da {$giorni} giorni",
                    '€' . number_format($deposito->valore_totale_invio, 2, ',', '.')
                ];
            }

            $this->table([
                'Codice', 'Sede Mittente', 'Sede Destinataria', 
                'Data Scadenza', 'Stato', 'Valore'
            ], $rows);
        }

        // Suggerimenti
        $this->newLine();
        $this->info('💡 COMANDI DISPONIBILI');
        $this->line('   php artisan depositi:test-scadenze --run');
        $this->line('   php artisan depositi:test-scadenze --notify=admin --tipo=scadenza');
        $this->line('   php artisan depositi:test-scadenze --deposito=1 --notify=1 --tipo=scaduto');

        return Command::SUCCESS;
    }
}
