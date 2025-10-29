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
        'prezzo_unitario',
        'totale_riga',
        'codice_articolo',
        'descrizione',
        'caricato',
    ];
    
    protected $casts = [
        'quantita' => 'integer',
        'prezzo_unitario' => 'decimal:2',
        'totale_riga' => 'decimal:2',
        'sconto_percentuale' => 'decimal:2',
        'iva_percentuale' => 'decimal:2',
        'caricato' => 'boolean',
        'data_carico_riga' => 'date',
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

