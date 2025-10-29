<?php

namespace App\Models;

use App\Models\CategoriaMerceologica;
use App\Models\ContoDeposito;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Societa - Modello per gestione multi-società
 * 
 * Rappresenta una società che utilizza Athena v2
 * - De Pascalis s.p.a. (DP)
 * - Luigi De Pascalis (LDP)
 */
class Societa extends Model
{
    use SoftDeletes;
    
    protected $table = 'societa';
    
    protected $fillable = [
        'codice',
        'ragione_sociale',
        'partita_iva',
        'codice_fiscale',
        'indirizzo',
        'citta',
        'provincia',
        'cap',
        'telefono',
        'email',
        'pec',
        'email_notifiche',
        'configurazione',
        'attivo',
        'note',
    ];
    
    protected $casts = [
        'email_notifiche' => 'array',
        'configurazione' => 'array',
        'attivo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Sedi appartenenti a questa società
     */
    public function sedi(): HasMany
    {
        return $this->hasMany(Sede::class, 'societa_id');
    }
    
    /**
     * Conti deposito di questa società (come mittente)
     */
    public function contiDepositoMittente(): HasManyThrough
    {
        return $this->hasManyThrough(
            ContoDeposito::class,
            Sede::class,
            'societa_id',
            'sede_mittente_id',
            'id',
            'id'
        );
    }
    
    /**
     * Conti deposito di questa società (come destinataria)
     */
    public function contiDepositoDestinataria(): HasManyThrough
    {
        return $this->hasManyThrough(
            ContoDeposito::class,
            Sede::class,
            'societa_id',
            'sede_destinataria_id',
            'id',
            'id'
        );
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeAttive($query)
    {
        return $query->where('attivo', true);
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    /**
     * Ottieni prefisso per numerazione DDT invio
     */
    public function getPrefissoDdtInvio(): string
    {
        return $this->configurazione['prefisso_ddt'] ?? "DEP-{$this->codice}";
    }
    
    /**
     * Ottieni prefisso per numerazione DDT reso
     */
    public function getPrefissoDdtReso(): string
    {
        return $this->configurazione['prefisso_reso'] ?? "RES-{$this->codice}";
    }
    
    /**
     * Ottieni prefisso per numerazione conti deposito
     */
    public function getPrefissoContoDeposito(): string
    {
        return $this->configurazione['prefisso_conto_deposito'] ?? "CD-{$this->codice}";
    }
    
    /**
     * Ottieni email per notifiche conti deposito
     */
    public function getEmailNotifiche(): array
    {
        return $this->email_notifiche ?? ($this->email ? [$this->email] : []);
    }
    
    /**
     * Verifica se la società è attiva
     */
    public function isAttiva(): bool
    {
        return $this->attivo === true;
    }
    
    /**
     * Ottieni il magazzino "Conto Deposito" di questa società
     * 
     * @return CategoriaMerceologica|null
     */
    public function getMagazzinoContoDeposito(): ?CategoriaMerceologica
    {
        $codiceMagazzino = "CD-{$this->codice}";
        
        // Cerca nella sede principale (prima sede attiva)
        $sedePrincipale = $this->sedi()->where('attivo', true)->orderBy('id')->first();
        
        if (!$sedePrincipale) {
            return null;
        }
        
        return CategoriaMerceologica::where('sede_id', $sedePrincipale->id)
            ->where('codice', $codiceMagazzino)
            ->first();
    }
}
