<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stampante extends Model
{
    use HasFactory;

    protected $table = 'stampanti';

    protected $fillable = [
        'nome',
        'ip_address',
        'port',
        'modello',
        'categorie_permesse',
        'sedi_permesse',
        'attiva'
    ];

    protected $casts = [
        'categorie_permesse' => 'array',
        'sedi_permesse' => 'array',
        'attiva' => 'boolean'
    ];

    /**
     * Relazione con gli utenti che hanno questa stampante come default
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'stampante_default_id');
    }

    /**
     * Verifica se la stampante può stampare per una determinata categoria
     */
    public function canPrintCategory(int $categoriaId): bool
    {
        return in_array($categoriaId, $this->categorie_permesse ?? []);
    }

    /**
     * Verifica se la stampante può stampare per una determinata sede
     */
    public function canPrintSede(int $sedeId): bool
    {
        return in_array($sedeId, $this->sedi_permesse ?? []);
    }

    /**
     * Verifica se la stampante può stampare un articolo
     */
    public function canPrintArticolo(Articolo $articolo): bool
    {
        return $this->canPrintCategory($articolo->categoria_merceologica_id) &&
               $this->canPrintSede($articolo->sede_id);
    }

    /**
     * Ottieni l'indirizzo completo della stampante
     */
    public function getFullAddressAttribute(): string
    {
        return $this->ip_address . ':' . $this->port;
    }
}