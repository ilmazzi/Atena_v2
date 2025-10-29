<?php

namespace App\Notifications;

use App\Models\ContoDeposito;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

/**
 * ContoDepositoScadenzaNotification - Notifica scadenze depositi
 * 
 * Invia email di alert per depositi in scadenza o scaduti
 */
class ContoDepositoScadenzaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ContoDeposito $deposito;
    public string $tipoAlert;
    public int $giorniRimanenti;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContoDeposito $deposito, string $tipoAlert = 'scadenza')
    {
        $this->deposito = $deposito;
        $this->tipoAlert = $tipoAlert; // 'scadenza' | 'scaduto'
        $this->giorniRimanenti = $this->calcolaGiorniRimanenti();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $messaggio = $this->costruisciMessaggio();
        $colore = $this->tipoAlert === 'scaduto' ? 'red' : 'orange';
        
        return (new MailMessage)
            ->subject($messaggio['oggetto'])
            ->greeting("Ciao {$notifiable->name}!")
            ->line($messaggio['introduzione'])
            ->line($messaggio['dettagli'])
            ->action('Gestisci Conto Deposito', route('conti-deposito.gestisci', $this->deposito->id))
            ->line('Puoi anche accedere alla dashboard completa per vedere tutti i depositi.')
            ->action('Dashboard Conti Deposito', route('conti-deposito.index'))
            ->line('Ti ricordiamo che è importante gestire tempestivamente i depositi scaduti.')
            ->salutation('Grazie, Il Team Athena v2');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'conto_deposito_scadenza',
            'deposito_id' => $this->deposito->id,
            'deposito_codice' => $this->deposito->codice,
            'tipo_alert' => $this->tipoAlert,
            'giorni_rimanenti' => $this->giorniRimanenti,
            'sede_mittente' => $this->deposito->sedeMittente->nome ?? 'N/A',
            'sede_destinataria' => $this->deposito->sedeDestinataria->nome ?? 'N/A',
            'data_scadenza' => $this->deposito->data_scadenza->format('d/m/Y'),
            'valore_deposito' => $this->deposito->valore_totale_invio,
            'articoli_rimanenti' => $this->deposito->getArticoliRimanenti(),
        ];
    }

    /**
     * Calcola giorni rimanenti alla scadenza
     */
    private function calcolaGiorniRimanenti(): int
    {
        return Carbon::parse($this->deposito->data_scadenza)->diffInDays(now(), false);
    }

    /**
     * Costruisce il messaggio personalizzato
     */
    private function costruisciMessaggio(): array
    {
        $sedeMittente = $this->deposito->sedeMittente->nome ?? 'N/A';
        $sedeDestinataria = $this->deposito->sedeDestinataria->nome ?? 'N/A';
        $codice = $this->deposito->codice;
        $dataScadenza = $this->deposito->data_scadenza->format('d/m/Y');
        $articoliRimanenti = $this->deposito->getArticoliRimanenti();
        $valore = number_format($this->deposito->valore_totale_invio, 2, ',', '.');

        if ($this->tipoAlert === 'scaduto') {
            return [
                'oggetto' => "⏰ URGENTE: Conto deposito {$codice} è SCADUTO",
                'introduzione' => "Il conto deposito {$codice} è SCADUTO da {$this->giorniRimanenti} giorni e richiede attenzione immediata.",
                'dettagli' => "📋 Dettagli:\n• Sede mittente: {$sedeMittente}\n• Sede destinataria: {$sedeDestinataria}\n• Data scadenza: {$dataScadenza}\n• Articoli rimanenti: {$articoliRimanenti}\n• Valore deposito: €{$valore}"
            ];
        } else {
            return [
                'oggetto' => "⚠️ Conto deposito {$codice} in scadenza tra {$this->giorniRimanenti} giorni",
                'introduzione' => "Il conto deposito {$codice} scadrà tra {$this->giorniRimanenti} giorni il {$dataScadenza}.",
                'dettagli' => "📋 Dettagli:\n• Sede mittente: {$sedeMittente}\n• Sede destinataria: {$sedeDestinataria}\n• Data scadenza: {$dataScadenza}\n• Articoli rimanenti: {$articoliRimanenti}\n• Valore deposito: €{$valore}"
            ];
        }
    }
}
