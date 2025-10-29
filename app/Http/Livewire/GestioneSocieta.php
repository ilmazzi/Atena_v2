<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Societa;

/**
 * GestioneSocieta - CRUD per società
 */
class GestioneSocieta extends Component
{
    use WithPagination;

    // Filtri e ricerca
    public $search = '';
    public $filtroAttivo = '';

    // Modali
    public $showModal = false;
    public $showDeleteModal = false;
    public $modalMode = 'create'; // create | edit
    public $societaSelezionataId = null;

    // Form fields
    public $codice = '';
    public $ragione_sociale = '';
    public $partita_iva = '';
    public $codice_fiscale = '';
    public $indirizzo = '';
    public $citta = '';
    public $provincia = '';
    public $cap = '';
    public $telefono = '';
    public $email = '';
    public $pec = '';
    public $email_notifiche = [];
    public $email_notifica_input = '';
    public $attivo = true;
    public $note = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroAttivo' => ['except' => ''],
    ];

    protected $rules = [
        'codice' => 'required|string|max:10|unique:societa,codice',
        'ragione_sociale' => 'required|string|max:255',
        'partita_iva' => 'nullable|string|max:20',
        'codice_fiscale' => 'nullable|string|max:20',
        'indirizzo' => 'nullable|string|max:255',
        'citta' => 'nullable|string|max:100',
        'provincia' => 'nullable|string|max:2',
        'cap' => 'nullable|string|max:10',
        'telefono' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
        'pec' => 'nullable|email|max:255',
        'attivo' => 'boolean',
        'note' => 'nullable|string',
    ];

    protected $messages = [
        'codice.unique' => 'Il codice società è già esistente',
        'codice.required' => 'Il codice è obbligatorio',
        'ragione_sociale.required' => 'La ragione sociale è obbligatoria',
    ];

    public function mount()
    {
        // Reset paginazione se necessario
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getSocietaProperty()
    {
        $query = Societa::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('codice', 'like', "%{$this->search}%")
                  ->orWhere('ragione_sociale', 'like', "%{$this->search}%")
                  ->orWhere('partita_iva', 'like', "%{$this->search}%");
            });
        }

        if ($this->filtroAttivo !== '') {
            $query->where('attivo', $this->filtroAttivo === 'si');
        }

        return $query->with('sedi')->orderBy('codice')->paginate(15);
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

    public function apriModalModifica($societaId)
    {
        $societa = Societa::findOrFail($societaId);
        
        $this->societaSelezionataId = $societa->id;
        $this->codice = $societa->codice;
        $this->ragione_sociale = $societa->ragione_sociale;
        $this->partita_iva = $societa->partita_iva ?? '';
        $this->codice_fiscale = $societa->codice_fiscale ?? '';
        $this->indirizzo = $societa->indirizzo ?? '';
        $this->citta = $societa->citta ?? '';
        $this->provincia = $societa->provincia ?? '';
        $this->cap = $societa->cap ?? '';
        $this->telefono = $societa->telefono ?? '';
        $this->email = $societa->email ?? '';
        $this->pec = $societa->pec ?? '';
        $this->email_notifiche = $societa->email_notifiche ?? [];
        $this->attivo = $societa->attivo;
        $this->note = $societa->note ?? '';
        
        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function aggiungiEmailNotifica()
    {
        if ($this->email_notifica_input && filter_var($this->email_notifica_input, FILTER_VALIDATE_EMAIL)) {
            if (!in_array($this->email_notifica_input, $this->email_notifiche)) {
                $this->email_notifiche[] = $this->email_notifica_input;
                $this->email_notifica_input = '';
            }
        }
    }

    public function rimuoviEmailNotifica($index)
    {
        unset($this->email_notifiche[$index]);
        $this->email_notifiche = array_values($this->email_notifiche);
    }

    public function salva()
    {
        // Aggiorna regole per edit
        if ($this->modalMode === 'edit') {
            $this->rules['codice'] = 'required|string|max:10|unique:societa,codice,' . $this->societaSelezionataId;
        }

        $this->validate();

        $data = [
            'codice' => strtoupper($this->codice),
            'ragione_sociale' => $this->ragione_sociale,
            'partita_iva' => $this->partita_iva ?: null,
            'codice_fiscale' => $this->codice_fiscale ?: null,
            'indirizzo' => $this->indirizzo ?: null,
            'citta' => $this->citta ?: null,
            'provincia' => strtoupper($this->provincia ?: ''),
            'cap' => $this->cap ?: null,
            'telefono' => $this->telefono ?: null,
            'email' => $this->email ?: null,
            'pec' => $this->pec ?: null,
            'email_notifiche' => !empty($this->email_notifiche) ? $this->email_notifiche : null,
            'attivo' => $this->attivo,
            'note' => $this->note ?: null,
        ];

        if ($this->modalMode === 'create') {
            Societa::create($data);
            session()->flash('message', '✅ Società creata con successo');
        } else {
            Societa::find($this->societaSelezionataId)->update($data);
            session()->flash('message', '✅ Società aggiornata con successo');
        }

        $this->chiudiModal();
        $this->resetForm();
    }

    public function apriModalEliminazione($societaId)
    {
        $this->societaSelezionataId = $societaId;
        $this->showDeleteModal = true;
    }

    public function elimina()
    {
        $societa = Societa::findOrFail($this->societaSelezionataId);
        
        // Verifica se ha sedi associate
        if ($societa->sedi()->count() > 0) {
            session()->flash('error', '❌ Impossibile eliminare: ci sono sedi associate a questa società');
            $this->chiudiModalEliminazione();
            return;
        }

        $societa->delete();
        session()->flash('message', '✅ Società eliminata con successo');
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
        $this->societaSelezionataId = null;
    }

    public function resetForm()
    {
        $this->societaSelezionataId = null;
        $this->codice = '';
        $this->ragione_sociale = '';
        $this->partita_iva = '';
        $this->codice_fiscale = '';
        $this->indirizzo = '';
        $this->citta = '';
        $this->provincia = '';
        $this->cap = '';
        $this->telefono = '';
        $this->email = '';
        $this->pec = '';
        $this->email_notifiche = [];
        $this->email_notifica_input = '';
        $this->attivo = true;
        $this->note = '';
        $this->modalMode = 'create';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.gestione-societa', [
            'societa' => $this->societa,
        ]);
    }
}
