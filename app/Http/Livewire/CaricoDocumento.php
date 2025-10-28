<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\OcrService;
use App\Models\OcrDocument;
use App\Models\CaricoDettaglio;
use App\Models\Articolo;
use App\Models\Giacenza;
use App\Models\Fornitore;
use App\Models\Sede;
use App\Models\CategoriaMerceologica;
use Illuminate\Support\Facades\DB;

class CaricoDocumento extends Component
{
    use WithFileUploads;

    // Step workflow
    public $step = 1; // 1=Upload, 2=Validazione, 3=Completato

    // Upload
    public $pdf;
    public $tipoDocumento = 'ddt';

    // Dati documento estratti
    public $ocrDocumentId;
    public $numeroDocumento;
    public $dataDocumento;
    public $fornitoreId;
    public $sedeId;
    public $categoriaId;
    public $partitaIva;
    public $importoTotale;
    public $confidenceScore = 0;

    // Articoli
    public $articoli = [];

    // Liste dropdown
    public $fornitori = [];
    public $sedi = [];
    public $categorie = [];

    // Regole di validazione (Livewire 2)
    protected $rules = [
        'pdf' => 'required|file|mimes:pdf|max:10240',
        'tipoDocumento' => 'required|in:ddt,fattura',
        'numeroDocumento' => 'required|string|max:50',
        'dataDocumento' => 'required|date',
        'sedeId' => 'required|exists:sedi,id',
        'categoriaId' => 'required|exists:categorie_merceologiche,id',
        'articoli.*.codice' => 'required|string|max:50',
        'articoli.*.quantita' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->fornitori = Fornitore::orderBy('ragione_sociale')->get();
        $this->sedi = Sede::orderBy('nome')->get();
        $this->categorie = CategoriaMerceologica::orderBy('nome')->get();
    }

    /**
     * Step 1: Processa PDF con OCR
     */
    public function processaPdf()
    {
        // Validazione Livewire 2
        $this->validate([
            'pdf' => 'required|mimes:pdf|max:10240',
            'tipoDocumento' => 'required|in:ddt,fattura',
        ]);
        
        try {
            // Processa con OCR
            $ocrService = app(OcrService::class);
            $ocrDocument = $ocrService->processPdf($this->pdf, $this->tipoDocumento);

            // Carica dati estratti
            $this->ocrDocumentId = $ocrDocument->id;
            $this->loadDatiDaOcr($ocrDocument);

            // Vai allo step 2
            $this->step = 2;

            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'OCR Completato!',
                'text' => 'Documento elaborato con successo. Controlla i dati estratti.',
            ]);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Errore OCR',
                'text' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Carica dati da documento OCR
     */
    protected function loadDatiDaOcr(OcrDocument $doc)
    {
        $dati = $doc->ocr_structured_data;

        $this->fornitoreId = $doc->fornitore_id;
        $this->numeroDocumento = $dati['numero'] ?? '';
        $this->dataDocumento = $dati['data'] ?? now()->format('Y-m-d');
        $this->partitaIva = $dati['partita_iva'] ?? '';
        $this->importoTotale = $dati['importo_totale'] ?? null;
        $this->confidenceScore = $doc->confidence_score;
        $this->articoli = $dati['articoli'] ?? [];

        // Verifica esistenza articoli
        foreach ($this->articoli as $index => $articolo) {
            $articoloEsistente = Articolo::where('codice', $articolo['codice'] ?? '')->first();
            $this->articoli[$index]['articolo_id'] = $articoloEsistente?->id;
            $this->articoli[$index]['esiste'] = !is_null($articoloEsistente);
        }
    }

    /**
     * Aggiungi articolo manualmente
     */
    public function aggiungiArticolo()
    {
        $this->articoli[] = [
            'codice' => '',
            'descrizione' => '',
            'quantita' => 1,
            'numero_seriale' => '',
            'ean' => '',
            'articolo_id' => null,
            'esiste' => false,
        ];
    }

    /**
     * Rimuovi articolo
     */
    public function rimuoviArticolo($index)
    {
        unset($this->articoli[$index]);
        $this->articoli = array_values($this->articoli);
    }

    /**
     * Step 2: Salva tutto
     */
    public function salvaCarico()
    {
        // Validazione Livewire 2
        $this->validate();

        DB::beginTransaction();
        try {
            // 1. Crea DDT o Fattura con tutti i dati
            $documento = null;
            $numeroArticoli = 0;
            $quantitaTotale = 0;
            $anno = date('Y', strtotime($this->dataDocumento));
            
            // ⚠️ CONTROLLO DUPLICATI: Verifica se esiste già un documento con stesso numero, anno e fornitore
            if ($this->tipoDocumento === 'ddt') {
                $documentoEsistente = \App\Models\Ddt::where('numero', $this->numeroDocumento)
                    ->where('anno', $anno)
                    ->where('fornitore_id', $this->fornitoreId)
                    ->first();
                
                if ($documentoEsistente) {
                    throw new \Exception("⚠️ DUPLICATO: Esiste già un DDT n. {$this->numeroDocumento}/{$anno} per questo fornitore (ID: {$documentoEsistente->id}). Controlla prima di procedere.");
                }
                
                $documento = \App\Models\Ddt::create([
                    'numero' => $this->numeroDocumento,
                    'anno' => $anno,
                    'data_documento' => $this->dataDocumento,
                    'fornitore_id' => $this->fornitoreId,
                    'sede_id' => $this->sedeId,
                    'categoria_id' => $this->categoriaId,
                    'tipo_carico' => 'ocr',
                    'ocr_document_id' => $this->ocrDocumentId,
                    'stato' => 'caricato',
                    'data_carico' => now(),
                    'user_carico_id' => auth()->id(),
                    'note' => 'Caricato tramite OCR',
                ]);
            } else {
                $documentoEsistente = \App\Models\Fattura::where('numero', $this->numeroDocumento)
                    ->where('anno', $anno)
                    ->where('fornitore_id', $this->fornitoreId)
                    ->first();
                
                if ($documentoEsistente) {
                    throw new \Exception("⚠️ DUPLICATO: Esiste già una Fattura n. {$this->numeroDocumento}/{$anno} per questo fornitore (ID: {$documentoEsistente->id}). Controlla prima di procedere.");
                }
                
                $documento = \App\Models\Fattura::create([
                    'numero' => $this->numeroDocumento,
                    'anno' => $anno,
                    'data_documento' => $this->dataDocumento,
                    'fornitore_id' => $this->fornitoreId,
                    'sede_id' => $this->sedeId,
                    'categoria_id' => $this->categoriaId,
                    'tipo_carico' => 'ocr',
                    'ocr_document_id' => $this->ocrDocumentId,
                    'partita_iva' => $this->partitaIva,
                    'totale' => $this->importoTotale,
                    'stato' => 'caricata',
                    'data_carico' => now(),
                    'user_carico_id' => auth()->id(),
                    'note' => 'Caricata tramite OCR',
                ]);
            }

            // 2. Processa articoli
            foreach ($this->articoli as $articolo) {
                // Crea o aggiorna articolo
                if (empty($articolo['articolo_id'])) {
                    // Genera codice progressivo per magazzino
                    $codiceService = app(\App\Domain\Magazzino\Services\CodiceService::class);
                    $codiceVO = $codiceService->prossimoCodiceDisponibile($this->categoriaId);
                    
                    // Prepara caratteristiche JSON con referenza fornitore
                    $caratteristiche = [
                        'referenza' => $articolo['codice'] ?? '', // Il codice OCR è la referenza
                        'marca' => null,
                        'oro' => null,
                        'pietre' => null,
                        'brill' => null,
                    ];
                    
                    $nuovoArticolo = Articolo::create([
                        'codice' => $codiceVO->toString(), // Es: "2-123"
                        'descrizione' => $articolo['descrizione'] ?? '',
                        'categoria_merceologica_id' => $this->categoriaId,
                        'fornitore_id' => $this->fornitoreId,
                        'ean' => $articolo['ean'] ?? null,
                        'numero_seriale' => $articolo['numero_seriale'] ?? null,
                        'caratteristiche' => json_encode($caratteristiche),
                    ]);
                    $articoloId = $nuovoArticolo->id;
                } else {
                    $articoloId = $articolo['articolo_id'];
                }

                // Crea dettaglio carico (punta direttamente a DDT o Fattura)
                CaricoDettaglio::create([
                    'ddt_id' => $this->tipoDocumento === 'ddt' ? $documento->id : null,
                    'fattura_id' => $this->tipoDocumento === 'fattura' ? $documento->id : null,
                    'articolo_id' => $articoloId,
                    'referenza_fornitore' => $articolo['codice'], // Codice OCR = referenza fornitore
                    'descrizione' => $articolo['descrizione'] ?? '',
                    'quantita' => $articolo['quantita'],
                    'numero_seriale' => $articolo['numero_seriale'] ?? null,
                    'ean' => $articolo['ean'] ?? null,
                    'verificato' => true,
                    'creato_nuovo' => empty($articolo['articolo_id']),
                ]);
                
                // Crea dettaglio DDT o Fattura (per compatibilità con sistema esistente)
                if ($this->tipoDocumento === 'ddt') {
                    \App\Models\DdtDettaglio::create([
                        'ddt_id' => $documento->id,
                        'articolo_id' => $articoloId,
                        'quantita' => $articolo['quantita'],
                        'descrizione' => $articolo['descrizione'] ?? '',
                    ]);
                } else {
                    \App\Models\FatturaDettaglio::create([
                        'fattura_id' => $documento->id,
                        'articolo_id' => $articoloId,
                        'quantita' => $articolo['quantita'],
                        'descrizione' => $articolo['descrizione'] ?? '',
                    ]);
                }

                // Aggiorna/Crea giacenza
                $giacenza = Giacenza::where('articolo_id', $articoloId)
                    ->where('sede_id', $this->sedeId)
                    ->first();

                if ($giacenza) {
                    // Incrementa sia lo storico che il residuo
                    $giacenza->increment('quantita', $articolo['quantita']);
                    $giacenza->increment('quantita_residua', $articolo['quantita']);
                } else {
                    Giacenza::create([
                        'articolo_id' => $articoloId,
                        'sede_id' => $this->sedeId,
                        'quantita' => $articolo['quantita'],
                        'quantita_residua' => $articolo['quantita'],
                        'quantita_iniziale' => $articolo['quantita'],
                    ]);
                }
                
                // Conta articoli e quantità
                $numeroArticoli++;
                $quantitaTotale += $articolo['quantita'];
            }
            
            // Aggiorna il documento con i totali
            $documento->update([
                'numero_articoli' => $numeroArticoli,
                'quantita_totale' => $quantitaTotale,
            ]);

            // 3. Aggiorna OCR document
            if ($this->ocrDocumentId) {
                OcrDocument::find($this->ocrDocumentId)->update([
                    'status' => 'completed',
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
            }

            DB::commit();

            $this->step = 3;

            $this->dispatch('swal:success', 
                title: 'Carico Completato!',
                text: "Documento {$this->numeroDocumento} caricato con successo.",
                timer: 3000
            );

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('swal:error',
                title: 'Errore Salvataggio',
                text: $e->getMessage()
            );
        }
    }

    /**
     * Ricomincia da capo
     */
    public function nuovoCarico()
    {
        $this->reset();
        $this->mount();
    }

    public function render()
    {
        return view('livewire.carico-documento', [
            'title' => 'Carico Documenti'
        ]);
    }
}

