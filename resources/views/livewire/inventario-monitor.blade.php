<div>
    <!-- Messaggi Flash -->
    @if (session()->has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
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

    @if(!$sessione)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                            Monitor Inventario
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1 text-muted"></iconify-icon>
                        <h4 class="text-muted mt-3">Nessuna Sessione Selezionata</h4>
                        <p class="text-muted">Seleziona una sessione di inventario per monitorare lo stato.</p>
                        <a href="{{ route('inventario.sessioni') }}" class="btn btn-primary">
                            <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                            Vai alle Sessioni
                        </a>
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
                            <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                            Monitor Inventario - {{ $sessione->nome }}
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
                                <strong>Progresso:</strong> {{ $statistiche['progresso'] }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche Dettagliate -->
        <div class="row">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['articoli_totali'] }}</h4>
                        <p class="mb-0">Totali</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['articoli_scansionati'] }}</h4>
                        <p class="mb-0">Scansionati</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['articoli_trovati'] }}</h4>
                        <p class="mb-0">Trovati</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['articoli_eliminati'] }}</h4>
                        <p class="mb-0">Eliminati</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['articoli_non_scansionati'] }}</h4>
                        <p class="mb-0">Mancanti</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-{{ $statistiche['completato'] ? 'success' : 'secondary' }} text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-0">{{ $statistiche['progresso'] }}%</h4>
                        <p class="mb-0">Completato</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra Progresso -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Progresso Inventario</h5>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ $statistiche['progresso'] }}%"
                                 aria-valuenow="{{ $statistiche['progresso'] }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                {{ $statistiche['progresso'] }}%
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <small class="text-success">
                                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                    Trovati: {{ $statistiche['articoli_trovati'] }}
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-warning">
                                    <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                                    Eliminati: {{ $statistiche['articoli_eliminati'] }}
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-danger">
                                    <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                                    Mancanti: {{ $statistiche['articoli_non_scansionati'] }}
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-info">
                                    <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                                    Scansionati: {{ $statistiche['articoli_scansionati'] }}/{{ $statistiche['articoli_totali'] }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <iconify-icon icon="solar:filter-bold-duotone"></iconify-icon>
                            Filtri e Ricerca
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Categoria:</label>
                                <select wire:model.live="categoriaId" class="form-select">
                                    <option value="">Tutte le categorie</option>
                                    @foreach($categorie as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stato Articolo:</label>
                                <select wire:model.live="statoArticolo" class="form-select">
                                    <option value="">Tutti gli articoli</option>
                                    <option value="trovati">Trovati</option>
                                    <option value="mancanti">Eliminati</option>
                                    <option value="non_scansionati">Non Scansionati</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button wire:click="filtraArticoli" class="btn btn-primary me-2">
                                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                                    Filtra
                                </button>
                                <button wire:click="resetFiltri" class="btn btn-secondary me-2">
                                    <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                    Reset
                                </button>
                                <button wire:click="verificaDati" class="btn btn-info me-2">
                                    <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                                    Verifica
                                </button>
                                <button wire:click="confrontaConArticoli" class="btn btn-warning">
                                    <iconify-icon icon="solar:scale-bold-duotone"></iconify-icon>
                                    Confronta
                                </button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <a href="{{ route('inventario.scanner', $sessioneId) }}" class="btn btn-success">
                                    <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                                    Continua Scanner
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista Articoli -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                            Articoli {{ $statoArticolo ? ucfirst(str_replace('_', ' ', $statoArticolo)) : 'Da Inventariare' }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th>Categoria</th>
                                        <th>Quantità Sistema</th>
                                        <th>Stato</th>
                                        <th>Ultima Scansione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($articoli as $articolo)
                                        <tr>
                                            <td><strong>{{ $articolo->codice }}</strong></td>
                                            <td>{{ Str::limit($articolo->descrizione, 50) }}</td>
                                            <td>{{ $articolo->categoriaMerceologica->nome ?? 'N/A' }}</td>
                                            <td>
                                                {{ $articolo->giacenze->where('sede_id', $sessione->sede_id)->sum('quantita_residua') }}
                                            </td>
                                            <td>
                                                @php
                                                    $scansione = \App\Models\InventarioScansione::where('sessione_id', $sessioneId)
                                                        ->where('articolo_id', $articolo->id)
                                                        ->first();
                                                @endphp
                                                @if($scansione)
                                                    <span class="badge bg-{{ $scansione->azione === 'trovato' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($scansione->azione) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Non Scansionato</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($scansione)
                                                    {{ $scansione->data_scansione->format('H:i:s') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('inventario.scanner', $sessioneId) }}?codice={{ $articolo->codice }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                                                    Scanner
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <iconify-icon icon="solar:list-bold-duotone" class="fs-1"></iconify-icon>
                                                <p class="mt-2">Nessun articolo trovato con i filtri selezionati</p>
                                            </td>
                                        </tr>
                                    @endforelse
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

        <!-- Sezione Scansioni Effettuate -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                            Scansioni Effettuate
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $scansioni = $this->getScansioniEffettuate();
                        @endphp
                        
                        @if($scansioni->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data/Ora</th>
                                            <th>Codice</th>
                                            <th>Descrizione</th>
                                            <th>Categoria</th>
                                            <th>Azione</th>
                                            <th>Qta Sistema</th>
                                            <th>Qta Trovata</th>
                                            <th>Differenza</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($scansioni as $scansione)
                                            <tr>
                                                <td>{{ $scansione->data_scansione->format('H:i:s') }}</td>
                                                <td><strong>{{ $scansione->articolo->codice }}</strong></td>
                                                <td>{{ Str::limit($scansione->articolo->descrizione, 30) }}</td>
                                                <td>{{ $scansione->articolo->categoriaMerceologica->nome ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $scansione->azione === 'trovato' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($scansione->azione) }}
                                                    </span>
                                                </td>
                                                <td>{{ $scansione->quantita_sistema }}</td>
                                                <td>{{ $scansione->quantita_trovata ?? '-' }}</td>
                                                <td>
                                                    @if($scansione->differenza != 0)
                                                        <span class="text-{{ $scansione->differenza > 0 ? 'danger' : 'warning' }}">
                                                            {{ $scansione->differenza > 0 ? '+' : '' }}{{ $scansione->differenza }}
                                                        </span>
                                                    @else
                                                        <span class="text-success">0</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <iconify-icon icon="solar:list-bold-duotone" class="fs-1"></iconify-icon>
                                <p class="mt-2">Nessuna scansione effettuata ancora</p>
                            </div>
                        @endif
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
                                <a href="{{ route('inventario.scanner', $sessioneId) }}" class="btn btn-success w-100">
                                    <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                                    Continua Scanner
                                </a>
                            </div>
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
                                <button wire:click="caricaArticoli" class="btn btn-success w-100">
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

    <!-- Modal Risultati Verifica -->
    @if($showModalVerifica && !empty($risultatiVerifica))
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                            Risultati Verifica Dati
                        </h5>
                        <button type="button" wire:click="chiudiModalVerifica" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">📊 Informazioni Sessione</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Sessione ID:</strong> {{ $risultatiVerifica['sessione_id'] }}</li>
                                    <li><strong>Sede:</strong> {{ $risultatiVerifica['sede_nome'] }} (ID: {{ $risultatiVerifica['sede_id'] }})</li>
                                    <li><strong>Categorie:</strong> {{ implode(', ', $risultatiVerifica['categorie_permesse']) }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">📈 Statistiche Articoli</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Articoli per sede:</strong> {{ $risultatiVerifica['articoli_per_sede'] }}</li>
                                    <li><strong>Totale con filtri:</strong> {{ $risultatiVerifica['totale_con_filtri'] }}</li>
                                    <li><strong>Totale DB:</strong> {{ $risultatiVerifica['totale_articoli_db'] }}</li>
                                    <li><strong>Giacenze totali:</strong> {{ $risultatiVerifica['totale_giacenze'] }}</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-info">🔍 Scansioni Effettuate</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Scansioni totali:</strong> {{ $risultatiVerifica['scansioni_totali'] }}</li>
                                    <li><strong>Articoli distinti scansionati:</strong> {{ $risultatiVerifica['scansioni_distinct'] }}</li>
                                </ul>
                            </div>
                        </div>
                        
                        @if(!empty($risultatiVerifica['articoli_per_categoria']))
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-warning">📋 Articoli per Categoria</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Categoria</th>
                                                <th>Articoli</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($risultatiVerifica['articoli_per_categoria'] as $categoria => $count)
                                                <tr>
                                                    <td>{{ $categoria }}</td>
                                                    <td><span class="badge bg-primary">{{ $count }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModalVerifica" class="btn btn-secondary">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Risultati Confronto -->
    @if($showModalConfronto && !empty($risultatiConfronto))
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:scale-bold-duotone"></iconify-icon>
                            Risultati Confronto Articoli
                        </h5>
                        <button type="button" wire:click="chiudiModalConfronto" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">📊 Informazioni Sessione</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Sessione ID:</strong> {{ $risultatiConfronto['sessione_id'] }}</li>
                                    <li><strong>Sede:</strong> {{ $risultatiConfronto['sede_nome'] }} (ID: {{ $risultatiConfronto['sede_id'] }})</li>
                                    <li><strong>Categorie permesse:</strong> {{ implode(', ', $risultatiConfronto['categorie_permesse_sessione']) }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">📈 Confronto Conteggi</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Articoli totali sistema:</strong> {{ $risultatiConfronto['articoli_pagina_totale'] }}</li>
                                    <li><strong>Articoli per sede:</strong> {{ $risultatiConfronto['articoli_sede'] }}</li>
                                    <li><strong>Categorie 1-9:</strong> {{ $risultatiConfronto['articoli_categorie_1_9'] }}</li>
                                    <li><strong>Tutte le categorie:</strong> {{ $risultatiConfronto['articoli_tutte_categorie'] }}</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-info">⚖️ Analisi Differenze</h6>
                                @php
                                    $diffSistema = $risultatiConfronto['articoli_pagina_totale'] - $risultatiConfronto['articoli_sede'];
                                    $diffCategorie = $risultatiConfronto['articoli_tutte_categorie'] - $risultatiConfronto['articoli_categorie_1_9'];
                                @endphp
                                <div class="alert alert-info">
                                    <strong>Differenza Sistema vs Sede:</strong> 
                                    @if($diffSistema > 0)
                                        <span class="text-warning">+{{ $diffSistema }} articoli in altre sedi</span>
                                    @elseif($diffSistema < 0)
                                        <span class="text-danger">{{ $diffSistema }} articoli mancanti</span>
                                    @else
                                        <span class="text-success">✅ Nessuna differenza</span>
                                    @endif
                                </div>
                                <div class="alert alert-info">
                                    <strong>Differenza Categorie:</strong> 
                                    @if($diffCategorie > 0)
                                        <span class="text-warning">+{{ $diffCategorie }} articoli in altre categorie</span>
                                    @elseif($diffCategorie < 0)
                                        <span class="text-danger">{{ $diffCategorie }} articoli mancanti</span>
                                    @else
                                        <span class="text-success">✅ Nessuna differenza</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="chiudiModalConfronto" class="btn btn-secondary">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
