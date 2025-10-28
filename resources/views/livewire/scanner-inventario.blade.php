<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:scanner-bold" class="me-2"></iconify-icon>
            Scanner Inventario
        </h5>
        <div class="btn-group" role="group">
            <button wire:click="$set('operazione', 'scarico')" 
                    class="btn {{ $operazione === 'scarico' ? 'btn-primary' : 'btn-secondary' }}">
                <iconify-icon icon="solar:box-remove-bold"></iconify-icon>
                Scarico
            </button>
            <button wire:click="$set('operazione', 'inventario')" 
                    class="btn {{ $operazione === 'inventario' ? 'btn-primary' : 'btn-secondary' }}">
                <iconify-icon icon="solar:clipboard-list-bold"></iconify-icon>
                Inventario
            </button>
            <button wire:click="$set('operazione', 'stampa')" 
                    class="btn {{ $operazione === 'stampa' ? 'btn-primary' : 'btn-secondary' }}">
                <iconify-icon icon="solar:printer-bold"></iconify-icon>
                Stampa
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Input Scanner -->
        <div class="mb-4">
            <label class="form-label fw-bold">Scansiona Codice Articolo</label>
            <input type="text" 
                   wire:model.live="codiceScansionato" 
                   class="form-control form-control-lg text-center"
                   placeholder="Scansiona o inserisci codice..."
                   autofocus>
            <div class="form-text">Inserisci almeno 3 caratteri per la ricerca</div>
        </div>

        <!-- Messaggi -->
        @if($messaggio)
            <div class="alert {{ str_contains($messaggio, 'Errore') ? 'alert-danger' : 'alert-success' }} alert-dismissible fade show">
                {{ $messaggio }}
                <button type="button" class="btn-close" wire:click="resetForm"></button>
            </div>
        @endif
        
        <!-- Articolo Trovato -->
        @if($articoloTrovato)
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                        Articolo Trovato
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">{{ $articoloTrovato->codice }}</h5>
                            <p class="mb-1">{{ $articoloTrovato->descrizione }}</p>
                            <small class="text-muted">{{ $articoloTrovato->marca }}</small>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Giacenza:</strong><br>
                                    <span class="badge bg-info">{{ $articoloTrovato->giacenze->sum('quantita_residua') }} pz</span>
                                </div>
                                <div class="col-6">
                                    <strong>Sede:</strong><br>
                                    <span class="badge bg-secondary">{{ $articoloTrovato->sede->nome ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantità -->
                    @if($articoloTrovato->giacenze->sum('quantita_residua') > 1 && $operazione !== 'stampa')
                        <div class="mt-3">
                            <label class="form-label fw-bold">Quantità</label>
                            <div class="input-group">
                                <input type="number" 
                                       wire:model="quantita" 
                                       class="form-control"
                                       min="1" 
                                       max="{{ $articoloTrovato->giacenze->sum('quantita_residua') }}">
                                <span class="input-group-text">di {{ $articoloTrovato->giacenze->sum('quantita_residua') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Selezione Stampante (solo per stampa) -->
                    @if($operazione === 'stampa' && $stampantiDisponibili->count() > 0)
                        <div class="mt-3">
                            <label class="form-label fw-bold">Stampante</label>
                            <select wire:model.live="stampanteSelezionata" class="form-select" onchange="console.log('Stampante selezionata:', this.value)">
                                <option value="">Seleziona stampante...</option>
                                @foreach($stampantiDisponibili as $stampante)
                                    <option value="{{ $stampante->id }}">{{ $stampante->nome }} ({{ $stampante->modello }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    
                    
                    <!-- Azione -->
                    <div class="mt-4">
                        <button wire:click="eseguiOperazione" 
                                class="btn btn-primary btn-lg w-100"
                                @if($operazione === 'stampa' && empty($stampanteSelezionata)) disabled @endif>
                            @if($operazione === 'scarico')
                                <iconify-icon icon="solar:box-remove-bold"></iconify-icon>
                                Scarica {{ $quantita }} pezzi
                            @elseif($operazione === 'inventario')
                                <iconify-icon icon="solar:clipboard-list-bold"></iconify-icon>
                                Aggiorna Inventario
                            @else
                                <iconify-icon icon="solar:printer-bold"></iconify-icon>
                                Stampa Etichetta
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Istruzioni -->
        <div class="mt-4">
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <iconify-icon icon="solar:info-circle-bold"></iconify-icon>
                    Istruzioni
                </h6>
                <ul class="mb-0">
                    <li><strong>Scarico:</strong> Riduce la giacenza dell'articolo</li>
                    <li><strong>Inventario:</strong> Aggiorna i dati dell'articolo</li>
                    <li><strong>Stampa:</strong> Genera e stampa l'etichetta</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Conferma Stampa -->
@if($showModal)
<div class="modal show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon>
                    Conferma Stampa
                </h5>
                <button wire:click="$set('showModal', false)" class="btn-close"></button>
            </div>
            <div class="modal-body">
                <p>Stai per stampare l'etichetta per:</p>
                <div class="alert alert-light">
                    <strong>{{ $articoloTrovato->codice ?? '' }}</strong><br>
                    {{ $articoloTrovato->descrizione ?? '' }}
                </div>
                <p class="mb-0">Confermi la stampa?</p>
            </div>
            <div class="modal-footer">
                <button wire:click="$set('showModal', false)" class="btn btn-secondary">Annulla</button>
                <button wire:click="confermaStampa" class="btn btn-primary">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon>
                    Stampa
                </button>
            </div>
        </div>
    </div>
</div>
@endif