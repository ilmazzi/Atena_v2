<div>
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Gestione Società</h4>
                    <p class="text-muted mb-0">Gestisci le società del sistema</p>
                </div>
                <button class="btn btn-primary" wire:click="apriModalCreazione">
                    <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                    Nuova Società
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:check-circle-bold" class="me-2"></iconify-icon>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:close-circle-bold" class="me-2"></iconify-icon>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filtri e Ricerca --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon>
                </span>
                <input type="text" 
                       class="form-control" 
                       placeholder="Cerca per codice, ragione sociale, P.IVA..." 
                       wire:model.debounce.300ms="search">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model="filtroAttivo">
                <option value="">Tutti gli stati</option>
                <option value="si">Attive</option>
                <option value="no">Non attive</option>
            </select>
        </div>
        <div class="col-md-3 text-end">
            <span class="text-muted small">Totale: {{ $societa->total() }} società</span>
        </div>
    </div>

    {{-- Tabella Società --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">Codice</th>
                            <th>Ragione Sociale</th>
                            <th width="150">P.IVA</th>
                            <th width="120">Sedi</th>
                            <th width="100">Stato</th>
                            <th width="150" class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($societa as $soc)
                            <tr>
                                <td>
                                    <span class="badge bg-primary">{{ $soc->codice }}</span>
                                </td>
                                <td>
                                    <strong>{{ $soc->ragione_sociale }}</strong>
                                    @if($soc->citta)
                                        <br><small class="text-muted">{{ $soc->citta }}</small>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $soc->partita_iva ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light-primary text-primary">
                                        {{ $soc->sedi->count() }} sede
                                    </span>
                                </td>
                                <td>
                                    @if($soc->attivo)
                                        <span class="badge bg-success">Attiva</span>
                                    @else
                                        <span class="badge bg-secondary">Non attiva</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-light" 
                                                wire:click="apriModalModifica({{ $soc->id }})"
                                                title="Modifica">
                                            <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                        </button>
                                        <button class="btn btn-light text-danger" 
                                                wire:click="apriModalEliminazione({{ $soc->id }})"
                                                title="Elimina">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <iconify-icon icon="solar:document-text-bold" class="fs-1 text-muted"></iconify-icon>
                                    <p class="text-muted mt-2 mb-0">Nessuna società trovata</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($societa->hasPages())
            <div class="card-footer">
                {{ $societa->links() }}
            </div>
        @endif
    </div>

    {{-- Modale Creazione/Modifica --}}
    @if($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:buildings-2-bold-duotone" class="me-2"></iconify-icon>
                            {{ $modalMode === 'create' ? 'Nuova Società' : 'Modifica Società' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="chiudiModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                                {{-- Codice --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Codice *</label>
                                    <input type="text" 
                                           class="form-control @error('codice') is-invalid @enderror" 
                                           wire:model="codice"
                                           placeholder="DP, LDP...">
                                    @error('codice') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Ragione Sociale --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ragione Sociale *</label>
                                    <input type="text" 
                                           class="form-control @error('ragione_sociale') is-invalid @enderror" 
                                           wire:model="ragione_sociale">
                                    @error('ragione_sociale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Partita IVA --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Partita IVA</label>
                                    <input type="text" 
                                           class="form-control @error('partita_iva') is-invalid @enderror" 
                                           wire:model="partita_iva">
                                    @error('partita_iva') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Codice Fiscale --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Codice Fiscale</label>
                                    <input type="text" 
                                           class="form-control @error('codice_fiscale') is-invalid @enderror" 
                                           wire:model="codice_fiscale">
                                    @error('codice_fiscale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Indirizzo --}}
                                <div class="col-12 mb-3">
                                    <label class="form-label">Indirizzo</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="indirizzo">
                                </div>

                                {{-- Città, Provincia, CAP --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Città</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="citta">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Provincia</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="provincia"
                                           maxlength="2"
                                           placeholder="RM">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">CAP</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="cap">
                                </div>

                                {{-- Contatti --}}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Telefono</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="telefono">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           wire:model="email">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">PEC</label>
                                    <input type="email" 
                                           class="form-control @error('pec') is-invalid @enderror" 
                                           wire:model="pec">
                                    @error('pec') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Email Notifiche --}}
                                <div class="col-12 mb-3">
                                    <label class="form-label">Email per Notifiche Conti Deposito</label>
                                    <div class="input-group">
                                        <input type="email" 
                                               class="form-control" 
                                               wire:model="email_notifica_input"
                                               placeholder="aggiungi@email.com"
                                               wire:keydown.enter.prevent="aggiungiEmailNotifica">
                                        <button class="btn btn-primary" type="button" wire:click="aggiungiEmailNotifica">
                                            <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                                        </button>
                                    </div>
                                    @if(!empty($email_notifiche))
                                        <div class="mt-2">
                                            @foreach($email_notifiche as $index => $email)
                                                <span class="badge bg-light-primary text-primary me-1 mb-1">
                                                    {{ $email }}
                                                    <button type="button" 
                                                            class="btn-close btn-close-sm ms-1"
                                                            wire:click="rimuoviEmailNotifica({{ $index }})"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Note --}}
                                <div class="col-12 mb-3">
                                    <label class="form-label">Note</label>
                                    <textarea class="form-control" 
                                              rows="3" 
                                              wire:model="note"></textarea>
                                </div>

                                {{-- Attivo --}}
                                <div class="col-12 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="attivo"
                                               wire:model="attivo">
                                        <label class="form-check-label" for="attivo">
                                            Società attiva
                                        </label>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiModal">
                            <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                            Annulla
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="salva">
                            <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>
                            {{ $modalMode === 'create' ? 'Crea' : 'Salva' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Modale Eliminazione --}}
    @if($showDeleteModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:danger-triangle-bold-duotone" class="me-2 text-danger"></iconify-icon>
                            Conferma Eliminazione
                        </h5>
                        <button type="button" class="btn-close" wire:click="chiudiModalEliminazione"></button>
                    </div>
                    <div class="modal-body">
                        <p>Sei sicuro di voler eliminare questa società?</p>
                        <p class="text-muted small">Questa operazione non può essere annullata.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiModalEliminazione">
                            <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                            Annulla
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="elimina">
                            <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="me-1"></iconify-icon>
                            Elimina
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        @endif
    </div>
</div>
