<div>
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Notifiche Conti Deposito</h4>
                    <p class="text-muted mb-0">Gestisci le notifiche dei conti deposito</p>
                </div>
                <div class="d-flex gap-2">
                    @if($societaSelezionata)
                        <button class="btn btn-light" wire:click="marcaTutteComeLette">
                            <iconify-icon icon="solar:check-read-bold" class="me-1"></iconify-icon>
                            Segna tutte come lette
                        </button>
                    @endif
                    <select class="form-select" wire:model.live="societaSelezionata" style="width: auto;">
                        <option value="">Seleziona società...</option>
                        @foreach($societaList as $soc)
                            <option value="{{ $soc->id }}">{{ $soc->codice }} - {{ $soc->ragione_sociale }}</option>
                        @endforeach
                    </select>
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

    @if($societaSelezionata)
        {{-- Statistiche --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 bg-light-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:bell-bold-duotone" class="fs-2 text-primary"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold mb-0">{{ $statistiche['totali'] }}</h5>
                                <p class="text-muted mb-0 small">Totale Notifiche</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-light-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:bell-ringing-bold-duotone" class="fs-2 text-warning"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-bold mb-0">{{ $statistiche['non_lette'] }}</h5>
                                <p class="text-muted mb-0 small">Non Lette</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-light-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:import-bold-duotone" class="fs-2 text-warning"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h5 class="fw-bold mb-0">{{ $statistiche['resi'] }}</h5>
                                <p class="text-muted mb-0 small">Resi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-light-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:cart-check-bold-duotone" class="fs-2 text-success"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h5 class="fw-bold mb-0">{{ $statistiche['vendite'] }}</h5>
                                <p class="text-muted mb-0 small">Vendite</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-light-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:clock-circle-bold-duotone" class="fs-2 text-danger"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h5 class="fw-bold mb-0">{{ $statistiche['scadenze'] }}</h5>
                                <p class="text-muted mb-0 small">Scadenze</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtri --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" wire:model.live="filtroTipo">
                    <option value="">Tutti i tipi</option>
                    <option value="reso">Resi</option>
                    <option value="vendita">Vendite</option>
                    <option value="scadenza">Scadenze</option>
                    <option value="deposito_scaduto">Depositi Scaduti</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filtroLettura">
                    <option value="">Tutte</option>
                    <option value="non_lette">Non lette</option>
                    <option value="lette">Lette</option>
                </select>
            </div>
            <div class="col-md-5 text-end">
                <span class="text-muted small">Totale: {{ $notifiche->total() }} notifiche</span>
            </div>
        </div>

        {{-- Lista Notifiche --}}
        <div class="card">
            <div class="card-body p-0">
                @forelse($notifiche as $notifica)
                    <div class="border-bottom {{ !$notifica->letta ? 'bg-light' : '' }}" 
                         wire:key="notifica-{{ $notifica->id }}">
                        <div class="p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <iconify-icon icon="{{ $notifica->getIcona() }}" 
                                                  class="fs-3 text-{{ $notifica->getColoreBadge() }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                @if(!$notifica->letta)
                                                    <span class="badge bg-{{ $notifica->getColoreBadge() }} me-2">Nuova</span>
                                                @endif
                                                {{ $notifica->titolo }}
                                            </h6>
                                            <p class="text-muted mb-2">{{ $notifica->messaggio }}</p>
                                            <div class="d-flex gap-3 small text-muted">
                                                <span>
                                                    <iconify-icon icon="solar:calendar-bold" class="me-1"></iconify-icon>
                                                    {{ $notifica->created_at->format('d/m/Y H:i') }}
                                                </span>
                                                @if($notifica->contoDeposito)
                                                    <span>
                                                        <iconify-icon icon="solar:box-bold" class="me-1"></iconify-icon>
                                                        Deposito: {{ $notifica->contoDeposito->codice }}
                                                    </span>
                                                @endif
                                                @if($notifica->email_inviata)
                                                    <span class="text-success">
                                                        <iconify-icon icon="solar:letter-bold" class="me-1"></iconify-icon>
                                                        Email inviata
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                                <iconify-icon icon="solar:menu-dots-bold"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if(!$notifica->letta)
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="marcaComeLetta({{ $notifica->id }})">
                                                            <iconify-icon icon="solar:check-read-bold" class="me-2"></iconify-icon>
                                                            Segna come letta
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($notifica->contoDeposito)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('conti-deposito.show', $notifica->contoDeposito->id) }}">
                                                            <iconify-icon icon="solar:eye-bold" class="me-2"></iconify-icon>
                                                            Vedi deposito
                                                        </a>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" wire:click.prevent="eliminaNotifica({{ $notifica->id }})">
                                                        <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="me-2"></iconify-icon>
                                                        Elimina
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center">
                        <iconify-icon icon="solar:bell-off-bold-duotone" class="fs-1 text-muted"></iconify-icon>
                        <p class="text-muted mt-2 mb-0">Nessuna notifica trovata</p>
                    </div>
                @endforelse
            </div>
            @if($notifiche->hasPages())
                <div class="card-footer">
                    {{ $notifiche->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-info text-center">
            <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
            Seleziona una società per visualizzare le notifiche
        </div>
    @endif
</div>
