<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimentazione - Entity per trasferimenti tra magazzini
 * 
 * Business Rules:
 * - Traccia spostamento articoli tra sedi
 * - Decrementa giacenza mittente
 * - Incrementa giacenza destinatario
 * - Può avere causale (vendita, trasferimento, reso, riparazione)
 */
class Movimentazione extends Model
{
    protected $table = 'movimentazioni';
    
    public $timestamps = true;
    
    protected $fillable = [
        'numero_documento',
        'magazzino_partenza_id',
        'magazzino_destinazione_id',
        'data_movimentazione',
        'data_prevista',
        'stato',
        'creata_da',
        'confermata_da',
        'confermata_at',
        'completata_at',
        'note',
        'causale',
    ];
    
    protected $casts = [
        'data_movimentazione' => 'date',
        'data_prevista' => 'date',
        'confermata_at' => 'datetime',
        'completata_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function dettagli()
    {
        return $this->hasMany(MovimentazioneDettaglio::class, 'movimentazione_id');
    }
    
    public function magazzinoPartenza(): BelongsTo
    {
        return $this->belongsTo(CategoriaMerceologica::class, 'magazzino_partenza_id');
    }
    
    public function magazzinoDestinazione(): BelongsTo
    {
        return $this->belongsTo(CategoriaMerceologica::class, 'magazzino_destinazione_id');
    }
    
    public function creataDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creata_da');
    }
    
    public function confermataDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confermata_da');
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeDelMagazzino($query, int $magazzinoId)
    {
        return $query->where('magazzino_partenza_id', $magazzinoId)
                     ->orWhere('magazzino_destinazione_id', $magazzinoId);
    }
    
    public function scopeInUscita($query, int $magazzinoId)
    {
        return $query->where('magazzino_partenza_id', $magazzinoId);
    }
    
    public function scopeInEntrata($query, int $magazzinoId)
    {
        return $query->where('magazzino_destinazione_id', $magazzinoId);
    }
    
    public function scopeNelPeriodo($query, \DateTime $da, \DateTime $a)
    {
        return $query->whereBetween('data_movimentazione', [$da, $a]);
    }
    
    public function scopeConfermate($query)
    {
        return $query->where('stato', 'confermata');
    }
    
    public function scopeCompletate($query)
    {
        return $query->where('stato', 'completata');
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    /**
     * Verifica se è un trasferimento interno
     */
    public function isTrasferimentoInterno(): bool
    {
        return $this->magazzino_partenza_id !== null 
            && $this->magazzino_destinazione_id !== null;
    }
    
    /**
     * Verifica se è un carico (da esterno)
     */
    public function isCarico(): bool
    {
        return $this->magazzino_partenza_id === null;
    }
    
    /**
     * Verifica se è uno scarico (verso esterno)
     */
    public function isScarico(): bool
    {
        return $this->magazzino_destinazione_id === null;
    }
    
    /**
     * Verifica se è confermata
     */
    public function isConfermata(): bool
    {
        return $this->stato === 'confermata';
    }
    
    /**
     * Verifica se è completata
     */
    public function isCompletata(): bool
    {
        return $this->stato === 'completata';
    }
}

