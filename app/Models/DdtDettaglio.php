<?php

namespace App\Models;

use App\Models\Articolo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DDT Dettaglio - Entity
 * 
 * Riga dettaglio DDT (link con articolo caricato)
 */
class DdtDettaglio extends Model
{
    protected $table = 'ddt_dettagli';
    
    public $timestamps = false;
    
    protected $fillable = [
        'ddt_id',
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
    
    public function ddt(): BelongsTo
    {
        return $this->belongsTo(Ddt::class, 'ddt_id');
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

