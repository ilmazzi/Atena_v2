<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CategoriaMerceologica;
use App\Models\Sede;

/**
 * GestioneMagazzini - CRUD per categorie merceologiche (magazzini)
 */
class GestioneMagazzini extends Component
{
    use WithPagination;

    // Filtri e ricerca
    public $search = '';
    public $filtroAttivo = '';
    public $filtroSede = '';

    // Modali
    public $showModal = false;
    public $showDeleteModal = false;
    public $modalMode = 'create';
    public $magazzinoSelezionatoId = null;

    // Form fields
    public $codice = '';
    public $nome = '';
    public $sede_id = '';
    public $attivo = true;
    public $note = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroAttivo' => ['except' => ''],
        'filtroSede' => ['except' => ''],
    ];

    protected $rules = [
        'codice' => 'required|string|max:50|unique:categorie_merceologiche,codice',
        'nome' => 'required|string|max:255',
        'sede_id' => 'required|exists:sedi,id',
        'attivo' => 'boolean',
        'note' => 'nullable|string',
    ];

    protected $messages = [
        'codice.unique' => 'Il codice magazzino è già esistente',
        'sede_id.required' => 'La sede è obbligatoria',
    ];

    public function mount()
    {
        // 
    }

    // Reset paginazione quando cambiano i filtri
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFiltroAttivo()
    {
        $this->resetPage();
    }

    public function updatingFiltroSede()
    {
        $this->resetPage();
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getMagazziniProperty()
    {
        $query = CategoriaMerceologica::with('sede');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('codice', 'like', "%{$this->search}%")
                  ->orWhere('nome', 'like', "%{$this->search}%");
            });
        }

        if ($this->filtroAttivo !== '') {
            $query->where('attivo', $this->filtroAttivo === 'si');
        }

        if ($this->filtroSede) {
            $query->where('sede_id', $this->filtroSede);
        }

        return $query->orderBy('nome')->paginate(15);
    }

    public function getSediProperty()
    {
        return Sede::where('attivo', true)->orderBy('nome')->get();
    }

    // ==========================================
    // ACTIONS
    // ==========================================

    public function apriModalCreazione()
    {
        $this->resetForm();
        $this->modalMode = 'create';
        $this->showModal = true;
    }

    public function apriModalModifica($magazzinoId)
    {
        $magazzino = CategoriaMerceologica::findOrFail($magazzinoId);
        
        $this->magazzinoSelezionatoId = $magazzino->id;
        $this->codice = $magazzino->codice;
        $this->nome = $magazzino->nome;
        $this->sede_id = $magazzino->sede_id;
        $this->attivo = $magazzino->attivo;
        $this->note = $magazzino->note ?? '';
        
        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function salva()
    {
        if ($this->modalMode === 'edit') {
            $this->rules['codice'] = 'required|string|max:50|unique:categorie_merceologiche,codice,' . $this->magazzinoSelezionatoId;
        }

        $this->validate();

        $data = [
            'codice' => strtoupper($this->codice),
            'nome' => $this->nome,
            'sede_id' => $this->sede_id,
            'attivo' => $this->attivo,
            'note' => $this->note ?: null,
        ];

        if ($this->modalMode === 'create') {
            CategoriaMerceologica::create($data);
            session()->flash('message', '✅ Magazzino creato con successo');
        } else {
            CategoriaMerceologica::find($this->magazzinoSelezionatoId)->update($data);
            session()->flash('message', '✅ Magazzino aggiornato con successo');
        }

        $this->chiudiModal();
        $this->resetForm();
    }

    public function apriModalEliminazione($magazzinoId)
    {
        $this->magazzinoSelezionatoId = $magazzinoId;
        $this->showDeleteModal = true;
    }

    public function elimina()
    {
        $magazzino = CategoriaMerceologica::findOrFail($this->magazzinoSelezionatoId);
        
        // Verifica se ha articoli o giacenze associate
        if ($magazzino->articoli()->count() > 0 || $magazzino->giacenze()->count() > 0) {
            session()->flash('error', '❌ Impossibile eliminare: ci sono articoli o giacenze associate');
            $this->chiudiModalEliminazione();
            return;
        }

        $magazzino->delete();
        session()->flash('message', '✅ Magazzino eliminato con successo');
        $this->chiudiModalEliminazione();
    }

    public function chiudiModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function chiudiModalEliminazione()
    {
        $this->showDeleteModal = false;
        $this->magazzinoSelezionatoId = null;
    }

    public function resetForm()
    {
        $this->magazzinoSelezionatoId = null;
        $this->codice = '';
        $this->nome = '';
        $this->sede_id = '';
        $this->attivo = true;
        $this->note = '';
        $this->modalMode = 'create';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.gestione-magazzini', [
            'magazzini' => $this->magazzini,
            'sediList' => $this->sedi,
        ]);
    }
}
