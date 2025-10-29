<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FatturaVendita - Fatture di vendita ai clienti
 * 
 * DIVERSA da Fattura (che è per acquisti dai fornitori)
 * Questa è per le vendite ai clienti dai conti deposito
 */
class FatturaVendita extends Model
{
    use SoftDeletes;
    
    protected $table = 'fatture_vendita';
    
    protected $fillable = [
        'numero',
        'anno',
        'data_documento',
        'cliente_nome',
        'cliente_cognome',
        'cliente_telefono',
        'cliente_email',
        'totale',
        'imponibile',
        'iva',
        'sede_id',
        'conto_deposito_id',
        'ddt_invio_id',
        'quantita_totale',
        'numero_articoli',
        'note',
    ];
    
    protected $casts = [
        'data_documento' => 'date',
        'anno' => 'integer',
        'totale' => 'decimal:2',
        'imponibile' => 'decimal:2',
        'iva' => 'decimal:2',
        'quantita_totale' => 'integer',
        'numero_articoli' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    public function contoDeposito(): BelongsTo
    {
        return $this->belongsTo(ContoDeposito::class, 'conto_deposito_id');
    }
    
    public function ddtInvio(): BelongsTo
    {
        return $this->belongsTo(DdtDeposito::class, 'ddt_invio_id');
    }
    
    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoDeposito::class, 'fattura_vendita_id');
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeAnno($query, int $anno)
    {
        return $query->where('anno', $anno);
    }
    
    public function scopeBySede($query, int $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    /**
     * Nome completo cliente
     */
    public function getClienteNomeCompletoAttribute(): string
    {
        return trim("{$this->cliente_nome} {$this->cliente_cognome}");
    }
}
