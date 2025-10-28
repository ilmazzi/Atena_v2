<?php

namespace App\Models;

use App\Models\Articolo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fattura Dettaglio - Entity
 * 
 * Riga dettaglio Fattura (link con articolo caricato)
 */
class FatturaDettaglio extends Model
{
    protected $table = 'fatture_dettagli';
    
    public $timestamps = false;
    
    protected $fillable = [
        'fattura_id',
        'articolo_id',
        'quantita',
        'caricato',
    ];
    
    protected $casts = [
        'quantita' => 'integer',
        'caricato' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function fattura(): BelongsTo
    {
        return $this->belongsTo(Fattura::class, 'fattura_id');
    }
    
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    public function isCaricato(): bool
    {
        return $this->caricato === true;
    }
}

