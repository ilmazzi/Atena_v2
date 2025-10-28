<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProdottoFinito - Prodotto assemblato da più componenti
 * 
 * Rappresenta un prodotto finito o semilavorato creato
 * assemblando più articoli dal magazzino
 */
class ProdottoFinito extends Model
{
    use SoftDeletes;
    
    protected $table = 'prodotti_finiti';
    
    protected $fillable = [
        'codice',
        'descrizione',
        'tipologia',
        'magazzino_id',
        'peso_totale',
        'materiale_principale',
        'caratura',
        'oro_totale',
        'brillanti_totali',
        'pietre_totali',
        'componenti',
        'lavorazioni',
        'costo_materiali',
        'costo_lavorazione',
        'costo_totale',
        'stato',
        'data_inizio_lavorazione',
        'data_completamento',
        'venduto_il',
        'venduto_a_cliente_id',
        'note',
        'foto_path',
        'creato_da',
        'assemblato_da',
        'articolo_risultante_id',
        'conto_deposito_corrente_id',
        'in_conto_deposito',
    ];
    
    protected $casts = [
        'peso_totale' => 'decimal:2',
        'costo_materiali' => 'decimal:2',
        'costo_lavorazione' => 'decimal:2',
        'costo_totale' => 'decimal:2',
        'componenti' => 'array',
        'lavorazioni' => 'array',
        'data_inizio_lavorazione' => 'date',
        'data_completamento' => 'date',
        'venduto_il' => 'datetime',
        'assemblato_il' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Categoria merceologica del prodotto finito
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMerceologica::class, 'magazzino_id');
    }
    
    /**
     * Alias per compatibilità con il codice delle vetrine
     */
    public function categoriaMerceologica(): BelongsTo
    {
        return $this->categoria();
    }
    
    
    /**
     * Componenti utilizzati (distinta base)
     */
    public function componentiArticoli(): HasMany
    {
        return $this->hasMany(ComponenteProdotto::class, 'prodotto_finito_id');
    }
    
    /**
     * Articolo finale creato in magazzino
     */
    public function articoloRisultante(): HasOne
    {
        return $this->hasOne(Articolo::class, 'prodotto_finito_id');
    }
    
    /**
     * Utente creatore
     */
    public function creatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creato_da');
    }
    
    /**
     * Utente assemblatore
     */
    public function assemblatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assemblato_da');
    }

    /**
     * Conto deposito corrente (se il PF è in deposito)
     */
    public function contoDepositoCorrente(): BelongsTo
    {
        return $this->belongsTo(ContoDeposito::class, 'conto_deposito_corrente_id');
    }

    /**
     * Tutti i movimenti deposito del prodotto finito
     */
    public function movimentiDeposito(): HasMany
    {
        return $this->hasMany(MovimentoDeposito::class, 'prodotto_finito_id');
    }

    // ==========================================
    // BUSINESS LOGIC - STATI PRODOTTI FINITI
    // ==========================================

    /**
     * Calcola lo stato dinamico del prodotto finito
     */
    public function getStatoDinamico(): string
    {
        // 1. Se è in conto deposito
        if ($this->in_conto_deposito) {
            return 'in_conto_deposito';
        }
        
        // 2. Basato sullo stato del prodotto
        return match($this->stato) {
            'completato' => 'disponibile',
            'venduto' => 'scaricato',
            'in_lavorazione' => 'in_lavorazione',
            default => 'sconosciuto'
        };
    }

    /**
     * Verifica se il prodotto finito è in conto deposito
     */
    public function isInContoDeposito(): bool
    {
        return $this->in_conto_deposito && !is_null($this->conto_deposito_corrente_id);
    }

    /**
     * Verifica se il prodotto finito è disponibile per invio in deposito
     */
    public function isDisponibilePerDeposito(): bool
    {
        return $this->stato === 'completato' && !$this->in_conto_deposito;
    }

    /**
     * Aggiorna lo stato del deposito
     */
    public function aggiornaStatoDeposito(): void
    {
        $haMovimentiAttivi = $this->movimentiDeposito()
            ->whereHas('contoDeposito', function($query) {
                $query->where('stato', 'attivo');
            })
            ->where(function($query) {
                $query->where('tipo_movimento', 'invio')
                      ->orWhere('tipo_movimento', 'rimando');
            })
            ->exists();

        $haVenditeOResoAttivi = $this->movimentiDeposito()
            ->whereHas('contoDeposito', function($query) {
                $query->where('stato', 'attivo');
            })
            ->where(function($query) {
                $query->where('tipo_movimento', 'vendita')
                      ->orWhere('tipo_movimento', 'reso');
            })
            ->exists();

        $inDeposito = $haMovimentiAttivi && !$haVenditeOResoAttivi;

        $this->update([
            'in_conto_deposito' => $inDeposito,
            'conto_deposito_corrente_id' => $inDeposito ? 
                $this->movimentiDeposito()
                    ->whereHas('contoDeposito', function($query) {
                        $query->where('stato', 'attivo');
                    })
                    ->latest('data_movimento')
                    ->value('conto_deposito_id') : null
        ]);
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeCompletati($query)
    {
        return $query->where('stato', 'completato');
    }
    
    public function scopeInLavorazione($query)
    {
        return $query->where('stato', 'in_lavorazione');
    }
    
    public function scopeVenduti($query)
    {
        return $query->where('stato', 'venduto');
    }
    
    public function scopeSemilavorati($query)
    {
        return $query->where('tipologia', 'semilavorato');
    }
    
    public function scopeProdottiFiniti($query)
    {
        return $query->where('tipologia', 'prodotto_finito');
    }
    
    // ==========================================
    // ACCESSORS & MUTATORS
    // ==========================================
    
    /**
     * Calcola costo totale automaticamente
     */
    public function calcolaCostoTotale(): float
    {
        $costoComponenti = $this->componentiArticoli()
            ->sum('costo_totale') ?? 0;
        
        return $costoComponenti + ($this->costo_lavorazione ?? 0);
    }
    
    /**
     * Conta componenti
     */
    public function numeroComponenti(): int
    {
        return $this->componentiArticoli()->count();
    }
    
    /**
     * Verifica se completato
     */
    public function isCompletato(): bool
    {
        return $this->stato === 'completato';
    }
    
    /**
     * Verifica se venduto
     */
    public function isVenduto(): bool
    {
        return $this->stato === 'venduto';
    }
    
    /**
     * Descrizione completa per etichetta
     */
    public function getDescrizioneCompletaAttribute(): string
    {
        $desc = $this->descrizione;
        
        if ($this->oro_totale) {
            $desc .= " - Oro: {$this->oro_totale}";
        }
        if ($this->brillanti_totali) {
            $desc .= " - Brill: {$this->brillanti_totali}";
        }
        if ($this->pietre_totali) {
            $desc .= " - Pietre: {$this->pietre_totali}";
        }
        
        return $desc;
    }
}




