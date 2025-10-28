<?php

namespace App\Http\Livewire;

use App\Models\Ddt;
use App\Models\Fattura;
use App\Models\Fornitore;
use App\Models\CategoriaMerceologica;
use App\Models\Sede;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.vertical', ['title' => 'Documenti di Acquisto'])]
class DocumentiAcquistoTable extends Component
{
    use WithPagination;

    // Filtri
    public $search = '';
    public $tipoDocumento = ''; // 'ddt', 'fattura', ''
    public $tipoCarico = ''; // 'ocr', 'manuale', ''
    public $fornitoreFilter = '';
    public $sedeFilter = '';
    public $categoriaFilter = '';
    public $statoFilter = '';
    public $dataFrom = '';
    public $dataTo = '';
    public $nascondiVuoti = true; // Nascondi DDT senza articoli
    
    // Paginazione e ordinamento
    public $perPage = 25;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Modal edit
    public $editingDocId = null;
    public $editingDocTipo = null;
    public $editForm = [
        'numero_documento' => '',
        'data_documento' => '',
        'fornitore_id' => '',
        'partita_iva' => '',
        'importo_totale' => '',
        'note' => '',
    ];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'tipoDocumento' => ['except' => ''],
        'tipoCarico' => ['except' => ''],
        'fornitoreFilter' => ['except' => ''],
        'sedeFilter' => ['except' => ''],
        'categoriaFilter' => ['except' => ''],
        'statoFilter' => ['except' => ''],
        'dataFrom' => ['except' => ''],
        'dataTo' => ['except' => ''],
        'nascondiVuoti' => ['except' => true],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTipoDocumento()
    {
        $this->resetPage();
    }

    public function updatingTipoCarico()
    {
        $this->resetPage();
    }

    public function updatingFornitoreFilter()
    {
        $this->resetPage();
    }

    public function updatingSedeFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoriaFilter()
    {
        $this->resetPage();
    }

    public function updatingStatoFilter()
    {
        $this->resetPage();
    }

    public function updatingDataFrom()
    {
        $this->resetPage();
    }

    public function updatingDataTo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->tipoDocumento = '';
        $this->tipoCarico = '';
        $this->fornitoreFilter = '';
        $this->sedeFilter = '';
        $this->categoriaFilter = '';
        $this->statoFilter = '';
        $this->dataFrom = '';
        $this->dataTo = '';
        $this->nascondiVuoti = true;
        $this->resetPage();
    }

    public function editDocument($tipo, $id)
    {
        $this->editingDocTipo = $tipo;
        $this->editingDocId = $id;
        
        if ($tipo === 'ddt') {
            $doc = Ddt::findOrFail($id);
        } else {
            $doc = Fattura::findOrFail($id);
        }
        
        $this->editForm = [
            'numero_documento' => $doc->numero,
            'data_documento' => $doc->data_documento,
            'fornitore_id' => $doc->fornitore_id ?? '',
            'partita_iva' => $doc->partita_iva ?? '',
            'importo_totale' => $tipo === 'fattura' ? $doc->totale : '',
            'note' => $doc->note ?? '',
        ];
        
        $this->dispatch('open-edit-modal');
    }

    public function updateDocument()
    {
        $this->validate([
            'editForm.numero_documento' => 'required|string|max:50',
            'editForm.data_documento' => 'required|date',
            'editForm.fornitore_id' => 'nullable|exists:fornitori,id',
            'editForm.partita_iva' => 'nullable|string|max:20',
            'editForm.importo_totale' => 'nullable|numeric|min:0',
            'editForm.note' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        try {
            if ($this->editingDocTipo === 'ddt') {
                $documento = Ddt::findOrFail($this->editingDocId);
                $documento->update([
                    'numero' => $this->editForm['numero_documento'],
                    'data_documento' => $this->editForm['data_documento'],
                    'anno' => date('Y', strtotime($this->editForm['data_documento'])),
                    'fornitore_id' => $this->editForm['fornitore_id'] ?: null,
                    'note' => $this->editForm['note'],
                ]);
            } else {
                $documento = Fattura::findOrFail($this->editingDocId);
                $documento->update([
                    'numero' => $this->editForm['numero_documento'],
                    'data_documento' => $this->editForm['data_documento'],
                    'anno' => date('Y', strtotime($this->editForm['data_documento'])),
                    'fornitore_id' => $this->editForm['fornitore_id'] ?: null,
                    'totale' => $this->editForm['importo_totale'],
                    'partita_iva' => $this->editForm['partita_iva'],
                    'note' => $this->editForm['note'],
                ]);
            }
            
            DB::commit();
            
            $this->dispatch('close-edit-modal');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Documento aggiornato con successo!'
            ]);
            
            $this->editingDocId = null;
            $this->editingDocTipo = null;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        // Recupera DDT
        $ddtQuery = Ddt::with(['fornitore', 'userCarico', 'categoria', 'sede', 'ocrDocument'])
            ->whereNotNull('tipo_carico');
        
        // Recupera Fatture
        $fattureQuery = Fattura::with(['fornitore', 'categoria', 'sede', 'ocrDocument'])
            ->whereNotNull('tipo_carico');
        
        // Applica filtri comuni
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $ddtQuery->where(function($q) use ($searchTerm) {
                $q->where('numero', 'like', $searchTerm)
                  ->orWhereHas('fornitore', function($subQ) use ($searchTerm) {
                      $subQ->where('ragione_sociale', 'like', $searchTerm);
                  });
            });
            $fattureQuery->where(function($q) use ($searchTerm) {
                $q->where('numero', 'like', $searchTerm)
                  ->orWhereHas('fornitore', function($subQ) use ($searchTerm) {
                      $subQ->where('ragione_sociale', 'like', $searchTerm);
                  });
            });
        }
        
        if ($this->tipoCarico) {
            $ddtQuery->where('tipo_carico', $this->tipoCarico);
            $fattureQuery->where('tipo_carico', $this->tipoCarico);
        }
        
        if ($this->fornitoreFilter) {
            $ddtQuery->where('fornitore_id', $this->fornitoreFilter);
            $fattureQuery->where('fornitore_id', $this->fornitoreFilter);
        }
        
        if ($this->sedeFilter) {
            $ddtQuery->where('sede_id', $this->sedeFilter);
            $fattureQuery->where('sede_id', $this->sedeFilter);
        }
        
        if ($this->categoriaFilter) {
            $ddtQuery->where('categoria_merceologica_id', $this->categoriaFilter);
            $fattureQuery->where('categoria_merceologica_id', $this->categoriaFilter);
        }
        
        if ($this->statoFilter) {
            $ddtQuery->where('stato', $this->statoFilter);
            $fattureQuery->where('stato', $this->statoFilter);
        }
        
        if ($this->dataFrom) {
            $ddtQuery->where('data_documento', '>=', $this->dataFrom);
            $fattureQuery->where('data_documento', '>=', $this->dataFrom);
        }
        
        if ($this->dataTo) {
            $ddtQuery->where('data_documento', '<=', $this->dataTo);
            $fattureQuery->where('data_documento', '<=', $this->dataTo);
        }
        
        // Nascondi documenti vuoti (senza articoli)
        if ($this->nascondiVuoti) {
            $ddtQuery->where('numero_articoli', '>', 0);
            $fattureQuery->where('numero_articoli', '>', 0);
        }
        
        // Se filtra per tipo documento, carica solo quello
        if ($this->tipoDocumento === 'ddt') {
            $ddt = $ddtQuery->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage);
            $documenti = $ddt->map(function($doc) {
                $doc->tipo_documento = 'ddt';
                return $doc;
            });
        } elseif ($this->tipoDocumento === 'fattura') {
            $fatture = $fattureQuery->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage);
            $documenti = $fatture->map(function($doc) {
                $doc->tipo_documento = 'fattura';
                return $doc;
            });
        } else {
            // Unisci entrambi
            $ddt = $ddtQuery->orderBy($this->sortField, $this->sortDirection)->get();
            $fatture = $fattureQuery->orderBy($this->sortField, $this->sortDirection)->get();
            
            $ddt = $ddt->map(function($doc) {
                $doc->tipo_documento = 'ddt';
                return $doc;
            });
            
            $fatture = $fatture->map(function($doc) {
                $doc->tipo_documento = 'fattura';
                return $doc;
            });
            
            $allDocumenti = $ddt->concat($fatture);
            
            // Ordina la collezione unita
            if ($this->sortDirection === 'asc') {
                $allDocumenti = $allDocumenti->sortBy($this->sortField);
            } else {
                $allDocumenti = $allDocumenti->sortByDesc($this->sortField);
            }
            
            // Paginazione manuale per Livewire 3
            $currentPage = request()->get('page', 1);
            $documenti = new \Illuminate\Pagination\LengthAwarePaginator(
                $allDocumenti->forPage($currentPage, $this->perPage)->values(),
                $allDocumenti->count(),
                $this->perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }
        
        // Statistiche
        $stats = [
            'totali' => Ddt::whereNotNull('tipo_carico')->count() + Fattura::whereNotNull('tipo_carico')->count(),
            'ddt' => Ddt::whereNotNull('tipo_carico')->count(),
            'fatture' => Fattura::whereNotNull('tipo_carico')->count(),
            'ocr' => Ddt::where('tipo_carico', 'ocr')->count() + Fattura::where('tipo_carico', 'ocr')->count(),
            'manuali' => Ddt::where('tipo_carico', 'manuale')->count() + Fattura::where('tipo_carico', 'manuale')->count(),
        ];
        
        // Opzioni per filtri
        $fornitori = Fornitore::where('attivo', true)
            ->orderBy('ragione_sociale')
            ->get(['id', 'ragione_sociale']);
        
        $categorie = CategoriaMerceologica::where('attivo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'codice']);
        
        $sedi = Sede::orderBy('nome')->get(['id', 'nome']);
        
        return view('livewire.documenti-acquisto-table', compact('documenti', 'stats', 'fornitori', 'categorie', 'sedi'));
    }
}

