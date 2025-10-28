<?php

namespace App\Models;

use App\Models\Giacenza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ubicazione - Entity
 * 
 * Rappresenta un'ubicazione fisica specifica all'interno di una sede
 * Gerarchia: Sede > Scaffale > Ripiano > Box > Posizione
 * 
 * Esempio: "CAVOUR > Scaffale A > Ripiano 2 > Box 3"
 */
class Ubicazione extends Model
{
    use SoftDeletes;
    
    protected $table = 'ubicazioni';
    
    protected $fillable = [
        'sede_id',
        'scaffale',
        'ripiano',
        'box',
        'posizione',
        'codice',
        'descrizione',
        'capacita_massima',
        'articoli_presenti',
        'attivo',
        'note',
    ];
    
    protected $casts = [
        'capacita_massima' => 'integer',
        'articoli_presenti' => 'integer',
        'attivo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Sede di appartenenza
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    
    /**
     * Giacenze presenti in questa ubicazione
     */
    public function giacenze(): HasMany
    {
        return $this->hasMany(Giacenza::class, 'ubicazione_id');
    }
    
    // ==========================================
    // SCOPES
    // ==========================================
    
    public function scopeAttive($query)
    {
        return $query->where('attivo', true);
    }
    
    public function scopeBySede($query, int $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }
    
    public function scopeDisponibili($query)
    {
        return $query->whereRaw('articoli_presenti < capacita_massima');
    }
    
    // ==========================================
    // BUSINESS LOGIC
    // ==========================================
    
    public function isDisponibile(): bool
    {
        return $this->attivo && $this->articoli_presenti < $this->capacita_massima;
    }
    
    public function isFull(): bool
    {
        return $this->articoli_presenti >= $this->capacita_massima;
    }
    
    /**
     * Percentuale riempimento
     */
    public function getPercentualeRiempimentoAttribute(): float
    {
        if ($this->capacita_massima == 0) {
            return 0;
        }
        return ($this->articoli_presenti / $this->capacita_massima) * 100;
    }
    
    /**
     * Incrementa contatore articoli presenti
     */
    public function incrementaArticoli(int $quantita = 1): void
    {
        $this->increment('articoli_presenti', $quantita);
    }
    
    /**
     * Decrementa contatore articoli presenti
     */
    public function decrementaArticoli(int $quantita = 1): void
    {
        $this->decrement('articoli_presenti', $quantita);
    }
    
    /**
     * Genera codice univoco
     * Esempio: "CAV-A-2-BOX3"
     */
    public static function generaCodice(int $sedeId, string $scaffale, ?string $ripiano = null, ?string $box = null, ?string $posizione = null): string
    {
        $sede = Sede::find($sedeId);
        if (!$sede) {
            throw new \Exception("Sede non trovata");
        }
        
        $parts = [
            $sede->codice,
            $scaffale,
        ];
        
        if ($ripiano) $parts[] = $ripiano;
        if ($box) $parts[] = $box;
        if ($posizione) $parts[] = $posizione;
        
        return strtoupper(implode('-', $parts));
    }
    
    /**
     * Descrizione completa gerarchica
     */
    public function getDescrizioneCompletaAttribute(): string
    {
        $parts = [$this->sede->nome];
        
        if ($this->scaffale) $parts[] = "Scaffale {$this->scaffale}";
        if ($this->ripiano) $parts[] = "Ripiano {$this->ripiano}";
        if ($this->box) $parts[] = "Box {$this->box}";
        if ($this->posizione) $parts[] = $this->posizione;
        
        return implode(' > ', $parts);
    }
    
    /**
     * Observer: Genera codice automaticamente prima del save
     */
    protected static function booted()
    {
        static::creating(function ($ubicazione) {
            if (empty($ubicazione->codice)) {
                $ubicazione->codice = self::generaCodice(
                    $ubicazione->sede_id,
                    $ubicazione->scaffale,
                    $ubicazione->ripiano,
                    $ubicazione->box,
                    $ubicazione->posizione
                );
            }
        });
    }
}

