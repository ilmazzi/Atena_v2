<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ComponenteProdotto - Componente di un prodotto finito
 * 
 * Rappresenta un articolo utilizzato come componente
 * nell'assemblaggio di un prodotto finito
 */
class ComponenteProdotto extends Model
{
    protected $table = 'componenti_prodotto';
    
    protected $fillable = [
        'prodotto_finito_id',
        'articolo_id',
        'quantita',
        'costo_unitario',
        'costo_totale',
        'stato',
        'prelevato_il',
        'prelevato_da',
        'note',
    ];
    
    protected $casts = [
        'quantita' => 'integer',
        'costo_unitario' => 'decimal:2',
        'costo_totale' => 'decimal:2',
        'prelevato_il' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Prodotto finito di appartenenza
     */
    public function prodottoFinito(): BelongsTo
    {
        return $this->belongsTo(ProdottoFinito::class, 'prodotto_finito_id');
    }
    
    /**
     * Articolo utilizzato come componente
     */
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }
    
    /**
     * Utente che ha prelevato il componente
     */
    public function prelievo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prelevato_da');
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeUtilizzati($query)
    {
        return $query->where('stato', 'utilizzato');
    }
    
    public function scopePrelevati($query)
    {
        return $query->where('stato', 'prelevato');
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    /**
     * Calcola costo totale automaticamente
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($componente) {
            // Calcola costo totale automaticamente
            if ($componente->costo_unitario && $componente->quantita) {
                $componente->costo_totale = $componente->costo_unitario * $componente->quantita;
            }
        });
    }
    
    /**
     * Verifica se utilizzato
     */
    public function isUtilizzato(): bool
    {
        return $this->stato === 'utilizzato';
    }
    
    /**
     * Ottieni dati gioielleria dal componente
     */
    public function getDatiGioielleria(): array
    {
        if (!$this->articolo) {
            return [];
        }
        
        $caratteristiche = is_string($this->articolo->caratteristiche)
            ? json_decode($this->articolo->caratteristiche, true)
            : $this->articolo->caratteristiche;
        
        return [
            'oro' => $caratteristiche['oro'] ?? null,
            'brillanti' => $caratteristiche['brill'] ?? null,
            'pietre' => $caratteristiche['pietre'] ?? null,
        ];
    }
}




