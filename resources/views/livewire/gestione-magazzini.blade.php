<div>
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Gestione Magazzini</h4>
                    <p class="text-muted mb-0">Gestisci le categorie merceologiche (magazzini)</p>
                </div>
                <button class="btn btn-primary" wire:click="apriModalCreazione">
                    <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                    Nuovo Magazzino
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
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text">
                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon>
                </span>
                <input type="text" 
                       class="form-control" 
                       placeholder="Cerca per codice o nome..." 
                       wire:model.debounce.300ms="search">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="filtroAttivo">
                <option value="">Tutti gli stati</option>
                <option value="si">Attivi</option>
                <option value="no">Non attivi</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="filtroSede">
                <option value="">Tutte le sedi</option>
                @foreach($sediList as $sede)
                    <option value="{{ $sede->id }}">{{ $sede->codice }} - {{ $sede->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 text-end">
            <span class="text-muted small">{{ $magazzini->total() }}</span>
        </div>
    </div>

    {{-- Tabella Magazzini --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="120">Codice</th>
                            <th>Nome</th>
                            <th width="200">Sede</th>
                            <th width="100">Stato</th>
                            <th width="150" class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($magazzini as $mag)
                            <tr>
                                <td>
                                    <span class="badge bg-primary">{{ $mag->codice }}</span>
                                </td>
                                <td>
                                    <strong>{{ $mag->nome }}</strong>
                                    @if($mag->note)
                                        <br><small class="text-muted">{{ Str::limit($mag->note, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($mag->sede)
                                        <span class="badge bg-light-primary text-primary">
                                            {{ $mag->sede->codice }}
                                        </span>
                                        <br><small class="text-muted">{{ $mag->sede->nome }}</small>
                                        @if($mag->sede->citta)
                                            <br><small class="text-muted">{{ $mag->sede->citta }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($mag->attivo)
                                        <span class="badge bg-success">Attivo</span>
                                    @else
                                        <span class="badge bg-secondary">Non attivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-light" 
                                                wire:click="apriModalModifica({{ $mag->id }})"
                                                title="Modifica">
                                            <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                        </button>
                                        <button class="btn btn-light text-danger" 
                                                wire:click="apriModalEliminazione({{ $mag->id }})"
                                                title="Elimina">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <iconify-icon icon="solar:document-text-bold" class="fs-1 text-muted"></iconify-icon>
                                    <p class="text-muted mt-2 mb-0">Nessun magazzino trovato</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($magazzini->hasPages())
            <div class="card-footer">
                {{ $magazzini->links() }}
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
                            <iconify-icon icon="solar:box-bold-duotone" class="me-2"></iconify-icon>
                            {{ $modalMode === 'create' ? 'Nuovo Magazzino' : 'Modifica Magazzino' }}
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
                                       placeholder="MAG1, CD-DP...">
                                @error('codice') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Nome --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" 
                                       class="form-control @error('nome') is-invalid @enderror" 
                                       wire:model="nome"
                                       placeholder="Nome categoria merceologica">
                                @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Sede --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sede *</label>
                                <select class="form-select @error('sede_id') is-invalid @enderror" wire:model="sede_id">
                                    <option value="">Seleziona sede...</option>
                                    @foreach($sediList as $sede)
                                        <option value="{{ $sede->id }}">{{ $sede->codice }} - {{ $sede->nome }}</option>
                                    @endforeach
                                </select>
                                @error('sede_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                        Magazzino attivo
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
                        <p>Sei sicuro di voler eliminare questo magazzino?</p>
                        <p class="text-muted small">Verifica che non ci siano articoli o giacenze associate prima di procedere.</p>
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
