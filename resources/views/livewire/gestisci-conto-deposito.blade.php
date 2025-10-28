<div>
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Gestisci Deposito {{ $deposito->codice }}</h4>
                    <p class="text-muted mb-0">
                        {{ $deposito->sedeMittente->nome }} → {{ $deposito->sedeDestinataria->nome }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('conti-deposito.index') }}" class="btn btn-light">
                        <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                        Torna alla Lista
                    </a>
                    
                    @if($deposito->stato === 'attivo')
                        <button class="btn btn-primary" wire:click="apriAggiungiArticoliModal">
                            <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                            Aggiungi Articoli
                        </button>
                        
                        @if($deposito->articoli_inviati > 0 && !$deposito->ddt_invio_id)
                            <button class="btn btn-success" wire:click="generaDdtInvio">
                                <iconify-icon icon="solar:document-add-bold" class="me-1"></iconify-icon>
                                Genera DDT Invio
                            </button>
                        @endif
                        
                        @if($deposito->ddt_invio_id)
                            <a href="{{ route('ddt.stampa', $deposito->ddt_invio_id) }}" class="btn btn-info" target="_blank">
                                <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                                Stampa DDT Invio
                            </a>
                        @endif
                    @endif
                    
                    @if($deposito->stato === 'attivo' && $deposito->isScaduto())
                        <button class="btn btn-warning" wire:click="generaDdtReso">
                            <iconify-icon icon="solar:import-bold" class="me-1"></iconify-icon>
                            Genera DDT Reso
                        </button>
                    @endif
                    
                    @if($deposito->ddt_reso_id)
                        <a href="{{ route('ddt.stampa', $deposito->ddt_reso_id) }}" class="btn btn-info" target="_blank">
                            <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                            Stampa DDT Reso
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Informazioni Deposito --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informazioni Deposito</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Codice:</strong> {{ $deposito->codice }}</p>
                            <p><strong>Data Invio:</strong> {{ $deposito->data_invio->format('d/m/Y') }}</p>
                            <p><strong>Data Scadenza:</strong> 
                                <span class="badge bg-light-{{ $deposito->isInScadenza(30) ? 'warning' : 'success' }} text-{{ $deposito->isInScadenza(30) ? 'warning' : 'success' }}">
                                    {{ $deposito->data_scadenza->format('d/m/Y') }}
                                </span>
                                ({{ $deposito->getGiorniRimanenti() }} giorni)
                            </p>
                            <p><strong>Stato:</strong> 
                                <span class="badge bg-light-{{ $deposito->stato_color }} text-{{ $deposito->stato_color }}">
                                    {{ $deposito->stato_label }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Creato da:</strong> {{ $deposito->creatoDa->name ?? 'N/A' }}</p>
                            <p><strong>Creato il:</strong> {{ $deposito->created_at->format('d/m/Y H:i') }}</p>
                            
                            @if($deposito->ddt_invio_id)
                                <p><strong>DDT Invio:</strong> 
                                    <a href="{{ route('ddt.stampa', $deposito->ddt_invio_id) }}" class="text-primary" target="_blank">
                                        {{ $deposito->ddtInvio->numero ?? 'N/A' }}
                                    </a>
                                </p>
                            @endif
                            
                            @if($deposito->ddt_reso_id)
                                <p><strong>DDT Reso:</strong> 
                                    <a href="{{ route('ddt.stampa', $deposito->ddt_reso_id) }}" class="text-primary" target="_blank">
                                        {{ $deposito->ddtReso->numero ?? 'N/A' }}
                                    </a>
                                </p>
                            @endif
                            
                            @if($deposito->note)
                                <p><strong>Note:</strong> {{ $deposito->note }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Statistiche</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Articoli Inviati:</span>
                        <strong>{{ $deposito->articoli_inviati }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Articoli Venduti:</span>
                        <strong class="text-success">{{ $deposito->articoli_venduti }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Articoli Rimanenti:</span>
                        <strong class="text-info">{{ $deposito->getArticoliRimanenti() }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Valore Inviato:</span>
                        <strong>€{{ number_format($deposito->valore_totale_invio, 2, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Valore Venduto:</span>
                        <strong class="text-success">€{{ number_format($deposito->valore_venduto, 2, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Valore Rimanente:</span>
                        <strong class="text-info">{{ $deposito->valore_rimanente_formatted }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Articoli in Deposito --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Articoli in Deposito</h5>
                </div>
                <div class="card-body">
                    @if($articoliInDeposito->count() > 0 || $prodottiFinitiInDeposito->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th>Quantità</th>
                                        <th>Costo Unit.</th>
                                        <th>Costo Tot.</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Articoli --}}
                                    @foreach($articoliInDeposito as $articoloData)
                                        <tr>
                                            <td>
                                                <span class="badge bg-light-primary text-primary">Articolo</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-primary">{{ $articoloData['articolo']->codice }}</span>
                                            </td>
                                            <td>{{ Str::limit($articoloData['articolo']->descrizione, 40) }}</td>
                                            <td>{{ $articoloData['quantita'] }}</td>
                                            <td>€{{ number_format($articoloData['costo_unitario'], 2, ',', '.') }}</td>
                                            <td>€{{ number_format($articoloData['costo_unitario'] * $articoloData['quantita'], 2, ',', '.') }}</td>
                                            <td class="text-center">
                                                @if($deposito->stato === 'attivo')
                                                    <button class="btn btn-success btn-sm" 
                                                            wire:click="apriRegistraVenditaModal('articolo', {{ $articoloData['articolo']->id }})"
                                                            title="Registra vendita">
                                                        <iconify-icon icon="solar:cart-check-bold"></iconify-icon>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Prodotti Finiti --}}
                                    @foreach($prodottiFinitiInDeposito as $pfData)
                                        <tr>
                                            <td>
                                                <span class="badge bg-light-warning text-warning">PF</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-primary">{{ $pfData['prodotto_finito']->codice }}</span>
                                            </td>
                                            <td>{{ Str::limit($pfData['prodotto_finito']->descrizione, 40) }}</td>
                                            <td>1</td>
                                            <td>€{{ number_format($pfData['costo_unitario'], 2, ',', '.') }}</td>
                                            <td>€{{ number_format($pfData['costo_unitario'], 2, ',', '.') }}</td>
                                            <td class="text-center">
                                                @if($deposito->stato === 'attivo')
                                                    <button class="btn btn-success btn-sm" 
                                                            wire:click="apriRegistraVenditaModal('prodotto_finito', {{ $pfData['prodotto_finito']->id }})"
                                                            title="Registra vendita">
                                                        <iconify-icon icon="solar:cart-check-bold"></iconify-icon>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <iconify-icon icon="solar:box-bold" class="fs-1 text-muted mb-2"></iconify-icon>
                            <p class="text-muted mb-0">Nessun articolo nel deposito</p>
                            @if($deposito->stato === 'attivo')
                                <button class="btn btn-primary mt-2" wire:click="apriAggiungiArticoliModal">
                                    Aggiungi Articoli
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Aggiungi Articoli --}}
    @if($showAggiungiArticoliModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:add-circle-bold-duotone" class="me-2"></iconify-icon>
                            Aggiungi Articoli al Deposito
                        </h5>
                        <button type="button" wire:click="chiudiAggiungiArticoliModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Filtri --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" wire:model.live="search" 
                                       placeholder="Cerca per codice o descrizione...">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" wire:model.live="tipoItem">
                                    <option value="articoli">Articoli</option>
                                    <option value="prodotti_finiti">Prodotti Finiti</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="text-end">
                                    <span class="badge bg-primary fs-6">{{ $this->getTotaleSelezionati() }} selezionati</span>
                                </div>
                            </div>
                        </div>

                        {{-- Lista Articoli --}}
                        @if($tipoItem === 'articoli')
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="50">Sel.</th>
                                            <th>Codice</th>
                                            <th>Descrizione</th>
                                            <th>Categoria</th>
                                            <th>Disp.</th>
                                            <th>Qtà</th>
                                            <th>Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($articoliDisponibili as $articolo)
                                            <tr class="{{ $this->isArticoloSelezionato($articolo->id) ? 'table-primary' : '' }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input" 
                                                           wire:click="toggleArticolo({{ $articolo->id }})"
                                                           {{ $this->isArticoloSelezionato($articolo->id) ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ $articolo->codice }}</span>
                                                </td>
                                                <td>{{ Str::limit($articolo->descrizione, 30) }}</td>
                                                <td>
                                                    <span class="badge bg-light-info text-info">
                                                        {{ $articolo->categoriaMerceologica->nome ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ $articolo->getQuantitaDisponibile() }}</td>
                                                <td>
                                                    @if($this->isArticoloSelezionato($articolo->id))
                                                        <input type="number" class="form-control form-control-sm" 
                                                               style="width: 80px;"
                                                               wire:model="articoliSelezionati.{{ $articolo->id }}.quantita"
                                                               min="1" 
                                                               max="{{ $articolo->getQuantitaDisponibile() }}">
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="small">€{{ number_format($articolo->prezzo_acquisto ?? 0, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-3">
                                                    <p class="text-muted mb-0">Nessun articolo disponibile</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Lista Prodotti Finiti --}}
                        @if($tipoItem === 'prodotti_finiti')
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="50">Sel.</th>
                                            <th>Codice</th>
                                            <th>Descrizione</th>
                                            <th>Categoria</th>
                                            <th>Stato</th>
                                            <th>Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($prodottiFinitiDisponibili as $pf)
                                            <tr class="{{ $this->isProdottoFinitoSelezionato($pf->id) ? 'table-primary' : '' }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input" 
                                                           wire:click="toggleProdottoFinito({{ $pf->id }})"
                                                           {{ $this->isProdottoFinitoSelezionato($pf->id) ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ $pf->codice }}</span>
                                                </td>
                                                <td>{{ Str::limit($pf->descrizione, 30) }}</td>
                                                <td>
                                                    <span class="badge bg-light-info text-info">
                                                        {{ $pf->categoriaMerceologica->nome ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-success text-success">
                                                        {{ ucfirst($pf->stato) }}
                                                    </span>
                                                </td>
                                                <td class="small">€{{ number_format($pf->costo_totale ?? 0, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-3">
                                                    <p class="text-muted mb-0">Nessun prodotto finito disponibile</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiAggiungiArticoliModal">
                            Annulla
                        </button>
                        <button type="button" class="btn btn-primary" 
                                wire:click="aggiungiArticoliAlDeposito"
                                {{ $this->getTotaleSelezionati() === 0 ? 'disabled' : '' }}>
                            <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>
                            Aggiungi {{ $this->getTotaleSelezionati() }} Selezionati
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Modal Registra Vendita --}}
    @if($showRegistraVenditaModal && $itemVendita)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:cart-check-bold-duotone" class="me-2"></iconify-icon>
                            Registra Vendita
                        </h5>
                        <button type="button" wire:click="chiudiRegistraVenditaModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Articolo/Prodotto</label>
                            <div class="p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold text-primary">{{ $itemVendita['item']->codice }}</span>
                                        <p class="mb-0 small text-muted">{{ $itemVendita['item']->descrizione }}</p>
                                    </div>
                                    <span class="badge bg-light-{{ $itemVendita['tipo'] === 'articolo' ? 'primary' : 'warning' }} text-{{ $itemVendita['tipo'] === 'articolo' ? 'primary' : 'warning' }}">
                                        {{ $itemVendita['tipo'] === 'articolo' ? 'Articolo' : 'PF' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantità da vendere</label>
                            <input type="number" class="form-control @error('quantitaVendita') is-invalid @enderror" 
                                   wire:model="quantitaVendita"
                                   min="1" 
                                   max="{{ $itemVendita['quantita_disponibile'] }}">
                            <div class="form-text">Disponibile: {{ $itemVendita['quantita_disponibile'] }}</div>
                            @error('quantitaVendita')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                            <strong>Costo unitario:</strong> €{{ number_format($itemVendita['costo_unitario'], 2, ',', '.') }}<br>
                            <strong>Totale vendita:</strong> €{{ number_format($itemVendita['costo_unitario'] * $quantitaVendita, 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiRegistraVenditaModal">
                            Annulla
                        </button>
                        <button type="button" class="btn btn-success" wire:click="registraVendita">
                            <iconify-icon icon="solar:cart-check-bold" class="me-1"></iconify-icon>
                            Registra Vendita
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>