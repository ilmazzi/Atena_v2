<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Aggiungi colonne necessarie a DDT e Fatture se non esistono
        if (!Schema::hasColumn('ddt', 'tipo_carico')) {
            Schema::table('ddt', function (Blueprint $table) {
                $table->enum('tipo_carico', ['manuale', 'ocr'])->default('manuale')->after('stato');
                $table->unsignedBigInteger('ocr_document_id')->nullable()->after('tipo_carico');
                $table->unsignedBigInteger('sede_id')->nullable()->after('magazzino_destinazione_id');
                $table->unsignedBigInteger('categoria_id')->nullable()->after('sede_id');
                $table->integer('quantita_totale')->default(0)->after('categoria_id');
                $table->integer('numero_articoli')->default(0)->after('quantita_totale');
                
                $table->foreign('ocr_document_id')->references('id')->on('ocr_documents')->onDelete('set null');
                $table->foreign('sede_id')->references('id')->on('sedi')->onDelete('set null');
                $table->foreign('categoria_id')->references('id')->on('categorie_merceologiche')->onDelete('set null');
            });
        }
        
        if (!Schema::hasColumn('fatture', 'tipo_carico')) {
            Schema::table('fatture', function (Blueprint $table) {
                $table->enum('tipo_carico', ['manuale', 'ocr'])->default('manuale')->after('stato');
                $table->unsignedBigInteger('ocr_document_id')->nullable()->after('tipo_carico');
                $table->unsignedBigInteger('sede_id')->nullable()->after('magazzino_destinazione_id');
                $table->unsignedBigInteger('categoria_id')->nullable()->after('sede_id');
                $table->string('partita_iva', 20)->nullable()->after('categoria_id');
                $table->integer('quantita_totale')->default(0)->after('partita_iva');
                $table->integer('numero_articoli')->default(0)->after('quantita_totale');
                
                $table->foreign('ocr_document_id')->references('id')->on('ocr_documents')->onDelete('set null');
                $table->foreign('sede_id')->references('id')->on('sedi')->onDelete('set null');
                $table->foreign('categoria_id')->references('id')->on('categorie_merceologiche')->onDelete('set null');
            });
        }
        
        // 2. Migra i dati da carichi a ddt/fatture
        DB::statement("
            UPDATE ddt d
            INNER JOIN carichi c ON c.ddt_id = d.id
            SET 
                d.tipo_carico = c.tipo,
                d.ocr_document_id = c.ocr_document_id,
                d.sede_id = c.sede_id,
                d.categoria_id = c.categoria_id,
                d.quantita_totale = c.quantita_totale,
                d.numero_articoli = c.numero_articoli
            WHERE c.ddt_id IS NOT NULL
        ");
        
        DB::statement("
            UPDATE fatture f
            INNER JOIN carichi c ON c.fattura_id = f.id
            SET 
                f.tipo_carico = c.tipo,
                f.ocr_document_id = c.ocr_document_id,
                f.sede_id = c.sede_id,
                f.categoria_id = c.categoria_id,
                f.quantita_totale = c.quantita_totale,
                f.numero_articoli = c.numero_articoli
            WHERE c.fattura_id IS NOT NULL
        ");
        
        // 3. Modifica carico_dettagli per puntare direttamente a ddt/fatture
        Schema::table('carico_dettagli', function (Blueprint $table) {
            // Rimuovi foreign key su carico_id
            $table->dropForeign(['carico_id']);
            
            // Aggiungi colonne per ddt e fatture
            $table->unsignedBigInteger('ddt_id')->nullable()->after('id');
            $table->unsignedBigInteger('fattura_id')->nullable()->after('ddt_id');
            
            $table->foreign('ddt_id')->references('id')->on('ddt')->onDelete('cascade');
            $table->foreign('fattura_id')->references('id')->on('fatture')->onDelete('cascade');
        });
        
        // 4. Migra i riferimenti in carico_dettagli
        DB::statement("
            UPDATE carico_dettagli cd
            INNER JOIN carichi c ON c.id = cd.carico_id
            SET cd.ddt_id = c.ddt_id
            WHERE c.ddt_id IS NOT NULL
        ");
        
        DB::statement("
            UPDATE carico_dettagli cd
            INNER JOIN carichi c ON c.id = cd.carico_id
            SET cd.fattura_id = c.fattura_id
            WHERE c.fattura_id IS NOT NULL
        ");
        
        // 5. Rimuovi colonna carico_id da carico_dettagli
        Schema::table('carico_dettagli', function (Blueprint $table) {
            $table->dropColumn('carico_id');
        });
        
        // 6. Elimina tabella carichi
        Schema::dropIfExists('carichi');
    }

    public function down()
    {
        // Ricrea tabella carichi
        Schema::create('carichi', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['manuale', 'ocr']);
            $table->unsignedBigInteger('ocr_document_id')->nullable();
            $table->unsignedBigInteger('ddt_id')->nullable();
            $table->unsignedBigInteger('fattura_id')->nullable();
            $table->unsignedBigInteger('fornitore_id')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->string('numero_documento', 50);
            $table->date('data_documento');
            $table->enum('tipo_documento', ['ddt', 'fattura']);
            $table->decimal('importo_totale', 10, 2)->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->integer('quantita_totale')->default(0);
            $table->integer('numero_articoli')->default(0);
            $table->enum('stato', ['bozza', 'validato', 'completato'])->default('bozza');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Ripristina carico_id in carico_dettagli
        Schema::table('carico_dettagli', function (Blueprint $table) {
            $table->unsignedBigInteger('carico_id')->after('id');
            $table->foreign('carico_id')->references('id')->on('carichi')->onDelete('cascade');
        });
        
        // Rimuovi colonne da ddt/fatture
        Schema::table('ddt', function (Blueprint $table) {
            $table->dropForeign(['ocr_document_id', 'sede_id', 'categoria_id']);
            $table->dropColumn(['tipo_carico', 'ocr_document_id', 'sede_id', 'categoria_id', 'quantita_totale', 'numero_articoli']);
        });
        
        Schema::table('fatture', function (Blueprint $table) {
            $table->dropForeign(['ocr_document_id', 'sede_id', 'categoria_id']);
            $table->dropColumn(['tipo_carico', 'ocr_document_id', 'sede_id', 'categoria_id', 'partita_iva', 'quantita_totale', 'numero_articoli']);
        });
        
        Schema::table('carico_dettagli', function (Blueprint $table) {
            $table->dropForeign(['ddt_id', 'fattura_id']);
            $table->dropColumn(['ddt_id', 'fattura_id']);
        });
    }
};


