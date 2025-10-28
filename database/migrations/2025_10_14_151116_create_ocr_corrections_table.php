<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ocr_corrections', function (Blueprint $table) {
            $table->id();
            
            // Documento OCR di riferimento
            $table->foreignId('ocr_document_id')
                  ->constrained('ocr_documents')
                  ->cascadeOnDelete();
            
            // Campo corretto
            $table->string('campo'); // 'numero_ddt', 'data', 'fornitore', 'articolo_descrizione', etc.
            $table->text('ocr_value')->nullable(); // Valore estratto da OCR
            $table->text('corrected_value'); // Valore corretto dall'utente
            
            // Confidence originale del campo
            $table->decimal('original_confidence', 5, 2)->nullable();
            
            // Utente che ha fatto la correzione
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            // Pattern riconosciuto (per machine learning)
            $table->text('pattern_notes')->nullable();
            
            $table->timestamps();
            
            // Indici
            $table->index('campo');
            $table->index('created_at');
            $table->index(['ocr_document_id', 'campo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_corrections');
    }
};
