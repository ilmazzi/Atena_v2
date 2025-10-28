<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OcrDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'fornitore_id',
        'pdf_path',
        'pdf_original_name',
        'pdf_size',
        'ocr_raw_data',
        'ocr_structured_data',
        'confidence_score',
        'status',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'ocr_raw_data' => 'array',
        'ocr_structured_data' => 'array',
        'confidence_score' => 'decimal:2',
        'validated_at' => 'datetime',
    ];

    /**
     * Fornitore associato (se riconosciuto)
     */
    public function fornitore(): BelongsTo
    {
        return $this->belongsTo(Fornitore::class);
    }

    /**
     * Utente che ha validato il documento
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Correzioni applicate dall'utente
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(OcrCorrection::class);
    }

    /**
     * Carico associato a questo documento OCR
     * Nota: potrebbe essere un DDT o una Fattura
     */
    public function ddt()
    {
        return $this->hasOne(Ddt::class, 'ocr_document_id');
    }
    
    public function fattura()
    {
        return $this->hasOne(Fattura::class, 'ocr_document_id');
    }

    /**
     * Scope per documenti pendenti
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope per documenti in lavorazione
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope per documenti completati
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope per documenti validati
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope per tipo documento
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope per DDT
     */
    public function scopeDdt($query)
    {
        return $query->where('tipo', 'ddt');
    }

    /**
     * Scope per Fatture
     */
    public function scopeFattura($query)
    {
        return $query->where('tipo', 'fattura');
    }

    /**
     * Verifica se il documento è stato validato
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated' && $this->validated_by !== null;
    }

    /**
     * Verifica se il documento ha una confidence accettabile
     */
    public function hasAcceptableConfidence(): bool
    {
        $threshold = config('ocr.confidence_threshold', 70);
        return $this->confidence_score >= $threshold;
    }

    /**
     * Ottiene il percorso completo del PDF
     */
    public function getPdfFullPath(): string
    {
        return storage_path('app/' . $this->pdf_path);
    }

    /**
     * Ottiene URL pubblico del PDF (se necessario)
     */
    public function getPdfUrl(): string
    {
        return route('ocr.documents.pdf', $this->id);
    }
}

