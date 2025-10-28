<div>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $titoloPagina }}</h1>
                    <p class="text-muted mb-0">
                        @if($isModifica)
                            Modifica le informazioni del prodotto finito
                        @else
                            Crea un nuovo prodotto finito assemblando componenti
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('prodotti-finiti.index') }}" class="btn btn-secondary">
                        <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                        Torna all'elenco
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-fill text-center {{ $step >= 1 ? 'text-primary' : 'text-muted' }}">
                    <div class="mb-2">
                        <iconify-icon icon="solar:{{ $step > 1 ? 'check-circle-bold' : 'document-text-bold' }}-duotone" class="fs-1"></iconify-icon>
                    </div>
                    <h6 class="mb-0">1. Informazioni Base</h6>
                </div>
                <div class="flex-shrink-0 px-3">
                    <iconify-icon icon="solar:alt-arrow-right-bold" class="fs-3 {{ $step >= 2 ? 'text-primary' : 'text-muted' }}"></iconify-icon>
                </div>
                <div class="flex-fill text-center {{ $step >= 2 ? 'text-primary' : 'text-muted' }}">
                    <div class="mb-2">
                        <iconify-icon icon="solar:{{ $step > 2 ? 'check-circle-bold' : 'add-square-bold' }}-duotone" class="fs-1"></iconify-icon>
                    </div>
                    <h6 class="mb-0">2. Selezione Componenti</h6>
                </div>
                <div class="flex-shrink-0 px-3">
                    <iconify-icon icon="solar:alt-arrow-right-bold" class="fs-3 {{ $step >= 3 ? 'text-primary' : 'text-muted' }}"></iconify-icon>
                </div>
                <div class="flex-fill text-center {{ $step >= 3 ? 'text-primary' : 'text-muted' }}">
                    <div class="mb-2">
                        <iconify-icon icon="solar:{{ $step > 3 ? 'check-circle-bold' : 'eye-bold' }}-duotone" class="fs-1"></iconify-icon>
                    </div>
                    <h6 class="mb-0">3. Riepilogo</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Informazioni Base -->
    @if($step === 1)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <iconify-icon icon="solar:document-text-bold-duotone" class="text-primary me-2"></iconify-icon>
                    Informazioni Base Prodotto
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-12">
                        <label class="form-label">Descrizione Prodotto *</label>
                        <input type="text" 
                               class="form-control @error('descrizione') is-invalid @enderror" 
                               wire:model="descrizione"
                               placeholder="Es: Collana oro bianco con brillanti">
                        @error('descrizione') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Tipologia *</label>
                        <select class="form-select @error('tipologia') is-invalid @enderror" wire:model="tipologia">
                            <option value="prodotto_finito">Prodotto Finito</option>
                            <option value="semilavorato">Semilavorato</option>
                            <option value="componente">Componente</option>
                        </select>
                        @error('tipologia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Categoria Finale *</label>
                        <select class="form-select @error('categoriaId') is-invalid @enderror" wire:model="categoriaId">
                            @foreach($categorie as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                        @error('categoriaId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Sede Assemblaggio *</label>
                        <select class="form-select @error('sedeId') is-invalid @enderror" wire:model="sedeId">
                            @foreach($sedi as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                            @endforeach
                        </select>
                        @error('sedeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Costo Lavorazione (€)</label>
                        <input type="number" 
                               step="0.01" 
                               class="form-control" 
                               wire:model.blur="costoLavorazione">
                    </div>

                    <div class="col-lg-12">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" wire:model="note" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" wire:click="avanti">
                        Avanti
                        <iconify-icon icon="solar:alt-arrow-right-bold" class="ms-1"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Step 2: Selezione Componenti -->
    @if($step === 2)
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <iconify-icon icon="solar:bag-4-bold-duotone" class="text-primary me-2"></iconify-icon>
                            Articoli Disponibili
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Ricerca articoli -->
                        <div class="row g-2 mb-3">
                            <div class="col-lg-5">
                                <input type="text" 
                                       class="form-control" 
                                       wire:model.live.debounce.300ms="searchArticoli"
                                       placeholder="Cerca articolo...">
                            </div>
                            <div class="col-lg-4">
                                <select class="form-select" wire:model.live="categoriaComponentiFilter">
                                    <option value="">Tutte categorie</option>
                                    @foreach($categorie as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-check form-switch h-100 d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" wire:model.live="soloDisponibili" id="soloDisponibiliCrea">
                                    <label class="form-check-label ms-2" for="soloDisponibiliCrea">Solo disponibili</label>
                                </div>
                            </div>
                        </div>

                        <!-- Lista articoli -->
                        <div style="max-height: 500px; overflow-y: auto;">
                            @forelse($articoliDisponibili as $articolo)
                                @php
                                    $giacenza = $articolo->giacenza->quantita_residua ?? 0;
                                    $disponibile = $giacenza > 0;
                                @endphp
                                <div class="border rounded p-2 mb-2 {{ $disponibile ? 'hover-shadow' : 'bg-light' }}" 
                                     style="cursor: {{ $disponibile ? 'pointer' : 'not-allowed' }}; {{ !$disponibile ? 'opacity: 0.6;' : '' }}" 
                                     @if($disponibile) wire:click="aggiungiComponente({{ $articolo->id }})" @endif>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <strong class="{{ $disponibile ? 'text-primary' : 'text-muted' }}">{{ $articolo->codice }}</strong>
                                            <p class="mb-0 small">{{ $articolo->descrizione }}</p>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <small class="text-muted">{{ $articolo->categoria->nome ?? '' }}</small>
                                                <span class="badge {{ $giacenza > 0 ? 'bg-success' : 'bg-danger' }}">
                                                    <iconify-icon icon="solar:box-bold" class="me-1"></iconify-icon>
                                                    Giacenza: {{ $giacenza }}
                                                </span>
                                                @if($articolo->prezzo_acquisto)
                                                    <small class="text-muted">€ {{ number_format($articolo->prezzo_acquisto, 2, ',', '.') }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        @if($disponibile)
                                            <iconify-icon icon="solar:add-circle-bold" class="fs-3 text-success"></iconify-icon>
                                        @else
                                            <iconify-icon icon="solar:close-circle-bold" class="fs-3 text-danger"></iconify-icon>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <iconify-icon icon="solar:box-minimalistic-bold" class="fs-1 mb-2"></iconify-icon>
                                    <p>Nessun articolo disponibile</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <iconify-icon icon="solar:cart-large-bold" class="me-2"></iconify-icon>
                            Componenti Selezionati
                        </h5>
                    </div>
                    <div class="card-body">
                        @forelse($componenti as $comp)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong class="small">{{ $comp['articolo']->codice }}</strong>
                                        <p class="mb-1 small text-muted">{{ $comp['articolo']->descrizione }}</p>
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="small mb-0">Qta:</label>
                                            <input type="number" 
                                                   class="form-control form-control-sm" 
                                                   style="width: 60px;"
                                                   min="1"
                                                   wire:model.blur="componenti.{{ $comp['articolo_id'] }}.quantita"
                                                   wire:change="aggiornaQuantita({{ $comp['articolo_id'] }}, $event.target.value)">
                                        </div>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            wire:click="rimuoviComponente({{ $comp['articolo_id'] }})">
                                        <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <iconify-icon icon="solar:cart-large-minimalistic-bold" class="fs-1 mb-2"></iconify-icon>
                                <p class="small">Nessun componente aggiunto</p>
                            </div>
                        @endforelse

                        @if(count($componenti) > 0)
                            <div class="alert alert-info mt-3">
                                <strong>Totale componenti:</strong> {{ count($componenti) }}
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary flex-fill" wire:click="indietro">
                                <iconify-icon icon="solar:alt-arrow-left-bold"></iconify-icon>
                                Indietro
                            </button>
                            <button type="button" class="btn btn-primary flex-fill" wire:click="avanti">
                                Avanti
                                <iconify-icon icon="solar:alt-arrow-right-bold"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Step 3: Riepilogo -->
    @if($step === 3)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <iconify-icon icon="solar:eye-bold-duotone" class="text-primary me-2"></iconify-icon>
                    Riepilogo Prodotto Finito
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="text-muted mb-3">Informazioni Prodotto</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150"><strong>Descrizione:</strong></td>
                                <td>{{ $descrizione }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tipologia:</strong></td>
                                <td><span class="badge bg-primary">{{ ucfirst($tipologia) }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Categoria:</strong></td>
                                <td>{{ $categorie->find($categoriaId)->nome ?? '' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sede:</strong></td>
                                <td>{{ $sedi->find($sedeId)->nome ?? '' }}</td>
                            </tr>
                            @if($note)
                            <tr>
                                <td><strong>Note:</strong></td>
                                <td>{{ $note }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    <div class="col-lg-6">
                        <h6 class="text-muted mb-3">Dati Gioielleria Calcolati</h6>
                        <table class="table table-sm table-borderless">
                            @if($oroTotale)
                            <tr>
                                <td width="150"><strong>Oro Totale:</strong></td>
                                <td><span class="badge bg-warning">{{ $oroTotale }}</span></td>
                            </tr>
                            @endif
                            @if($brillantiTotali)
                            <tr>
                                <td><strong>Brillanti Totali:</strong></td>
                                <td><span class="badge bg-info">{{ $brillantiTotali }}</span></td>
                            </tr>
                            @endif
                            @if($pietreTotali)
                            <tr>
                                <td><strong>Pietre Totali:</strong></td>
                                <td><span class="badge bg-success">{{ $pietreTotali }}</span></td>
                            </tr>
                            @endif
                        </table>
                    </div>

                    <div class="col-12">
                        <h6 class="text-muted mb-3">Componenti Utilizzati ({{ count($componenti) }})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th width="80">Qta</th>
                                        <th width="120">Costo Unit.</th>
                                        <th width="120">Costo Tot.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($componenti as $comp)
                                        <tr>
                                            <td><strong>{{ $comp['articolo']->codice }}</strong></td>
                                            <td>{{ $comp['articolo']->descrizione }}</td>
                                            <td class="text-center">{{ $comp['quantita'] }}</td>
                                            <td class="text-end">€ {{ number_format($comp['articolo']->prezzo_acquisto ?? 0, 2, ',', '.') }}</td>
                                            <td class="text-end">€ {{ number_format(($comp['articolo']->prezzo_acquisto ?? 0) * $comp['quantita'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Totale Materiali:</th>
                                        <th class="text-end">€ {{ number_format($costoMaterialiTotale, 2, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Costo Lavorazione:</th>
                                        <th class="text-end">€ {{ number_format($costoLavorazione, 2, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">TOTALE GENERALE:</th>
                                        <th class="text-end text-primary"><strong>€ {{ number_format($costoTotale, 2, ',', '.') }}</strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" wire:click="indietro">
                        <iconify-icon icon="solar:alt-arrow-left-bold"></iconify-icon>
                        Indietro
                    </button>
                    <button type="button" class="btn btn-success" wire:click="salva" wire:loading.attr="disabled" wire:loading.class="disabled">
                        <iconify-icon icon="solar:diskette-bold-duotone" class="me-1" wire:loading.remove wire:target="salva"></iconify-icon>
                        <span class="spinner-border spinner-border-sm me-1" wire:loading wire:target="salva"></span>
                        <span wire:loading.remove wire:target="salva">Conferma e Assembla</span>
                        <span wire:loading wire:target="salva">Assemblaggio in corso...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Listener per toast notifications
    window.addEventListener('show-toast', event => {
        const data = event.detail[0] || event.detail;
        const type = data.type || 'info';
        const message = data.message || 'Operazione completata';
        
        let icon = 'info';
        let title = 'Informazione';
        
        if (type === 'success') {
            icon = 'success';
            title = 'Successo';
        } else if (type === 'error') {
            icon = 'error';
            title = 'Errore';
        } else if (type === 'warning') {
            icon = 'warning';
            title = 'Attenzione';
        }
        
        Swal.fire({
            icon: icon,
            title: title,
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    });
</script>
@endpush
