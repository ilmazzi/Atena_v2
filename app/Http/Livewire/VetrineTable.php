<?php

namespace App\Http\Livewire;

use App\Models\Vetrina;
use App\Models\CategoriaMerceologica;
use Livewire\Component;
use Livewire\WithPagination;

class VetrineTable extends Component
{
    use WithPagination;

    // Proprietà per filtri e ricerca
    public $search = '';
    public $tipologiaFilter = '';
    public $attivaFilter = '';
    
    // Proprietà per modal creazione/modifica
    public $showModal = false;
    public $editingVetrina = null;
    public $codice = '';
    public $nome = '';
    public $tipologia = 'gioielleria';
    public $ubicazione = '';
    public $attiva = true;
    public $note = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'tipologiaFilter' => ['except' => ''],
        'attivaFilter' => ['except' => ''],
    ];

    protected $rules = [
        'codice' => 'required|string|max:50',
        'nome' => 'required|string|max:255',
        'tipologia' => 'required|in:gioielleria,orologeria',
        'ubicazione' => 'nullable|string|max:255',
        'attiva' => 'boolean',
        'note' => 'nullable|string',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTipologiaFilter()
    {
        $this->resetPage();
    }

    public function updatedAttivaFilter()
    {
        $this->resetPage();
    }

    public function createVetrina()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editVetrina($id)
    {
        $vetrina = Vetrina::findOrFail($id);
        
        $this->editingVetrina = $vetrina;
        $this->codice = $vetrina->codice;
        $this->nome = $vetrina->nome;
        $this->tipologia = $vetrina->tipologia;
        $this->ubicazione = $vetrina->ubicazione;
        $this->attiva = $vetrina->attiva;
        $this->note = $vetrina->note;
        
        $this->showModal = true;
    }

    public function saveVetrina()
    {
        $this->validate();

        try {
            if ($this->editingVetrina) {
                // Aggiorna vetrina esistente
                $this->editingVetrina->update([
                    'codice' => $this->codice,
                    'nome' => $this->nome,
                    'tipologia' => $this->tipologia,
                    'ubicazione' => $this->ubicazione,
                    'attiva' => $this->attiva,
                    'note' => $this->note,
                ]);
                
                session()->flash('success', "Vetrina {$this->codice} aggiornata con successo");
            } else {
                // Crea nuova vetrina
                Vetrina::create([
                    'codice' => $this->codice,
                    'nome' => $this->nome,
                    'tipologia' => $this->tipologia,
                    'ubicazione' => $this->ubicazione,
                    'attiva' => $this->attiva,
                    'note' => $this->note,
                ]);
                
                session()->flash('success', "Vetrina {$this->codice} creata con successo");
            }

            $this->closeModal();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }

    public function deleteVetrina($id)
    {
        try {
            $vetrina = Vetrina::findOrFail($id);
            
            // Verifica se ha articoli
            if ($vetrina->articoli()->count() > 0) {
                session()->flash('error', "Impossibile eliminare la vetrina {$vetrina->codice}: contiene ancora articoli");
                return;
            }
            
            $codice = $vetrina->codice;
            $vetrina->delete();
            
            session()->flash('success', "Vetrina {$codice} eliminata con successo");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingVetrina = null;
        $this->codice = '';
        $this->nome = '';
        $this->tipologia = 'gioielleria';
        $this->ubicazione = '';
        $this->attiva = true;
        $this->note = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $vetrine = Vetrina::query()
            ->withCount('articoli')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('codice', 'like', '%' . $this->search . '%')
                      ->orWhere('nome', 'like', '%' . $this->search . '%')
                      ->orWhere('ubicazione', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->tipologiaFilter, function ($query) {
                $query->where('tipologia', $this->tipologiaFilter);
            })
            ->when($this->attivaFilter !== '', function ($query) {
                $query->where('attiva', $this->attivaFilter);
            })
            ->orderBy('codice')
            ->paginate(20);

        return view('livewire.vetrine-table', [
            'vetrine' => $vetrine,
        ]);
    }
}
