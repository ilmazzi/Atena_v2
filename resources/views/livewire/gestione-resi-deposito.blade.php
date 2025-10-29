<div>
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Gestione Resi Conti Deposito</h4>
                    <p class="text-muted mb-0">Gestisci i resi dai conti deposito</p>
                </div>
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

    {{-- Statistiche --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:import-bold-duotone" class="fs-2 text-warning"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">{{ $statistiche['resi_today'] }}</h5>
                            <p class="text-muted mb-0 small">Resi Oggi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:document-text-bold-duotone" class="fs-2 text-info"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">{{ $statistiche['resi_da_ddt'] }}</h5>
                            <p class="text-muted mb-0 small">Resi in Attesa DDT</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:euro-bold-duotone" class="fs-2 text-success"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">€{{ number_format($statistiche['valore_totale_resi'], 0, ',', '.') }}</h5>
                            <p class="text-muted mb-0 small">Valore Resi (Ultimo Mese)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtri --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text">
                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon>
                </span>
                <input type="text" 
                       class="form-control" 
                       placeholder="Cerca articolo o deposito..." 
                       wire:model.debounce.300ms="search">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="filtroDeposito">
                <option value="">Tutti i depositi</option>
                @foreach($depositi as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->codice }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="filtroStato">
                <option value="">Tutti i resi</option>
                <option value="da_ddt">In attesa DDT</option>
            </select>
        </div>
        <div class="col-md-2 text-end">
            <span class="text-muted small">Totale: {{ $resi->total() }}</span>
        </div>
    </div>

    {{-- Tabella Resi --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Data</th>
                            <th width="120">Deposito</th>
                            <th width="80">Tipo</th>
                            <th width="120">Codice</th>
                            <th>Descrizione</th>
                            <th width="80">Q.tà</th>
                            <th width="100">Valore</th>
                            <th width="120">DDT Reso</th>
                            <th width="150" class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resi as $reso)
                            @php
                                $item = $reso->articolo ?? $reso->prodottoFinito;
                                $deposito = $reso->contoDeposito;
                            @endphp
                            <tr>
                                <td>
                                    <small>{{ $reso->data_movimento->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('conti-deposito.show', $deposito->id) }}" 
                                       class="text-primary fw-bold">
                                        {{ $deposito->codice }}
                                    </a>
                                    <br><small class="text-muted">{{ $deposito->sedeMittente->nome ?? '' }} → {{ $deposito->sedeDestinataria->nome ?? '' }}</small>
                                </td>
                                <td>
                                    @if($reso->articolo_id)
                                        <span class="badge bg-light-primary text-primary">Art</span>
                                    @else
                                        <span class="badge bg-light-warning text-warning">PF</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $item->codice ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    {{ Str::limit($item->descrizione ?? 'N/A', 40) }}
                                </td>
                                <td class="text-center">
                                    {{ $reso->quantita }}
                                </td>
                                <td class="text-end">
                                    €{{ number_format($reso->costo_totale, 2, ',', '.') }}
                                </td>
                                <td>
                                    @if($deposito->ddt_reso_id && $deposito->ddtReso)
                                        <a href="{{ route('ddt-deposito.stampa', $deposito->ddt_reso_id) }}" 
                                           class="badge bg-success text-white" 
                                           target="_blank">
                                            {{ $deposito->ddtReso->numero }}
                                        </a>
                                    @elseif($reso->ddt_id && $reso->ddt)
                                        <a href="{{ route('ddt.show', $reso->ddt_id) }}" 
                                           class="badge bg-success text-white" 
                                           target="_blank">
                                            {{ $reso->ddt->numero }}
                                        </a>
                                    @else
                                        <span class="badge bg-light-warning text-warning">Da generare</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        @if(!$deposito->ddt_reso_id)
                                            <button class="btn btn-warning" 
                                                    wire:click="generaDdtReso({{ $deposito->id }})"
                                                    title="Genera DDT Reso">
                                                <iconify-icon icon="solar:document-text-bold"></iconify-icon>
                                            </button>
                                        @endif
                                        <a href="{{ route('conti-deposito.show', $deposito->id) }}" 
                                           class="btn btn-light"
                                           title="Vedi deposito">
                                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <iconify-icon icon="solar:document-text-bold" class="fs-1 text-muted"></iconify-icon>
                                    <p class="text-muted mt-2 mb-0">Nessun reso trovato</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($resi->hasPages())
            <div class="card-footer">
                {{ $resi->links() }}
            </div>
        @endif
    </div>
</div>
