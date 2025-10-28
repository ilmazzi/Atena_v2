<div class="container-xxl">
    
    {{-- Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('carico.scelta') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Indietro
                    </a>
                </div>
                <h4 class="page-title">
                    <i class="ri-file-upload-line me-2"></i> Carico Documenti
                </h4>
                <p class="text-muted small mb-0">Carica DDT o Fatture e gestisci automaticamente il magazzino</p>
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-fill">
                            <div class="mb-2">
                                <span class="avatar-sm d-inline-flex align-items-center justify-content-center rounded-circle {{ $step >= 1 ? 'bg-primary' : 'bg-light' }}">
                                    <iconify-icon icon="solar:upload-minimalistic-bold-duotone" class="fs-4 {{ $step >= 1 ? 'text-white' : 'text-muted' }}"></iconify-icon>
                                </span>
                            </div>
                            <p class="mb-0 small {{ $step >= 1 ? 'text-primary fw-semibold' : 'text-muted' }}">Carica PDF</p>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="mb-2">
                                <span class="avatar-sm d-inline-flex align-items-center justify-content-center rounded-circle {{ $step >= 2 ? 'bg-primary' : 'bg-light' }}">
                                    <iconify-icon icon="solar:clipboard-check-bold-duotone" class="fs-4 {{ $step >= 2 ? 'text-white' : 'text-muted' }}"></iconify-icon>
                                </span>
                            </div>
                            <p class="mb-0 small {{ $step >= 2 ? 'text-primary fw-semibold' : 'text-muted' }}">Valida Dati</p>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="mb-2">
                                <span class="avatar-sm d-inline-flex align-items-center justify-content-center rounded-circle {{ $step >= 3 ? 'bg-success' : 'bg-light' }}">
                                    <iconify-icon icon="solar:check-circle-bold-duotone" class="fs-4 {{ $step >= 3 ? 'text-white' : 'text-muted' }}"></iconify-icon>
                                </span>
                            </div>
                            <p class="mb-0 small {{ $step >= 3 ? 'text-success fw-semibold' : 'text-muted' }}">Completato</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STEP 1: UPLOAD PDF --}}
    @if($step === 1)
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    
                    <form wire:submit.prevent="processaPdf">
                    
                    {{-- Tipo Documento --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Tipo Documento *</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="card mb-0 cursor-pointer {{ $tipoDocumento === 'ddt' ? 'border-primary' : '' }}">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" wire:model="tipoDocumento" value="ddt" name="tipoDocumento" id="tipoDdt" required>
                                            <label class="form-check-label w-100" for="tipoDdt">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                                                        <iconify-icon icon="solar:document-text-bold-duotone" class="fs-4 text-info"></iconify-icon>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">DDT</div>
                                                        <small class="text-muted">Documento di Trasporto</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="card mb-0 cursor-pointer {{ $tipoDocumento === 'fattura' ? 'border-primary' : '' }}">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" wire:model="tipoDocumento" value="fattura" name="tipoDocumento" id="tipoFattura" required>
                                            <label class="form-check-label w-100" for="tipoFattura">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                                        <iconify-icon icon="solar:bill-list-bold-duotone" class="fs-4 text-success"></iconify-icon>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">Fattura</div>
                                                        <small class="text-muted">Fattura di Acquisto</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Upload PDF con Livewire - SEGUENDO DOC UFFICIALE --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">File PDF *</label>
                        
                        <div class="border-2 border-dashed rounded p-4 text-center {{ $photo ? 'border-success bg-success-subtle' : 'border-secondary' }}" style="min-height: 200px;">
                            @if($photo)
                                <iconify-icon icon="solar:file-check-bold-duotone" class="text-success d-block mb-3" style="font-size: 4rem;"></iconify-icon>
                                <h6 class="mb-1">{{ $photo->getClientOriginalName() }}</h6>
                                <p class="text-muted mb-3">{{ number_format($photo->getSize() / 1024, 2) }} KB</p>
                                <button type="button" wire:click="$set('photo', null)" class="btn btn-sm btn-danger">
                                    <iconify-icon icon="solar:trash-bin-trash-bold-duotone" class="me-1"></iconify-icon> Rimuovi
                                </button>
                            @else
                                <iconify-icon icon="solar:cloud-upload-bold-duotone" class="text-primary d-block mb-3" style="font-size: 4rem;"></iconify-icon>
                                <h5 class="mb-2">Seleziona un file PDF</h5>
                                <p class="text-muted mb-3">Max 10 MB</p>
                                <input type="file" wire:model="photo" accept=".pdf" class="form-control" required>
                            @endif
                        </div>
                        
                        @error('photo')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror

                        {{-- Loading durante upload --}}
                        <div wire:loading wire:target="photo" class="text-center mt-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <span class="text-primary">Caricamento file...</span>
                        </div>
                    </div>

                    {{-- Pulsante Processa --}}
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span wire:loading.remove wire:target="processaPdf">
                                <iconify-icon icon="solar:magic-stick-3-bold-duotone" class="me-2"></iconify-icon>
                                Elabora con OCR
                            </span>
                            <span wire:loading wire:target="processaPdf">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Elaborazione in corso...
                            </span>
                        </button>
                    </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- STEP 2: VALIDAZIONE --}}
    @if($step === 2)
    <form wire:submit.prevent="salvaCarico">
        
        {{-- Intestazione Documento --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    
                    @if($confidenceScore > 0)
                    <div class="col-auto">
                        <span class="badge {{ $confidenceScore >= 70 ? 'bg-success' : 'bg-warning' }} fs-6 px-3 py-2">
                            <i class="ri-bar-chart-line me-1"></i>
                            {{ number_format($confidenceScore, 0) }}%
                        </span>
                    </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Numero {{ strtoupper($tipoDocumento) }} *</label>
                        <input type="text" wire:model.defer="numeroDocumento" 
                               class="form-control form-control-sm @error('numeroDocumento') is-invalid @enderror">
                        @error('numeroDocumento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Data *</label>
                        <input type="date" wire:model.defer="dataDocumento" 
                               class="form-control form-control-sm @error('dataDocumento') is-invalid @enderror">
                        @error('dataDocumento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Fornitore</label>
                        <select wire:model.defer="fornitoreId" class="form-select form-select-sm">
                            <option value="">Nessuno</option>
                            @foreach($fornitori as $fornitore)
                                <option value="{{ $fornitore->id }}">{{ $fornitore->ragione_sociale }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Sede *</label>
                        <select wire:model.defer="sedeId" class="form-select form-select-sm @error('sedeId') is-invalid @enderror">
                            <option value="">Seleziona...</option>
                            @foreach($sedi as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                            @endforeach
                        </select>
                        @error('sedeId')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Categoria *</label>
                        <select wire:model.defer="categoriaId" class="form-select form-select-sm @error('categoriaId') is-invalid @enderror">
                            <option value="">Seleziona...</option>
                            @foreach($categorie as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                            @endforeach
                        </select>
                        @error('categoriaId')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabella Articoli --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:box-bold-duotone" class="text-primary me-2"></iconify-icon>
                        Articoli
                    </h5>
                    <small class="text-muted">{{ count($articoli) }} articol{{ count($articoli) != 1 ? 'i' : 'o' }}</small>
                </div>
                <button type="button" wire:click="aggiungiArticolo" class="btn btn-sm btn-primary">
                    <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon> Aggiungi
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th width="50">Stato</th>
                            <th width="180">Codice *</th>
                            <th>Descrizione</th>
                            <th width="100">Quantità *</th>
                            <th width="150">Seriale</th>
                            <th width="150">EAN</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articoli as $index => $articolo)
                        <tr wire:key="art-{{ $index }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                @if($articolo['esiste'] ?? false)
                                    <span class="badge bg-success-subtle text-success">Esiste</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Nuovo</span>
                                @endif
                            </td>
                            <td>
                                <input type="text" wire:model.defer="articoli.{{ $index }}.codice"
                                       class="form-control form-control-sm @error('articoli.'.$index.'.codice') is-invalid @enderror"
                                       placeholder="Codice">
                            </td>
                            <td>
                                <input type="text" wire:model.defer="articoli.{{ $index }}.descrizione"
                                       class="form-control form-control-sm" placeholder="Descrizione">
                            </td>
                            <td>
                                <input type="number" wire:model.defer="articoli.{{ $index }}.quantita"
                                       class="form-control form-control-sm text-center @error('articoli.'.$index.'.quantita') is-invalid @enderror"
                                       min="1">
                            </td>
                            <td>
                                <input type="text" wire:model.defer="articoli.{{ $index }}.numero_seriale"
                                       class="form-control form-control-sm" placeholder="Seriale">
                            </td>
                            <td>
                                <input type="text" wire:model.defer="articoli.{{ $index }}.ean"
                                       class="form-control form-control-sm" placeholder="EAN">
                            </td>
                            <td class="text-center">
                                <button type="button" wire:click="rimuoviArticolo({{ $index }})" 
                                        class="btn btn-sm btn-danger">
                                    <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <iconify-icon icon="solar:inbox-line-bold-duotone" class="fs-1 text-muted d-block mb-2" style="font-size: 3rem;"></iconify-icon>
                                <p class="text-muted">Nessun articolo trovato</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pulsanti --}}
        <div class="d-flex justify-content-between mt-4">
            <button type="button" wire:click="$set('step', 1)" class="btn btn-secondary">
                <iconify-icon icon="solar:arrow-left-bold-duotone" class="me-1"></iconify-icon> Indietro
            </button>
            
            <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="salvaCarico">
                <span wire:loading.remove wire:target="salvaCarico">
                    <iconify-icon icon="solar:diskette-bold-duotone" class="me-1"></iconify-icon> Salva Carico
                </span>
                <span wire:loading wire:target="salvaCarico">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Salvataggio...
                </span>
            </button>
        </div>

    </form>
    @endif

    {{-- STEP 3: COMPLETATO --}}
    @if($step === 3)
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-5">
                    <div class="avatar-lg bg-success-subtle mx-auto mb-4 d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:check-circle-bold-duotone" class="text-success" style="font-size: 4rem;"></iconify-icon>
                    </div>
                    <h3 class="mb-3">Carico Completato!</h3>
                    <p class="text-muted mb-4">
                        Il documento <strong>{{ $numeroDocumento }}</strong> è stato caricato con successo.<br>
                        Tutti gli articoli sono stati aggiunti al magazzino.
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" wire:click="nuovoCarico" class="btn btn-primary">
                            <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon> Nuovo Carico
                        </button>
                        <a href="{{ route('carico.storico') }}" class="btn btn-secondary">
                            <iconify-icon icon="solar:list-check-bold-duotone" class="me-1"></iconify-icon> Vedi Storico
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
