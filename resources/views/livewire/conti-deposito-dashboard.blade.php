<div>
    {{-- Header con statistiche --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Gestione Conti Deposito</h4>
                    <p class="text-muted mb-0">Monitora e gestisci i depositi articoli tra sedi</p>
                </div>
                <button class="btn btn-primary" wire:click="apriNuovoDepositoModal">
                    <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
                    Nuovo Deposito
                </button>
            </div>
        </div>
    </div>

    {{-- Statistiche --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-light-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:box-bold-duotone" class="fs-2 text-primary"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">{{ $statistiche['depositi_attivi'] }}</h5>
                            <p class="text-muted mb-0 small">Depositi Attivi</p>
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
                            <iconify-icon icon="solar:clock-circle-bold-duotone" class="fs-2 text-warning"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">{{ $statistiche['depositi_in_scadenza'] }}</h5>
                            <p class="text-muted mb-0 small">In Scadenza</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:danger-bold-duotone" class="fs-2 text-danger"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">{{ $statistiche['depositi_scaduti'] }}</h5>
                            <p class="text-muted mb-0 small">Scaduti</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <iconify-icon icon="solar:euro-bold-duotone" class="fs-2 text-success"></iconify-icon>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-0">€{{ number_format($statistiche['valore_totale_depositi'], 0, ',', '.') }}</h5>
                            <p class="text-muted mb-0 small">Valore Totale</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if($alerts->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                @foreach($alerts as $alert)
                    <div class="alert alert-{{ $alert['tipo'] }} d-flex align-items-center" role="alert">
                        <iconify-icon icon="{{ $alert['icona'] }}" class="me-2 fs-5"></iconify-icon>
                        <div class="flex-grow-1">
                            <strong>{{ $alert['titolo'] }}:</strong> {{ $alert['messaggio'] }}
                        </div>
                        <button class="btn btn-sm btn-{{ $alert['tipo'] }}" 
                                wire:click="applicaFiltroAlert('{{ $alert['azione'] }}', '{{ $alert['valore'] }}')">
                            Visualizza
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filtri --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cerca</label>
                    <input type="text" class="form-control" wire:model.live="search" 
                           placeholder="Codice, sede...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stato</label>
                    <select class="form-select" wire:model.live="filtroStato">
                        <option value="">Tutti</option>
                        <option value="attivo">Attivo</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="chiuso">Chiuso</option>
                        <option value="parziale">Parziale</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sede</label>
                    <select class="form-select" wire:model.live="filtroSede">
                        <option value="">Tutte</option>
                        @foreach($sedi as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Scadenza</label>
                    <select class="form-select" wire:model.live="filtroScadenza">
                        <option value="">Tutte</option>
                        <option value="scaduti">Scaduti</option>
                        <option value="in_scadenza_30">Scadenza 30gg</option>
                        <option value="in_scadenza_60">Scadenza 60gg</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-light w-100" wire:click="resetFiltri">
                        <iconify-icon icon="solar:refresh-bold" class="me-1"></iconify-icon>
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabella depositi --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Codice</th>
                            <th>DDT</th>
                            <th>Sedi</th>
                            <th>Data Invio</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                            <th>Articoli</th>
                            <th>Valore</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depositi as $deposito)
                            <tr>
                                <td>
                                    <span class="fw-bold text-primary">{{ $deposito->codice }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($deposito->ddt_invio_id && $deposito->ddtInvio)
                                            <div class="d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:export-bold" class="text-primary small"></iconify-icon>
                                                <a href="{{ route('ddt-deposito.stampa', $deposito->ddt_invio_id) }}" 
                                                   class="text-primary small fw-bold" target="_blank" title="DDT Invio">
                                                    {{ $deposito->ddtInvio->numero }}
                                                </a>
                                            </div>
                                        @endif
                                        @if($deposito->ddt_reso_id && $deposito->ddtReso)
                                            <div class="d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:import-bold" class="text-warning small"></iconify-icon>
                                                <a href="{{ route('ddt-deposito.stampa', $deposito->ddt_reso_id) }}" 
                                                   class="text-warning small fw-bold" target="_blank" title="DDT Reso">
                                                    {{ $deposito->ddtReso->numero }}
                                                </a>
                                            </div>
                                        @endif
                                        @if(!$deposito->ddt_invio_id && !$deposito->ddt_reso_id)
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <iconify-icon icon="solar:export-bold" class="text-muted me-1"></iconify-icon>
                                        <small>{{ $deposito->sedeMittente->nome }}</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <iconify-icon icon="solar:import-bold" class="text-muted me-1"></iconify-icon>
                                        <small>{{ $deposito->sedeDestinataria->nome }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="small">{{ $deposito->data_invio->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light-{{ $this->getColoreScadenza($deposito->data_scadenza) }} text-{{ $this->getColoreScadenza($deposito->data_scadenza) }} me-2">
                                            {{ $deposito->data_scadenza->format('d/m/Y') }}
                                        </span>
                                        @php $giorni = $this->getGiorniRimanenti($deposito->data_scadenza); @endphp
                                        @if($giorni < 0)
                                            <small class="text-danger">Scaduto</small>
                                        @elseif($giorni <= 30)
                                            <small class="text-warning">{{ $giorni }}gg</small>
                                        @else
                                            <small class="text-muted">{{ $giorni }}gg</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light-{{ $deposito->stato_color }} text-{{ $deposito->stato_color }}">
                                        {{ $deposito->stato_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>Inviati: <strong>{{ $deposito->articoli_inviati }}</strong></div>
                                        @if($deposito->articoli_venduti > 0)
                                            <div class="text-success">Venduti: {{ $deposito->articoli_venduti }}</div>
                                        @endif
                                        @if($deposito->articoli_rientrati > 0)
                                            <div class="text-info">Rientrati: {{ $deposito->articoli_rientrati }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>€{{ number_format($deposito->valore_totale_invio, 0, ',', '.') }}</div>
                                        @if($deposito->valore_venduto > 0)
                                            <div class="text-success">-€{{ number_format($deposito->valore_venduto, 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        {{-- Tasto Gestisci (principale) --}}
                                        <a href="{{ route('conti-deposito.gestisci', $deposito->id) }}" 
                                           class="btn btn-primary btn-sm"
                                           title="Gestisci deposito">
                                            <iconify-icon icon="solar:settings-bold" class="me-1"></iconify-icon>
                                            Gestisci
                                        </a>
                                        
                                        {{-- Tasto Dettagli --}}
                                        <button class="btn btn-light btn-sm" 
                                                wire:click="apriDettaglioModal({{ $deposito->id }})"
                                                title="Visualizza dettagli">
                                            <iconify-icon icon="solar:eye-bold" class="text-info"></iconify-icon>
                                        </button>
                                        
                                        @if($deposito->stato === 'attivo' && $deposito->isScaduto())
                                            <button class="btn btn-light btn-sm" 
                                                    wire:click="gestisciResoScadenza({{ $deposito->id }})"
                                                    title="Gestisci reso scadenza">
                                                <iconify-icon icon="solar:import-bold" class="text-warning"></iconify-icon>
                                            </button>
                                        @endif
                                        
                                        @if($deposito->stato === 'chiuso' && $deposito->puoEssereRinnovato())
                                            <button class="btn btn-light btn-sm" 
                                                    wire:click="creaRimando({{ $deposito->id }})"
                                                    title="Rimanda in deposito">
                                                <iconify-icon icon="solar:refresh-bold" class="text-success"></iconify-icon>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <iconify-icon icon="solar:box-bold" class="fs-1 mb-2"></iconify-icon>
                                        <p class="mb-0">Nessun deposito trovato</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginazione --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Mostrando {{ $depositi->firstItem() ?? 0 }} - {{ $depositi->lastItem() ?? 0 }} 
                    di {{ $depositi->total() }} depositi
                </div>
                {{ $depositi->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Nuovo Deposito --}}
    @if($showNuovoDepositoModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:add-circle-bold-duotone" class="me-2"></iconify-icon>
                            Nuovo Conto Deposito
                        </h5>
                        <button type="button" wire:click="chiudiNuovoDepositoModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sede Mittente *</label>
                            <select class="form-select @error('sedeMittenteId') is-invalid @enderror" 
                                    wire:model="sedeMittenteId">
                                <option value="">Seleziona sede mittente</option>
                                @foreach($sedi as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                                @endforeach
                            </select>
                            @error('sedeMittenteId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sede Destinataria *</label>
                            <select class="form-select @error('sedeDestinatariaId') is-invalid @enderror" 
                                    wire:model="sedeDestinatariaId">
                                <option value="">Seleziona sede destinataria</option>
                                @foreach($sedi as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                                @endforeach
                            </select>
                            @error('sedeDestinatariaId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note</label>
                            <textarea class="form-control @error('noteDeposito') is-invalid @enderror" 
                                      wire:model="noteDeposito" 
                                      rows="3" 
                                      placeholder="Note aggiuntive..."></textarea>
                            @error('noteDeposito')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                            Dopo la creazione potrai aggiungere articoli e prodotti finiti al deposito.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiNuovoDepositoModal">
                            Annulla
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="creaDeposito">
                            <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>
                            Crea Deposito
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Modal Dettaglio Deposito --}}
    @if($showDettaglioModal && $depositoSelezionato)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:box-bold-duotone" class="me-2"></iconify-icon>
                            Dettaglio Deposito {{ $depositoSelezionato->codice }}
                        </h5>
                        <button type="button" wire:click="chiudiDettaglioModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Informazioni generali --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Informazioni Generali</h6>
                                <p><strong>Sede Mittente:</strong> {{ $depositoSelezionato->sedeMittente->nome }}</p>
                                <p><strong>Sede Destinataria:</strong> {{ $depositoSelezionato->sedeDestinataria->nome }}</p>
                                <p><strong>Data Invio:</strong> {{ $depositoSelezionato->data_invio->format('d/m/Y') }}</p>
                                <p><strong>Data Scadenza:</strong> {{ $depositoSelezionato->data_scadenza->format('d/m/Y') }}</p>
                                <p><strong>Stato:</strong> 
                                    <span class="badge bg-light-{{ $depositoSelezionato->stato_color }} text-{{ $depositoSelezionato->stato_color }}">
                                        {{ $depositoSelezionato->stato_label }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Statistiche</h6>
                                <p><strong>Articoli Inviati:</strong> {{ $depositoSelezionato->articoli_inviati }}</p>
                                <p><strong>Articoli Venduti:</strong> {{ $depositoSelezionato->articoli_venduti }}</p>
                                <p><strong>Articoli Rientrati:</strong> {{ $depositoSelezionato->articoli_rientrati }}</p>
                                <p><strong>Valore Totale:</strong> €{{ number_format($depositoSelezionato->valore_totale_invio, 2, ',', '.') }}</p>
                                <p><strong>Valore Venduto:</strong> €{{ number_format($depositoSelezionato->valore_venduto, 2, ',', '.') }}</p>
                            </div>
                        </div>

                        {{-- Movimenti --}}
                        @if($depositoSelezionato->movimenti->count() > 0)
                            <h6 class="fw-bold">Movimenti</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Tipo</th>
                                            <th>Articolo/PF</th>
                                            <th>Qtà</th>
                                            <th>Valore</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($depositoSelezionato->movimenti->sortBy('data_movimento') as $movimento)
                                            <tr>
                                                <td class="small">{{ $movimento->data_movimento->format('d/m/Y') }}</td>
                                                <td>
                                                    <span class="badge bg-light-{{ $movimento->tipo_movimento_color }} text-{{ $movimento->tipo_movimento_color }}">
                                                        {{ $movimento->tipo_movimento_label }}
                                                    </span>
                                                </td>
                                                <td class="small">
                                                    <strong>{{ $movimento->getCodiceItem() }}</strong><br>
                                                    {{ Str::limit($movimento->getDescrizioneItem(), 30) }}
                                                </td>
                                                <td>{{ $movimento->quantita }}</td>
                                                <td class="small">{{ $movimento->costo_totale_formatted }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Note --}}
                        @if($depositoSelezionato->note)
                            <div class="mt-3">
                                <h6 class="fw-bold">Note</h6>
                                <p class="text-muted">{{ $depositoSelezionato->note }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiDettaglioModal">
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>