<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MovimentazioneDettaglio - Dettagli articoli nelle movimentazioni
 */
class MovimentazioneDettaglio extends Model
{
    protected $table = 'movimentazioni_dettagli';
    
    public $timestamps = false;
    
    protected $fillable = [
        'movimentazione_id',
        'articolo_id',
        'quantita',
        'note',
    ];
    
    protected $casts = [
        'quantita' => 'integer',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function movimentazione(): BelongsTo
    {
        return $this->belongsTo(Movimentazione::class, 'movimentazione_id');
    }
    
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }
}
