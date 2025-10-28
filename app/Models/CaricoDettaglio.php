<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaricoDettaglio extends Model
{
    use HasFactory;

    protected $table = 'carico_dettagli';

    protected $fillable = [
        'ddt_id',
        'fattura_id',
        'articolo_id',
        'referenza_fornitore',
        'descrizione',
        'quantita',
        'numero_seriale',
        'ean',
        'prezzo_unitario',
        'prezzo_totale',
        'verificato',
        'creato_nuovo',
    ];

    protected $casts = [
        'quantita' => 'integer',
        'prezzo_unitario' => 'decimal:2',
        'prezzo_totale' => 'decimal:2',
        'verificato' => 'boolean',
        'creato_nuovo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relazione con DDT
     */
    public function ddt()
    {
        return $this->belongsTo(Ddt::class, 'ddt_id');
    }
    
    /**
     * Relazione con Fattura
     */
    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'fattura_id');
    }
    
    /**
     * Ottieni il documento (DDT o Fattura)
     */
    public function documento()
    {
        return $this->ddt_id ? $this->ddt : $this->fattura;
    }

    /**
     * Relazione con Articolo (se esistente)
     */
    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'articolo_id');
    }

    /**
     * Verifica se l'articolo esiste già nel sistema
     */
    public function hasArticolo(): bool
    {
        return !is_null($this->articolo_id);
    }

    /**
     * Cerca articolo per referenza fornitore
     */
    public function findArticoloByReferenza()
    {
        return Articolo::whereRaw("JSON_EXTRACT(caratteristiche, '$.referenza') = ?", [$this->referenza_fornitore])->first();
    }
}
