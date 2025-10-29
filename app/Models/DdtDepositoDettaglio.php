<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DdtDepositoDettaglio - Dettagli (righe) dei DDT Deposito
 * 
 * Gestisce le singole righe di articoli/PF nei DDT di deposito
 */
class DdtDepositoDettaglio extends Model
{
    protected $table = 'ddt_depositi_dettagli';
    
    public $timestamps = false; // Solo created_at
    
    protected $fillable = [
        'ddt_deposito_id',
        'articolo_id',
        'prodotto_finito_id',
        'codice_item',
        'descrizione',
        'quantita',
        'valore_unitario',
        'valore_totale',
        'confermato',
        'quantita_ricevuta',
        'note_riga',
    ];
    
    protected $casts = [
        'valore_unitario' => 'decimal:2',
        'valore_totale' => 'decimal:2',
        'confermato' => 'boolean',
        'created_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * DDT deposito di appartenenza
     */
    public function ddtDeposito(): BelongsTo
    {
        return $this->belongsTo(DdtDeposito::class, 'ddt_deposito_id');
    }
    
    /**
     * Articolo (se presente)
     */
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }
    
    /**
     * Prodotto finito (se presente)
     */
    public function prodottoFinito(): BelongsTo
    {
        return $this->belongsTo(ProdottoFinito::class, 'prodotto_finito_id');
    }
    
    // ==========================================
    // BUSINESS METHODS
    // ==========================================
    
    /**
     * Ottieni l'item (articolo o prodotto finito)
     */
    public function getItem()
    {
        return $this->articolo_id ? $this->articolo : $this->prodottoFinito;
    }
    
    /**
     * Verifica se è un articolo
     */
    public function isArticolo(): bool
    {
        return !is_null($this->articolo_id);
    }
    
    /**
     * Verifica se è un prodotto finito
     */
    public function isProdottoFinito(): bool
    {
        return !is_null($this->prodotto_finito_id);
    }
    
    /**
     * Conferma la riga
     */
    public function conferma(?int $quantitaRicevuta = null): bool
    {
        return $this->update([
            'confermato' => true,
            'quantita_ricevuta' => $quantitaRicevuta ?? $this->quantita
        ]);
    }
    
    /**
     * Annulla conferma
     */
    public function annullaConferma(): bool
    {
        return $this->update([
            'confermato' => false,
            'quantita_ricevuta' => null
        ]);
    }
    
    // ==========================================
    // ACCESSORS
    // ==========================================
    
    /**
     * Tipo item per UI
     */
    public function getTipoItemAttribute(): string
    {
        return $this->isArticolo() ? 'Articolo' : 'Prodotto Finito';
    }
    
    /**
     * Quantità confermata o normale
     */
    public function getQuantitaEffettivaAttribute(): int
    {
        return $this->quantita_ricevuta ?? $this->quantita;
    }
    
    /**
     * Differenza tra quantità spedita e ricevuta
     */
    public function getDifferenzaQuantitaAttribute(): int
    {
        if (is_null($this->quantita_ricevuta)) {
            return 0;
        }
        
        return $this->quantita_ricevuta - $this->quantita;
    }
    
    /**
     * Stato della riga per UI
     */
    public function getStatoRigaAttribute(): string
    {
        if (!$this->confermato) {
            return 'In attesa';
        }
        
        if ($this->differenza_quantita == 0) {
            return 'Confermato';
        } elseif ($this->differenza_quantita < 0) {
            return 'Mancante';
        } else {
            return 'Eccedente';
        }
    }
    
    /**
     * Colore stato per UI
     */
    public function getStatoRigaColorAttribute(): string
    {
        return match($this->stato_riga) {
            'In attesa' => 'warning',
            'Confermato' => 'success',
            'Mancante' => 'danger',
            'Eccedente' => 'info',
            default => 'secondary'
        };
    }
}
