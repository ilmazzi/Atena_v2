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
        Schema::create('ocr_documents', function (Blueprint $table) {
            $table->id();
            
            // Tipo documento
            $table->enum('tipo', ['ddt', 'fattura'])->index();
            
            // Fornitore (nullable perché potrebbe non essere riconosciuto subito)
            $table->foreignId('fornitore_id')
                  ->nullable()
                  ->constrained('fornitori')
                  ->nullOnDelete();
            
            // Paths
            $table->string('pdf_path');
            $table->string('pdf_original_name');
            $table->integer('pdf_size')->nullable(); // bytes
            
            // OCR Results
            $table->json('ocr_raw_data')->nullable(); // Testo grezzo estratto
            $table->json('ocr_structured_data')->nullable(); // Dati strutturati
            $table->decimal('confidence_score', 5, 2)->default(0); // 0.00-100.00
            
            // Status workflow
            $table->enum('status', ['pending', 'processing', 'completed', 'validated', 'rejected'])
                  ->default('pending')
                  ->index();
            
            // Validazione utente
            $table->foreignId('validated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            
            // Note
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indici per performance
            $table->index('created_at');
            $table->index(['tipo', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_documents');
    }
};
