<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'ocr_document_id',
        'campo',
        'ocr_value',
        'corrected_value',
        'original_confidence',
        'user_id',
        'pattern_notes',
    ];

    protected $casts = [
        'original_confidence' => 'decimal:2',
    ];

    /**
     * Documento OCR di riferimento
     */
    public function ocrDocument(): BelongsTo
    {
        return $this->belongsTo(OcrDocument::class);
    }

    /**
     * Utente che ha effettuato la correzione
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope per campo specifico
     */
    public function scopeCampo($query, string $campo)
    {
        return $query->where('campo', $campo);
    }

    /**
     * Scope per correzioni di un documento
     */
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('ocr_document_id', $documentId);
    }

    /**
     * Scope per correzioni di un utente
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Verifica se la correzione ha migliorato la confidence
     */
    public function hasImprovedConfidence(): bool
    {
        return $this->original_confidence < 70; // Threshold configurabile
    }

    /**
     * Formatta la differenza tra valore OCR e corretto
     */
    public function getDifference(): array
    {
        return [
            'ocr' => $this->ocr_value,
            'corrected' => $this->corrected_value,
            'changed' => $this->ocr_value !== $this->corrected_value,
        ];
    }
}



