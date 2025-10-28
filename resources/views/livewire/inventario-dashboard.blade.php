<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                        Dashboard Inventario
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Statistiche Generali -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ $statistiche['sessioni_attive'] }}</h4>
                                            <p class="mb-0">Sessioni Attive</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <iconify-icon icon="solar:check-circle-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ $statistiche['sessioni_totali'] }}</h4>
                                            <p class="mb-0">Sessioni Totali</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ number_format($statistiche['articoli_eliminati_totale']) }}</h4>
                                            <p class="mb-0">Articoli Eliminati</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <iconify-icon icon="solar:euro-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">€{{ number_format($statistiche['valore_eliminato_totale'], 2) }}</h4>
                                            <p class="mb-0">Valore Eliminato</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtri -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Filtra per Sede:</label>
                            <select wire:model.live="sedeSelezionata" class="form-select" wire:change="filtraPerSede">
                                <option value="">Tutte le sedi</option>
                                @foreach($sedi as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button wire:click="caricaDashboard" class="btn btn-primary">
                                <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                Aggiorna
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessioni Attive -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <iconify-icon icon="solar:play-circle-bold-duotone"></iconify-icon>
                        Sessioni Attive
                    </h4>
                </div>
                <div class="card-body">
                    @if($sessioniAttive->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Sede</th>
                                        <th>Utente</th>
                                        <th>Data Inizio</th>
                                        <th>Progresso</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sessioniAttive as $sessione)
                                        <tr>
                                            <td>
                                                <strong>{{ $sessione->nome }}</strong>
                                            </td>
                                            <td>{{ $sessione->sede->nome }}</td>
                                            <td>{{ $sessione->utente->name }}</td>
                                            <td>{{ $sessione->data_inizio->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $sessione->progresso }}%"
                                                         aria-valuenow="{{ $sessione->progresso }}" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        {{ $sessione->progresso }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('inventario.scanner', $sessione->id) }}" 
                                                       class="btn btn-sm btn-primary">
                                                        <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                                                        Scanner
                                                    </a>
                                                    <a href="{{ route('inventario.monitor', $sessione->id) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                                                        Monitor
                                                    </a>
                                                    <button wire:click="chiudiSessione({{ $sessione->id }})" 
                                                            class="btn btn-sm btn-success"
                                                            onclick="return confirm('Sei sicuro di voler chiudere questa sessione? Gli articoli non trovati verranno eliminati.')">
                                                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                                        Chiudi
                                                    </button>
                                                    <button wire:click="annullaSessione({{ $sessione->id }})" 
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Sei sicuro di voler annullare questa sessione? Tutte le scansioni verranno eliminate.')">
                                                        <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                                                        Annulla
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1 text-muted"></iconify-icon>
                            <p class="text-muted mt-2">Nessuna sessione attiva</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sessioni Recenti -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                        Sessioni Recenti
                    </h4>
                </div>
                <div class="card-body">
                    @if($sessioniRecenti->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Sede</th>
                                        <th>Utente</th>
                                        <th>Data Inizio</th>
                                        <th>Data Fine</th>
                                        <th>Risultati</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sessioniRecenti as $sessione)
                                        <tr>
                                            <td>
                                                <strong>{{ $sessione->nome }}</strong>
                                                <span class="badge bg-{{ $sessione->stato === 'chiusa' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($sessione->stato) }}
                                                </span>
                                            </td>
                                            <td>{{ $sessione->sede->nome }}</td>
                                            <td>{{ $sessione->utente->name }}</td>
                                            <td>{{ $sessione->data_inizio->format('d/m/Y H:i') }}</td>
                                            <td>{{ $sessione->data_fine ? $sessione->data_fine->format('d/m/Y H:i') : 'N/A' }}</td>
                                            <td>
                                                <small class="text-muted">
                                                    Trovati: {{ $sessione->articoli_trovati }}<br>
                                                    Eliminati: {{ $sessione->articoli_eliminati }}<br>
                                                    Valore: €{{ number_format($sessione->valore_eliminato, 2) }}
                                                </small>
                                            </td>
                                            <td>
                                                <a href="{{ route('inventario.report', $sessione->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                                                    Report
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <iconify-icon icon="solar:history-bold-duotone" class="fs-1 text-muted"></iconify-icon>
                            <p class="text-muted mt-2">Nessuna sessione recente</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>