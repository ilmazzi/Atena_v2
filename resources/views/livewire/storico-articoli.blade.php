<div>
    <!-- Messaggi Flash -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                        Storico Articoli Eliminati
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Statistiche -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ number_format($statistiche['totale_articoli']) }}</h4>
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
                                            <h4 class="mb-0">€{{ number_format($statistiche['valore_totale'], 2) }}</h4>
                                            <p class="mb-0">Valore Totale</p>
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
                                            <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ $statistiche['per_motivo']->get('inventario', 0) }}</h4>
                                            <p class="mb-0">Da Inventario</p>
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
                                            <iconify-icon icon="solar:buildings-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="mb-0">{{ $statistiche['per_sede']->count() }}</h4>
                                            <p class="mb-0">Sedi Coinvolte</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtri -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <label class="form-label">Sede:</label>
                            <select wire:model.live="filtroSede" class="form-select">
                                <option value="">Tutte</option>
                                @foreach($sedi as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Motivo:</label>
                            <select wire:model.live="filtroMotivo" class="form-select">
                                <option value="">Tutti</option>
                                <option value="inventario">Inventario</option>
                                <option value="vendita">Vendita</option>
                                <option value="manuale">Manuale</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Data Inizio:</label>
                            <input type="date" wire:model.live="filtroDataInizio" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Data Fine:</label>
                            <input type="date" wire:model.live="filtroDataFine" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Codice:</label>
                            <input type="text" wire:model.live="filtroCodice" class="form-control" placeholder="Cerca per codice...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button wire:click="$refresh" class="btn btn-primary">
                                <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                Aggiorna
                            </button>
                        </div>
                    </div>

                    <!-- Controlli Selezione -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-2 align-items-center">
                                <button wire:click="toggleSelezionaTutti" class="btn btn-secondary btn-sm">
                                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                    {{ $selezionaTutti ? 'Deseleziona Tutti' : 'Seleziona Tutti' }}
                                </button>
                                <span class="text-muted">
                                    {{ count($articoliSelezionati) }} articoli selezionati
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            @if(count($articoliSelezionati) > 0)
                                <button wire:click="apriModalRipristinoMultiplo" class="btn btn-success">
                                    <iconify-icon icon="solar:restart-bold-duotone"></iconify-icon>
                                    Ripristina Selezionati ({{ count($articoliSelezionati) }})
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Tabella Articoli -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" 
                                               wire:click="toggleSelezionaTutti"
                                               @if($selezionaTutti) checked @endif
                                               class="form-check-input">
                                    </th>
                                    <th>Codice</th>
                                    <th>Descrizione</th>
                                    <th>Sede</th>
                                    <th>Motivo</th>
                                    <th>Data Eliminazione</th>
                                    <th>Utente</th>
                                    <th>Valore</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($articoli as $articolo)
                                    <tr>
                                        <td>
                                            <input type="checkbox" 
                                                   wire:click="toggleArticolo({{ $articolo->id }})"
                                                   @if(in_array($articolo->id, $articoliSelezionati)) checked @endif
                                                   class="form-check-input">
                                        </td>
                                        <td>
                                            <strong>{{ $articolo->codice }}</strong>
                                        </td>
                                        <td>{{ Str::limit($articolo->descrizione, 50) }}</td>
                                        <td>
                                            @if($articolo->sessioneInventario)
                                                {{ $articolo->sessioneInventario->sede->nome }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $articolo->motivo_eliminazione === 'inventario' ? 'warning' : 'info' }}">
                                                {{ ucfirst($articolo->motivo_eliminazione) }}
                                            </span>
                                        </td>
                                        <td>{{ $articolo->data_eliminazione->format('d/m/Y H:i') }}</td>
                                        <td>{{ $articolo->utente->name }}</td>
                                        <td>€{{ number_format($articolo->valore_eliminato, 2) }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button wire:click="selezionaArticolo({{ $articolo->id }})" 
                                                        class="btn btn-sm btn-primary">
                                                    <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                                                    Dettagli
                                                </button>
                                                <button wire:click="ripristinaArticolo({{ $articolo->id }})" 
                                                        class="btn btn-sm btn-success"
                                                        onclick="return confirm('Sei sicuro di voler ripristinare questo articolo?')">
                                                    <iconify-icon icon="solar:restart-bold-duotone"></iconify-icon>
                                                    Ripristina
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginazione -->
                    <div class="d-flex justify-content-center">
                        {{ $articoli->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dettagli Articolo -->
    @if($showModal && $articoloSelezionato)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                            Dettagli Articolo Eliminato
                        </h5>
                        <button type="button" wire:click="chiudiModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informazioni Articolo</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Codice:</strong></td>
                                        <td>{{ $articoloSelezionato->codice }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Descrizione:</strong></td>
                                        <td>{{ $articoloSelezionato->descrizione }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Valore:</strong></td>
                                        <td>€{{ number_format($articoloSelezionato->valore_eliminato, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Categoria:</strong></td>
                                        <td>{{ $articoloSelezionato->categoria }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sede:</strong></td>
                                        <td>{{ $articoloSelezionato->sede }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Informazioni Eliminazione</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Motivo:</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $articoloSelezionato->motivo_eliminazione === 'inventario' ? 'warning' : 'info' }}">
                                                {{ ucfirst($articoloSelezionato->motivo_eliminazione) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Data Eliminazione:</strong></td>
                                        <td>{{ $articoloSelezionato->data_eliminazione->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Utente:</strong></td>
                                        <td>{{ $articoloSelezionato->utente->name }}</td>
                                    </tr>
                                    @if($articoloSelezionato->sessioneInventario)
                                        <tr>
                                            <td><strong>Sessione:</strong></td>
                                            <td>{{ $articoloSelezionato->sessioneInventario->nome }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <!-- Dati Completi (JSON) -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Dati Completi (Backup)</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($articoloSelezionato->dati_completi, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Relazioni Storiche -->
                        @if($articoloSelezionato->relazioni_storico)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6>Relazioni Storiche</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <pre class="mb-0" style="max-height: 200px; overflow-y: auto;">{{ json_encode($articoloSelezionato->relazioni_storico, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModal" class="btn btn-secondary">Chiudi</button>
                        <button type="button" wire:click="ripristinaArticolo({{ $articoloSelezionato->id }})" 
                                class="btn btn-success"
                                onclick="return confirm('Sei sicuro di voler ripristinare questo articolo?')">
                            <iconify-icon icon="solar:restart-bold-duotone"></iconify-icon>
                            Ripristina Articolo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Ripristino Multiplo -->
    @if($showModalRipristinoMultiplo)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:restart-bold-duotone"></iconify-icon>
                            Conferma Ripristino Multiplo
                        </h5>
                        <button type="button" wire:click="chiudiModalRipristinoMultiplo" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
                            <strong>Attenzione!</strong> Stai per ripristinare {{ count($articoliSelezionati) }} articoli dallo storico.
                            @if(count($articoliSelezionati) > 100)
                                <br><strong>⚠️ Limite:</strong> Massimo 100 articoli alla volta. Seleziona meno articoli.
                            @endif
                        </div>

                        <p>Questa operazione:</p>
                        <ul>
                            <li>✅ Creerà nuovi articoli nella tabella principale</li>
                            <li>✅ Ripristinerà le giacenze se presenti</li>
                            <li>❌ <strong>NON</strong> eliminerà i record dallo storico</li>
                        </ul>
                        
                        <p class="mb-0">
                            <strong>Sei sicuro di voler procedere?</strong>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModalRipristinoMultiplo" class="btn btn-secondary">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Annulla
                        </button>
                        <button type="button" wire:click="confermaRipristinoMultiplo" 
                                class="btn btn-success"
                                @if(count($articoliSelezionati) > 100) disabled @endif>
                            <iconify-icon icon="solar:restart-bold-duotone"></iconify-icon>
                            Ripristina {{ count($articoliSelezionati) }} Articoli
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>