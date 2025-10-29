<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ArticoloStoricoCosto - Storico costi articoli
 * 
 * Traccia tutte le modifiche al prezzo_acquisto degli articoli
 */
class ArticoloStoricoCosto extends Model
{
    protected $table = 'articolo_storico_costi';
    
    protected $fillable = [
        'articolo_id',
        'costo_precedente',
        'costo_nuovo',
        'fattura_id',
        'user_id',
        'note',
    ];
    
    protected $casts = [
        'costo_precedente' => 'decimal:2',
        'costo_nuovo' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }
    
    public function fattura(): BelongsTo
    {
        return $this->belongsTo(Fattura::class, 'fattura_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

