<div>
    @if(!$sessione)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                            Scanner Inventario
                        </h3>
                    </div>
                    <div class="card-body">
                        @if(count($sessioniDisponibili) > 0)
                            <div class="text-center mb-4">
                                <iconify-icon icon="solar:scanner-bold-duotone" class="fs-1 text-primary"></iconify-icon>
                                <h4 class="mt-3">Seleziona Sessione Inventario</h4>
                                <p class="text-muted">Scegli una sessione attiva per iniziare la scansione.</p>
                            </div>
                            
                            <div class="row">
                                @foreach($sessioniDisponibili as $sessione)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $sessione['nome'] }}</h5>
                                                <p class="card-text">
                                                    <strong>Sede:</strong> {{ $sessione['sede'] }}<br>
                                                    <strong>Iniziata:</strong> {{ $sessione['data_inizio'] }}
                                                </p>
                                                <button wire:click="selezionaSessione" 
                                                        wire:model="sessioneSelezionata" 
                                                        value="{{ $sessione['id'] }}"
                                                        class="btn btn-primary">
                                                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                                    Seleziona
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center">
                                <iconify-icon icon="solar:scanner-bold-duotone" class="fs-1 text-muted"></iconify-icon>
                                <h4 class="text-muted mt-3">Nessuna Sessione Attiva</h4>
                                <p class="text-muted">Non ci sono sessioni di inventario attive al momento.</p>
                                <a href="{{ route('inventario.sessioni') }}" class="btn btn-primary">
                                    <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                                    Gestisci Sessioni
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Header Sessione -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                            Scanner Inventario - {{ $sessione->nome }}
                        </h3>
                        <div class="card-tools">
                            <span class="badge bg-{{ $sessione->stato === 'attiva' ? 'success' : 'secondary' }}">
                                {{ ucfirst($sessione->stato) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Sede:</strong> {{ $sessione->sede->nome }}
                            </div>
                            <div class="col-md-3">
                                <strong>Utente:</strong> {{ $sessione->utente->name }}
                            </div>
                            <div class="col-md-3">
                                <strong>Data Inizio:</strong> {{ $sessione->data_inizio->format('d/m/Y H:i') }}
                            </div>
                            <div class="col-md-3">
                                <strong>Progresso:</strong> {{ $sessione->progresso }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $statistiche['scansioni_totali'] }}</h4>
                                <p class="mb-0">Scansioni Totali</p>
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
                                <h4 class="mb-0">{{ $statistiche['articoli_trovati'] }}</h4>
                                <p class="mb-0">Articoli Trovati</p>
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
                                <h4 class="mb-0">{{ $statistiche['articoli_eliminati'] }}</h4>
                                <p class="mb-0">Articoli Eliminati</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <iconify-icon icon="solar:exclamation-circle-bold-duotone" class="fs-1"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $statistiche['differenze'] }}</h4>
                                <p class="mb-0">Differenze</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanner -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                            Scanner
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Codice Articolo</label>
                            <div class="input-group">
                                <input type="text" wire:model.live="codiceScansionato" 
                                       class="form-control form-control-lg" 
                                       placeholder="Scansiona o inserisci codice..."
                                       autofocus>
                                <button wire:click="cercaArticolo" class="btn btn-primary">
                                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                                    Cerca
                                </button>
                            </div>
                        </div>

                        @if($messaggio)
                            <div class="alert alert-{{ $articoloTrovato ? 'success' : 'warning' }} alert-dismissible fade show">
                                {{ $messaggio }}
                            </div>
                        @endif

                        @if($articoloTrovato)
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $articoloTrovato->codice }}</h5>
                                    <p class="card-text">{{ $articoloTrovato->descrizione }}</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Quantità Sistema:</strong> {{ $quantitaSistema }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Categoria:</strong> {{ $articoloTrovato->categoriaMerceologica->nome ?? 'N/A' }}
                                        </div>
                                    </div>
                                    
                                    @if($quantitaSistema > 1)
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <label class="form-label">Quantità Trovata:</label>
                                                <div class="input-group">
                                                    <input type="number" wire:model.live="quantitaTrovata" 
                                                           wire:change="aggiornaQuantita"
                                                           class="form-control" min="1" max="{{ $quantitaSistema }}">
                                                    <span class="input-group-text">/ {{ $quantitaSistema }}</span>
                                                </div>
                                                <small class="text-muted">
                                                    Inserisci quanti pezzi hai effettivamente trovato (max: {{ $quantitaSistema }})
                                                </small>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            @if($quantitaSistema > 1)
                                                <button wire:click="apriModalQuantita" 
                                                        class="btn btn-success w-100">
                                                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                                    TROVATO ({{ $quantitaTrovata }}/{{ $quantitaSistema }})
                                                </button>
                                            @else
                                                <button wire:click="trovatoCompleto" 
                                                        class="btn btn-success w-100">
                                                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                                    TROVATO
                                                </button>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <button wire:click="eliminaArticolo({{ $articoloTrovato->id }})" 
                                                    class="btn btn-danger w-100">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                                                ELIMINA
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Scansioni Recenti -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                            Scansioni Recenti
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th>Azione</th>
                                        <th>Quantità</th>
                                        <th>Ora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scansioni as $scansione)
                                        <tr>
                                            <td><strong>{{ $scansione->articolo->codice }}</strong></td>
                                            <td>{{ Str::limit($scansione->articolo->descrizione, 30) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $scansione->azione === 'trovato' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($scansione->azione) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($scansione->quantita_trovata)
                                                    {{ $scansione->quantita_trovata }}
                                                    @if($scansione->differenza != 0)
                                                        <small class="text-{{ $scansione->differenza > 0 ? 'success' : 'danger' }}">
                                                            ({{ $scansione->differenza > 0 ? '+' : '' }}{{ $scansione->differenza }})
                                                        </small>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $scansione->data_scansione->format('H:i:s') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Azioni Rapide -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                            Azioni Rapide
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{ route('inventario.dashboard') }}" class="btn btn-primary w-100">
                                    <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                                    Dashboard
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('inventario.sessioni') }}" class="btn btn-secondary w-100">
                                    <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                                    Sessioni
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('inventario.storico') }}" class="btn btn-info w-100">
                                    <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                                    Storico
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button wire:click="caricaScansioni" class="btn btn-success w-100">
                                    <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                    Aggiorna
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Conferma Eliminazione -->
    @if($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
                            Conferma Eliminazione
                        </h5>
                        <button type="button" wire:click="chiudiModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <strong>ATTENZIONE!</strong> Stai per eliminare definitivamente questo articolo:
</div>

                        @if($articoloTrovato)
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $articoloTrovato->codice }}</h6>
                                    <p class="card-text">{{ $articoloTrovato->descrizione }}</p>
                                    <p class="card-text">
                                        <strong>Quantità:</strong> {{ $quantitaTrovata }}<br>
                                        <strong>Valore:</strong> €{{ number_format($articoloTrovato->prezzo_acquisto ?? 0, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endif
                        
                        <p class="text-muted mt-3">
                            L'articolo verrà spostato nello storico e non sarà più visibile nella pagina articoli.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModal" class="btn btn-secondary">Annulla</button>
                        <button type="button" wire:click="confermaEliminazione" class="btn btn-danger">
                            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                            Elimina Definitivamente
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Gestione Quantità -->
    @if($showQuantitaModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:calculator-bold-duotone"></iconify-icon>
                            Gestione Quantità
                        </h5>
                        <button type="button" wire:click="chiudiModalQuantita" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        @if($articoloTrovato)
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $articoloTrovato->codice }}</h6>
                                    <p class="card-text">{{ $articoloTrovato->descrizione }}</p>
                                    <p class="card-text">
                                        <strong>Quantità Sistema:</strong> {{ $quantitaSistema }}<br>
                                        <strong>Quantità Trovata:</strong> 
                                        <input type="number" wire:model.live="quantitaTrovata" 
                                               wire:change="aggiornaQuantita"
                                               class="form-control d-inline-block w-auto" 
                                               min="1" max="{{ $quantitaSistema }}" 
                                               style="width: 80px;">
                                    </p>
                                    
                                    @if($quantitaTrovata < $quantitaSistema)
                                        <div class="alert alert-warning">
                                            <strong>Attenzione!</strong> Hai trovato solo {{ $quantitaTrovata }} pezzi su {{ $quantitaSistema }}. 
                                            La differenza di {{ $quantitaSistema - $quantitaTrovata }} pezzi verrà registrata come mancanza.
                                        </div>
                                    @endif
                                    
                                    @if($quantitaTrovata > $quantitaSistema)
                                        <div class="alert alert-danger">
                                            <strong>Errore!</strong> Non puoi trovare più pezzi di quelli registrati nel sistema.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModalQuantita" class="btn btn-secondary">Annulla</button>
                        <button type="button" wire:click="confermaQuantita" 
                                class="btn btn-success" 
                                @if($quantitaTrovata > $quantitaSistema) disabled @endif>
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                            Conferma Quantità
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Selezione Varianti -->
    @if($showVariantiModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                            Seleziona Variante Articolo
                        </h5>
                        <button type="button" wire:click="chiudiVariantiModal" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Sono state trovate {{ count($variantiTrovate) }} varianti per il codice "{{ $codiceScansionato }}". 
                            Seleziona quella che vuoi scansionare:
                        </p>
                        
                        <div class="row">
                            @foreach($variantiTrovate as $variante)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $variante['codice'] }}</h6>
                                            <p class="card-text">{{ Str::limit($variante['descrizione'], 50) }}</p>
                                            <p class="card-text">
                                                <strong>Quantità:</strong> {{ $variante['quantita'] }} pezzi
                                            </p>
                                            <button wire:click="selezionaVariante({{ $variante['id'] }})" 
                                                    class="btn btn-primary btn-sm">
                                                <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                                Seleziona
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiVariantiModal" class="btn btn-secondary">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>