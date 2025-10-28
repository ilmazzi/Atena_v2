<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticoloStorico extends Model
{
    use HasFactory;
    
    protected $table = 'articoli_storico';

    protected $fillable = [
        'articolo_id_originale',
        'codice',
        'descrizione',
        'dati_completi',
        'relazioni_storico',
        'motivo_eliminazione',
        'sessione_inventario_id',
        'utente_id',
        'data_eliminazione'
    ];

    protected $casts = [
        'dati_completi' => 'array',
        'relazioni_storico' => 'array',
        'data_eliminazione' => 'datetime'
    ];

    /**
     * Relazione con la sessione inventario
     */
    public function sessioneInventario(): BelongsTo
    {
        return $this->belongsTo(InventarioSessione::class, 'sessione_inventario_id');
    }

    /**
     * Relazione con l'utente che ha eliminato
     */
    public function utente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utente_id');
    }

    /**
     * Ottieni il valore dell'articolo eliminato
     */
    public function getValoreEliminatoAttribute(): float
    {
        return $this->dati_completi['prezzo_acquisto'] ?? 0;
    }

    /**
     * Ottieni la categoria dell'articolo eliminato
     */
    public function getCategoriaAttribute(): string
    {
        return $this->dati_completi['categoria_merceologica_id'] ?? 'N/A';
    }

    /**
     * Ottieni la sede dell'articolo eliminato
     */
    public function getSedeAttribute(): string
    {
        return $this->dati_completi['sede_id'] ?? 'N/A';
    }

    /**
     * Ripristina l'articolo (crea nuovo record in articoli)
     */
    public function ripristina(): Articolo
    {
        if (!$this->dati_completi) {
            throw new \Exception("Dati completi mancanti per l'articolo storico ID: {$this->id}");
        }
        
        $datiArticolo = $this->dati_completi;
        
        // Rimuovi campi che non devono essere copiati
        unset($datiArticolo['id'], $datiArticolo['created_at'], $datiArticolo['updated_at']);
        
        // Verifica campi obbligatori
        if (empty($datiArticolo['codice'])) {
            throw new \Exception("Codice articolo mancante per l'articolo storico ID: {$this->id}");
        }
        
        // Verifica se l'articolo esiste già
        $articoloEsistente = Articolo::where('codice', $datiArticolo['codice'])->first();
        if ($articoloEsistente) {
            throw new \Exception("Articolo con codice '{$datiArticolo['codice']}' esiste già (ID: {$articoloEsistente->id}). Non può essere ripristinato.");
        }
        
        // Crea nuovo articolo
        try {
            $nuovoArticolo = Articolo::create($datiArticolo);
        } catch (\Exception $e) {
            throw new \Exception("Errore creazione articolo: " . $e->getMessage());
        }
        
        // Ripristina le giacenze se presenti
        if (isset($this->relazioni_storico['giacenze']) && !empty($this->relazioni_storico['giacenze'])) {
            foreach ($this->relazioni_storico['giacenze'] as $giacenzaData) {
                try {
                    unset($giacenzaData['id'], $giacenzaData['created_at'], $giacenzaData['updated_at']);
                    $giacenzaData['articolo_id'] = $nuovoArticolo->id;
                    \App\Models\Giacenza::create($giacenzaData);
                } catch (\Exception $e) {
                    \Log::warning("Errore ripristino giacenza per articolo {$nuovoArticolo->id}", [
                        'error' => $e->getMessage(),
                        'giacenza_data' => $giacenzaData
                    ]);
                }
            }
        }
        
        // Log dell'operazione
        \Log::info("Articolo ripristinato dallo storico", [
            'articolo_storico_id' => $this->id,
            'nuovo_articolo_id' => $nuovoArticolo->id,
            'codice' => $this->codice,
            'giacenze_ripristinate' => count($this->relazioni_storico['giacenze'] ?? [])
        ]);
        
        return $nuovoArticolo;
    }

    /**
     * Scope per filtrare per motivo eliminazione
     */
    public function scopePerMotivo($query, string $motivo)
    {
        return $query->where('motivo_eliminazione', $motivo);
    }

    /**
     * Scope per filtrare per sessione inventario
     */
    public function scopePerSessione($query, int $sessioneId)
    {
        return $query->where('sessione_inventario_id', $sessioneId);
    }

    /**
     * Scope per filtrare per data eliminazione
     */
    public function scopePerData($query, $dataInizio, $dataFine = null)
    {
        $query->where('data_eliminazione', '>=', $dataInizio);
        
        if ($dataFine) {
            $query->where('data_eliminazione', '<=', $dataFine);
        }
        
        return $query;
    }
}