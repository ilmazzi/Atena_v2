<div>
    <!-- Messaggi Flash -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:check-circle-bold" class="me-2"></iconify-icon>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:close-circle-bold" class="me-2"></iconify-icon>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistiche Compatte e Professionali -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Articoli Totali</h6>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['totali']) }}</h4>
                            <small class="text-muted">Tutte le sedi</small>
                        </div>
                        <div class="bg-primary-subtle rounded p-2">
                            <iconify-icon icon="solar:box-bold" class="fs-4 text-primary"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Con Giacenza</h6>
                            <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['con_giacenza']) }}</h4>
                            <small class="text-muted">{{ number_format(($stats['con_giacenza'] / max($stats['totali'], 1)) * 100, 1) }}% del totale</small>
                        </div>
                        <div class="bg-success-subtle rounded p-2">
                            <iconify-icon icon="solar:check-circle-bold" class="fs-4 text-success"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Giacenza Zero</h6>
                            <h4 class="mb-0 fw-bold text-warning">{{ number_format($stats['giacenza_zero']) }}</h4>
                            <small class="text-muted">{{ number_format(($stats['giacenza_zero'] / max($stats['totali'], 1)) * 100, 1) }}% del totale</small>
                        </div>
                        <div class="bg-warning-subtle rounded p-2">
                            <iconify-icon icon="solar:box-minimalistic-bold" class="fs-4 text-warning"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Giacenza Negativa</h6>
                            <h4 class="mb-0 fw-bold text-danger">{{ number_format($stats['giacenza_negativa']) }}</h4>
                            <small class="text-muted">{{ number_format(($stats['giacenza_negativa'] / max($stats['totali'], 1)) * 100, 1) }}% del totale</small>
                        </div>
                        <div class="bg-danger-subtle rounded p-2">
                            <iconify-icon icon="solar:danger-triangle-bold" class="fs-4 text-danger"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seconda riga di statistiche -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Senza Giacenze</h6>
                            <h4 class="mb-0 fw-bold text-secondary">{{ number_format($stats['senza_giacenze']) }}</h4>
                            <small class="text-muted">{{ number_format(($stats['senza_giacenze'] / max($stats['totali'], 1)) * 100, 1) }}% del totale</small>
                        </div>
                        <div class="bg-secondary-subtle rounded p-2">
                            <iconify-icon icon="solar:box-remove-bold-duotone" class="fs-4 text-secondary"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">In Vetrina</h6>
                            <h4 class="mb-0 fw-bold text-info">{{ number_format($stats['in_vetrina']) }}</h4>
                            <small class="text-muted">{{ number_format(($stats['in_vetrina'] / max($stats['totali'], 1)) * 100, 1) }}% del totale</small>
                        </div>
                        <div class="bg-info-subtle rounded p-2">
                            <iconify-icon icon="solar:shop-bold" class="fs-4 text-info"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-12">
            <div class="card border-0  shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-muted small">Valore Totale</h6>
                            <h4 class="mb-0 fw-bold text-success">€ {{ number_format($stats['valore_totale'], 2) }}</h4>
                            <small class="text-muted">Valore giacenze con quantità > 0</small>
                        </div>
                        <div class="bg-success-subtle rounded p-2">
                            <iconify-icon icon="solar:euro-bold" class="fs-4 text-success"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Badge Filtri Attivi -->
    @php
        $filtriAttivi = [];
        if($search) $filtriAttivi[] = ['label' => 'Ricerca', 'value' => $search, 'field' => 'search'];
        if(!empty($magazziniSelezionati)) {
            $magazziniSelezionatiNomi = collect($magazzini)->whereIn('id', $magazziniSelezionati)->pluck('nome')->toArray();
            $filtriAttivi[] = ['label' => 'Magazzino', 'value' => implode(', ', $magazziniSelezionatiNomi), 'field' => 'magazziniSelezionati'];
        } elseif($magazzinoFilter) {
            $mag = collect($magazzini)->firstWhere('id', $magazzinoFilter);
            $filtriAttivi[] = ['label' => 'Magazzino', 'value' => $mag ? $mag->nome : $magazzinoFilter, 'field' => 'magazzinoFilter'];
        }
        if($fornitoreFilter) {
            $forn = collect($fornitori)->firstWhere('id', $fornitoreFilter);
            $filtriAttivi[] = ['label' => 'Fornitore', 'value' => $forn ? $forn->ragione_sociale : $fornitoreFilter, 'field' => 'fornitoreFilter'];
        }
        if($giacenzaFilter) $filtriAttivi[] = ['label' => 'Giacenza', 'value' => ucfirst($giacenzaFilter), 'field' => 'giacenzaFilter'];
        if($giacenza) {
            $giacenzaLabels = ['positiva' => 'Con Giacenza', 'zero' => 'Giacenza Zero', 'negativa' => 'Giacenza Negativa', 'nessuna' => 'Senza Giacenze'];
            $filtriAttivi[] = ['label' => 'Filtro Giacenza', 'value' => $giacenzaLabels[$giacenza] ?? ucfirst($giacenza), 'field' => 'giacenza'];
        }
        if($statoArticoloFilter) $filtriAttivi[] = ['label' => 'Stato Articolo', 'value' => ucfirst($statoArticoloFilter), 'field' => 'statoArticoloFilter'];
        if($marcaFilter) $filtriAttivi[] = ['label' => 'Marca', 'value' => $marcaFilter, 'field' => 'marcaFilter'];
        if($ubicazioneFilter) {
            $sede = collect($sedi)->firstWhere('id', $ubicazioneFilter);
            $filtriAttivi[] = ['label' => 'Sede', 'value' => $sede ? $sede->nome : $ubicazioneFilter, 'field' => 'ubicazioneFilter'];
        }
        if($statoFilter) $filtriAttivi[] = ['label' => 'Stato', 'value' => ucfirst($statoFilter), 'field' => 'statoFilter'];
    @endphp

    @if(count($filtriAttivi) > 0)
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small fw-semibold">
                    <iconify-icon icon="solar:filter-bold" class="me-1"></iconify-icon>
                    Filtri attivi ({{ count($filtriAttivi) }}):
                </span>
                @foreach($filtriAttivi as $filtro)
                    <span class="badge bg-primary-subtle text-primary d-inline-flex align-items-center gap-2 px-3 py-2">
                        <span><strong>{{ $filtro['label'] }}:</strong> {{ Str::limit($filtro['value'], 30) }}</span>
                        <button type="button" 
                                class="btn-close btn-close-sm" 
                                style="font-size: 0.6rem;"
                                @if($filtro['field'] === 'magazziniSelezionati')
                                    wire:click="deselezionaTuttiMagazzini"
                                @else
                                    wire:click="$set('{{ $filtro['field'] }}', '')"
                                @endif
                                aria-label="Rimuovi filtro"></button>
                    </span>
                @endforeach
                <button class="btn btn-sm btn-danger" wire:click="resetFilters">
                    <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="me-1"></iconify-icon>
                    Rimuovi tutti
                </button>
            </div>
        </div>
    @endif

    <!-- Filtri Compatti -->
    <div class="card border-0  shadow-sm mb-4">
        <div class="card-header  border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="solar:filter-bold" class="me-2"></iconify-icon>
                    Filtri e Ricerca
                    @if(count($filtriAttivi) > 0)
                        <span class="badge bg-primary ms-2">{{ count($filtriAttivi) }}</span>
                    @endif
                </h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-info" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#advancedFilters"
                            aria-expanded="true"
                            aria-controls="advancedFilters"
                            id="toggleAdvancedBtn">
                        <iconify-icon icon="solar:slider-vertical-bold" class="me-1"></iconify-icon>
                        Avanzati
                    </button>
                    <button class="btn btn-sm btn-secondary" wire:click="resetFilters">
                        <iconify-icon icon="solar:refresh-bold" class="me-1"></iconify-icon>
                        Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body pt-2">
            <div class="row g-3">
                <!-- Ricerca Globale -->
                <div class="col-lg-4">
                    <label class="form-label small fw-semibold">Ricerca</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <iconify-icon icon="solar:magnifer-bold"></iconify-icon>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               placeholder="Codice, descrizione, fornitore..." 
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                <!-- Filtro Magazzino Multiplo -->
                <div class="col-lg-3">
                    <label class="form-label small fw-semibold">Magazzino</label>
                    <div class="position-relative" id="magazzinoDropdown">
                        <button class="btn btn-secondary btn-sm w-100 text-start d-flex justify-content-between align-items-center" 
                                type="button" 
                                wire:click="toggleMagazzinoDropdown"
                                id="magazzinoToggle">
                            <span>
                                @if(empty($magazziniSelezionati))
                                    Tutti i Magazzini
                                @else
                                    {{ count($magazziniSelezionati) }} Magazzini Selezionati
                                @endif
                            </span>
                            <iconify-icon icon="solar:alt-arrow-down-bold" class="ms-2"></iconify-icon>
                        </button>
                        
                        @if($showMagazzinoDropdown)
                            <div class="position-absolute w-100 bg-white border rounded shadow-lg" 
                                 style="top: 100%; left: 0; z-index: 1050; max-height: 300px; overflow-y: auto;">
                                <div class="p-2">
                                    <div class="d-flex gap-2 mb-2">
                                        <button class="btn btn-sm btn-success flex-fill" wire:click="selezionaTuttiMagazzini">
                                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                            Tutti
                                        </button>
                                        <button class="btn btn-sm btn-danger flex-fill" wire:click="deselezionaTuttiMagazzini">
                                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                                            Nessuno
                                        </button>
                                    </div>
                                    <hr class="my-2">
                                    @foreach($magazzini as $magazzino)
                                        <div class="form-check py-1">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="magazzino_{{ $magazzino->id }}"
                                                   wire:click="toggleMagazzino({{ $magazzino->id }})"
                                                   @if(in_array($magazzino->id, $magazziniSelezionati)) checked @endif>
                                            <label class="form-check-label w-100" for="magazzino_{{ $magazzino->id }}">
                                                {{ $magazzino->id }} - {{ $magazzino->nome }}
                                                @if(isset($magazzino->articoli_count))
                                                    <small class="text-muted">({{ $magazzino->articoli_count }})</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Filtro Fornitore -->
                <div class="col-lg-3">
                    <label class="form-label small fw-semibold">Fornitore</label>
                    <select class="form-select form-select-sm" wire:model.live="fornitoreFilter">
                        <option value="">Tutti</option>
                        @foreach($fornitori as $fornitore)
                            <option value="{{ $fornitore->id }}">{{ $fornitore->ragione_sociale }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Giacenza -->
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold">Giacenza</label>
                    <select class="form-select form-select-sm" wire:model.live="giacenzaFilter">
                        <option value="">Tutti</option>
                        <option value="giacenti">Solo Giacenti</option>
                        <option value="in_produzione">In Produzione</option>
                        <option value="scarichi">Solo Scarichi</option>
                    </select>
                </div>

                <!-- Filtro Stato Articolo -->
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold">Stato Articolo</label>
                    <select class="form-select form-select-sm" wire:model.live="statoArticoloFilter">
                        <option value="">Tutti</option>
                        <option value="disponibile">Disponibili</option>
                        <option value="scaricato">Scaricati</option>
                    </select>
                </div>

                <!-- Per Pagina -->
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold">Per Pagina</label>
                    <select class="form-select form-select-sm" wire:model.live="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>

            <!-- Filtri Avanzati (Aperti per default) -->
            <div class="collapse show mt-3" id="advancedFilters">
                <div class="row g-3">
                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold">Marca</label>
                        <select class="form-select form-select-sm" wire:model.live="marcaFilter">
                            <option value="">Tutte</option>
                            @foreach($marche as $marca)
                                <option value="{{ $marca }}">{{ $marca }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold">Sede</label>
                        <select class="form-select form-select-sm" wire:model.live="ubicazioneFilter">
                            <option value="">Tutte</option>
                            @foreach($sedi as $sede)
                                <option value="{{ $sede->id }}">
                                    {{ $sede->nome }} ({{ $sede->citta }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2" wire:ignore>
                        <label class="form-label small fw-semibold">Data Inizio</label>
                        <input type="text" 
                               class="form-control form-control-sm" 
                               placeholder="gg/mm/aaaa"
                               id="dataDocumentoFrom"
                               data-input>
                    </div>

                    <div class="col-lg-2" wire:ignore>
                        <label class="form-label small fw-semibold">Data Fine</label>
                        <input type="text" 
                               class="form-control form-control-sm" 
                               placeholder="gg/mm/aaaa"
                               id="dataDocumentoTo"
                               data-input>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold">Prezzo Min (€)</label>
                        <input type="number" class="form-control form-control-sm" placeholder="Min" wire:model.live.debounce.500ms="prezzoMin">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold">Prezzo Max (€)</label>
                        <input type="number" class="form-control form-control-sm" placeholder="Max" wire:model.live.debounce.500ms="prezzoMax">
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold">Filtri Speciali</label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="soloVetrina" id="soloVetrina">
                                <label class="form-check-label small" for="soloVetrina">In Vetrina</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella Articoli Professionale -->
    <div class="card border-0  shadow-sm">
        <div class="card-header  border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title mb-0">
                        @if($magazzinoFilter)
                            @php
                                $magazzinoSelezionato = $magazzini->firstWhere('id', $magazzinoFilter);
                                $codiceMagazzino = $magazzinoSelezionato->codice ?? '';
                                $nomeMagazzino = $magazzinoSelezionato->nome ?? 'Magazzino Sconosciuto';
                                $numeroMagazzino = (int) str_replace('MAG', '', $codiceMagazzino);
                            @endphp
                            Articoli - {{ $numeroMagazzino }} - {{ $nomeMagazzino }}
                        @else
                            Articoli Magazzino
                        @endif
                    </h6>
                    <small class="text-muted">
                        {{ $articoli->total() }} articoli • Pagina {{ $articoli->currentPage() }} di {{ $articoli->lastPage() }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Controlli Sorting -->
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-secondary" wire:click="sortBy('codice')" title="Ordina per Codice">
                            <iconify-icon icon="solar:hash-bold" class="me-1"></iconify-icon>
                            Codice
                        </button>
                        <button class="btn btn-secondary" wire:click="sortBy('prezzo_acquisto')" title="Ordina per Prezzo">
                            <iconify-icon icon="solar:dollar-bold" class="me-1"></iconify-icon>
                            Prezzo
                        </button>
                        <button class="btn btn-secondary" wire:click="sortBy('data_carico')" title="Ordina per Data">
                            <iconify-icon icon="solar:calendar-bold" class="me-1"></iconify-icon>
                            Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover table-centered">
                    <thead class="">
                        @php
                            // Mappa icone eleganti per categoria merceologica
                            $iconeCategorie = [
                                1 => ['icon' => 'lucide:alarm-clock', 'color' => 'text-primary', 'title' => 'Sveglie'],           // Sveglie
                                2 => ['icon' => 'lucide:watch', 'color' => 'text-secondary', 'title' => 'Orologi Acciaio'], // Orologi Acciaio
                                3 => ['icon' => 'lucide:watch', 'color' => 'text-warning', 'title' => 'Orologi Oro'],       // Orologi Oro
                                4 => ['icon' => 'lucide:link', 'color' => '', 'title' => 'Cinturini'],           // Cinturini
                                5 => ['icon' => 'lucide:gem', 'color' => 'text-info', 'title' => 'Gioielleria'],         // Gioielleria
                                6 => ['icon' => 'lucide:sparkles', 'color' => '', 'title' => 'Argenteria'],         // Argenteria
                                7 => ['icon' => 'lucide:sparkles', 'color' => 'text-secondary', 'title' => 'Silver'],             // Silver (grigio)
                                8 => ['icon' => 'fluent-emoji-high-contrast:dodo', 'color' => 'text-success', 'title' => 'Dodo'],             // Dodo
                            ];
                            
                            // Determina categoria attuale e se è orologio
                            $categoriaAttuale = null;
                            $isOrologioCategory = false;
                            $iconaCategoria = ['icon' => 'lucide:package', 'color' => 'text-secondary', 'title' => 'Tutti']; // default
                            
                            if($this->magazzinoFilter) {
                                $categoriaAttuale = \App\Models\CategoriaMerceologica::find($this->magazzinoFilter);
                                if($categoriaAttuale) {
                                    $iconaCategoria = $iconeCategorie[$categoriaAttuale->id] ?? $iconaCategoria;
                                    $isOrologioCategory = in_array($categoriaAttuale->id, [1, 2, 3, 4]); // Sveglie, Orologi Acciaio, Oro, Cinturini
                                }
                            } elseif($articoli->count() > 0) {
                                $primoArticolo = $articoli->first();
                                if($primoArticolo->categoriaMerceologica) {
                                    $categoriaAttuale = $primoArticolo->categoriaMerceologica;
                                    $iconaCategoria = $iconeCategorie[$categoriaAttuale->id] ?? $iconaCategoria;
                                    $isOrologioCategory = in_array($categoriaAttuale->id, [1, 2, 3, 4]); // Sveglie, Orologi Acciaio, Oro, Cinturini
                                }
                            }
                        @endphp
                        <tr>
                            <th style="cursor: pointer;" wire:click="sortBy('codice')">
                                <div class="d-flex align-items-center gap-1">
                                    @php
                                        // Header icona: se filtro attivo usa categoria, altrimenti icona generica
                                        if($this->magazzinoFilter) {
                                            $iconaHeader = $iconaCategoria;
                                        } else {
                                            $iconaHeader = ['icon' => 'lucide:package', 'color' => 'text-secondary', 'title' => 'Tutti'];
                                        }
                                    @endphp
                                    <iconify-icon icon="{{ $iconaHeader['icon'] }}" class="{{ $iconaHeader['color'] }}" title="{{ $iconaHeader['title'] }}"></iconify-icon>
                                    Codice
                                    @if($sortField === 'codice')
                                        <iconify-icon icon="solar:{{ $sortDirection === 'asc' ? 'alt-arrow-up' : 'alt-arrow-down' }}-bold" class="text-primary"></iconify-icon>
                                    @endif
                                </div>
                            </th>
                            <th style="cursor: pointer;" wire:click="sortBy('descrizione')">
                                <div class="d-flex align-items-center gap-1">
                                    <iconify-icon icon="solar:text-field-bold" class="text-info"></iconify-icon>
                                    Descrizione
                                    @if($sortField === 'descrizione')
                                        <iconify-icon icon="solar:{{ $sortDirection === 'asc' ? 'alt-arrow-up' : 'alt-arrow-down' }}-bold" class="text-primary"></iconify-icon>
                                    @endif
                                </div>
                            </th>
                            @if(!$isOrologioCategory)
                            <th>
                                <iconify-icon icon="solar:settings-bold" class="text-secondary me-1"></iconify-icon>
                                Specifiche
                            </th>
                            <th>
                                <iconify-icon icon="solar:gem-bold" class="text-warning me-1"></iconify-icon>
                                Caratura
                            </th>
                            @endif
                            <th>
                                <iconify-icon icon="solar:box-bold" class="text-secondary me-1"></iconify-icon>
                                Giacenza
                            </th>
                            <th style="cursor: pointer;" wire:click="sortBy('prezzo_acquisto')">
                                <div class="d-flex align-items-center gap-1">
                                    <iconify-icon icon="solar:dollar-bold" class="text-warning"></iconify-icon>
                                    Costo Unitario
                                    @if($sortField === 'prezzo_acquisto')
                                        <iconify-icon icon="solar:{{ $sortDirection === 'asc' ? 'alt-arrow-up' : 'alt-arrow-down' }}-bold" class="text-primary"></iconify-icon>
                                    @endif
                                </div>
                            </th>
                            <th>
                                <div class="d-flex align-items-center gap-1">
                                    <iconify-icon icon="solar:calculator-bold" class="text-secondary"></iconify-icon>
                                    Valore Totale
                                </div>
                            </th>
                            <th>
                                <iconify-icon icon="solar:file-text-bold" class="text-info me-1"></iconify-icon>
                                Dati Carico
                            </th>
                            <th>
                                <iconify-icon icon="solar:map-point-bold" class="text-danger me-1"></iconify-icon>
                                Ubicazione
                            </th>
                            <th class="text-center">
                                <iconify-icon icon="solar:settings-bold" class="text-secondary me-1"></iconify-icon>
                                Azioni
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articoli as $index => $articolo)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            // Se c'è un filtro attivo, usa l'icona della categoria filtrata
                                            // Altrimenti usa l'icona specifica dell'articolo
                                            if($this->magazzinoFilter) {
                                                $iconaArticolo = $iconaCategoria;
                                            } else {
                                                // Nessun filtro: usa l'icona specifica dell'articolo
                                                $iconaArticolo = $iconeCategorie[$articolo->categoria_merceologica_id] ?? ['icon' => 'lucide:help-circle', 'color' => 'text-secondary', 'title' => 'Sconosciuto'];
                                            }
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-center">
                                            <iconify-icon icon="{{ $iconaArticolo['icon'] }}" class="{{ $iconaArticolo['color'] }} fs-5" title="{{ $iconaArticolo['title'] }}"></iconify-icon>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">{{ $articolo->codice }}</span>
                                            <br><small class="text-muted">#{{ $articoli->firstItem() + $index }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-semibold ">{{ Str::limit($articolo->descrizione, 30) ?? 'N/A' }}</span>
                                        @if($articolo->descrizione_estesa)
                                            <br><small class="text-muted">{{ Str::limit($articolo->descrizione_estesa, 40) }}</small>
                                        @endif
                                        
                                        <!-- Marca e Referenza -->
                                        <br><small class="text-muted">
                                            <iconify-icon icon="solar:star-bold" class="text-warning me-1"></iconify-icon>
                                            @if($articolo->caratteristiche && is_array($articolo->caratteristiche) && isset($articolo->caratteristiche['marca']))
                                                {{ $articolo->caratteristiche['marca'] }}
                                            @else
                                                N/A
                                            @endif
                                            @if($articolo->caratteristiche && is_array($articolo->caratteristiche) && isset($articolo->caratteristiche['referenza']))
                                                <span class="ms-2">
                                                    <iconify-icon icon="solar:settings-bold" class="text-secondary me-1"></iconify-icon>
                                                    {{ $articolo->caratteristiche['referenza'] }}
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </td>
                                @if(!$isOrologioCategory)
                                <td>
                                    <div class="text-center">
                                        <!-- Per gioielli: materiale, colore, peso -->
                                        @if($articolo->materiale)
                                            <div class="mb-1">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <iconify-icon icon="solar:tag-bold" class="text-warning"></iconify-icon>
                                                    <span class="badge bg-warning-subtle text-warning">{{ $articolo->materiale }}</span>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if($articolo->colore)
                                            <div class="mb-1">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <div class="rounded-circle bg-{{ strtolower($articolo->colore) }} border border-light" style="width: 14px; height: 14px;"></div>
                                                    <span class="fw-semibold ">{{ $articolo->colore }}</span>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if($articolo->peso_lordo || $articolo->peso_netto)
                                            <div class="mb-1">
                                                @if($articolo->peso_lordo)
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <iconify-icon icon="solar:weight-bold" class="text-secondary"></iconify-icon>
                                                        <span class="fw-semibold text-secondary">{{ number_format($articolo->peso_lordo, 1) }}g</span>
                                                    </div>
                                                @endif
                                                @if($articolo->peso_netto && $articolo->peso_netto != $articolo->peso_lordo)
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <iconify-icon icon="solar:scale-bold" class="text-primary"></iconify-icon>
                                                        <span class="fw-semibold text-primary">{{ number_format($articolo->peso_netto, 1) }}g</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        @if(!$articolo->materiale && !$articolo->colore && !$articolo->peso_lordo && !$articolo->peso_netto)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                @endif
                                @if(!$isOrologioCategory)
                                <td>
                                    <div class="text-center">
                                        <!-- Per gioielli: titolo e caratura -->
                                        @if($articolo->titolo || $articolo->caratura)
                                            @if($articolo->titolo)
                                                <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                                                    <iconify-icon icon="solar:gem-bold" class="text-warning"></iconify-icon>
                                                    <span class="fw-semibold ">{{ $articolo->titolo }}K</span>
                                                </div>
                                                <small class="text-muted">Titolo</small>
                                            @endif
                                            @if($articolo->caratura)
                                                <br><div class="d-flex align-items-center justify-content-center gap-1 mt-1">
                                                    <iconify-icon icon="solar:star-bold" class="text-info"></iconify-icon>
                                                    <span class="fw-semibold text-info">{{ $articolo->caratura }}ct</span>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                @endif
                                <td>
                                    <div class="text-center">
                                        @if($articolo->giacenza)
                                            @php
                                                $inProdottoFinito = $articolo->isInProdottoFinito();
                                                $prodottoFinito = $inProdottoFinito ? $articolo->prodotto_finito : null;
                                                // Se è in un prodotto finito, mostra la quantità originale, altrimenti la residua
                                                $giacenzaMostrata = $inProdottoFinito ? $articolo->giacenza->quantita : $articolo->giacenza->quantita_residua;
                                                $badgeColor = $inProdottoFinito ? 'warning' : ($articolo->giacenza->quantita_residua > 0 ? 'success' : 'danger');
                                            @endphp
                                            
                                            <!-- Giacenza -->
                                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                                <span class="badge rounded-pill bg-{{ $badgeColor }}" 
                                                      style="cursor: pointer;"
                                                      data-bs-toggle="popover" 
                                                      data-bs-placement="top"
                                                      data-bs-html="true"
                                                      data-bs-trigger="hover"
                                                      data-bs-content="
                                                        <div class='text-start'>
                                                            <div class='mb-1'><strong>Qtà Iniziale:</strong> {{ number_format($articolo->giacenza->quantita, 0, ',', '.') }}</div>
                                                            <div class='mb-1'><strong>Qtà Residua:</strong> {{ number_format($articolo->giacenza->quantita_residua, 0, ',', '.') }}</div>
                                                            @if($inProdottoFinito && $prodottoFinito)
                                                                <div class='text-warning mt-2'><strong>In Prodotto Finito:</strong><br>{{ $prodottoFinito->codice }}</div>
                                                            @endif
                                                            @if($articolo->data_carico)
                                                                <div class='text-muted small mt-2'>Caricato: {{ \Carbon\Carbon::parse($articolo->data_carico)->format('d/m/Y') }}</div>
                                                            @endif
                                                        </div>
                                                      ">
                                                    {{ number_format($giacenzaMostrata, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            
                                            <!-- Stato -->
                                            <div class="mb-1">
                                                @if($inProdottoFinito && $prodottoFinito)
                                                    <a href="{{ route('prodotti-finiti.dettaglio', $prodottoFinito->id) }}" 
                                                       class="badge bg-warning-subtle text-warning text-decoration-none"
                                                       data-bs-toggle="tooltip"
                                                       title="Usato in: {{ $prodottoFinito->codice }} - {{ $prodottoFinito->descrizione }}">
                                                        <iconify-icon icon="solar:package-bold" class="me-1"></iconify-icon>
                                                        In un PF
                                                    </a>
                                                @elseif($articolo->giacenza->quantita_residua > 0)
                                                    <span class="badge bg-success-subtle text-success">Giacente</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Scaricato</span>
                                                @endif
                                            </div>
                                            
                                            <!-- Vetrina -->
                                            @if($articolo->inventariato)
                                                <div class="mb-1">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <iconify-icon icon="solar:eye-bold" class="text-warning"></iconify-icon>
                                                        <span class="badge bg-warning-subtle text-warning">In Vetrina</span>
                                                    </div>
                                                    @if($articolo->articoliVetrina && $articolo->articoliVetrina->first() && $articolo->articoliVetrina->first()->prezzo_vetrina)
                                                        <small class="text-muted">€{{ number_format($articolo->articoliVetrina->first()->prezzo_vetrina, 0) }}</small>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                                <iconify-icon icon="solar:close-circle-bold" class="text-danger fs-5"></iconify-icon>
                                                <span class="badge rounded-pill bg-danger">0</span>
                                            </div>
                                            <span class="badge bg-danger-subtle text-danger">Esaurito</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if($articolo->prezzo_acquisto)
                                            <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                                                <iconify-icon icon="solar:dollar-bold" class="text-warning"></iconify-icon>
                                                <span class="fw-semibold text-warning">€{{ number_format($articolo->prezzo_acquisto, 0, ',', '.') }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if($articolo->prezzo_acquisto && $articolo->giacenza)
                                            <span class="fw-semibold ">€{{ number_format($articolo->prezzo_acquisto * $articolo->giacenza->quantita_residua, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @php
                                            // Preferisci fattura se presente, altrimenti DDT
                                            $fattura = $articolo->fatturaDettaglio->first()?->fattura;
                                            $ddt = $articolo->ddtDettaglio->first()?->ddt;
                                            $documento = $fattura ?? $ddt;
                                            $tipoDocumento = $fattura ? 'FATTURA' : 'DDT';
                                            $badgeColor = $fattura ? 'success' : 'primary';
                                        @endphp
                                        
                                        <!-- Fornitore -->
                                        <div class="mb-1">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:shop-bold" class="text-warning"></iconify-icon>
                                                <small class="fw-semibold">
                                                    {{ Str::limit($documento?->fornitore?->ragione_sociale ?? 'N/A', 15) }}
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Numero Documento -->
                                        <div class="mb-1">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:document-bold" class="text-info"></iconify-icon>
                                                <small class="text-muted">{{ $documento?->numero ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Data e Tipo -->
                                        <div class="mb-1">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:calendar-bold" class="text-primary"></iconify-icon>
                                                <small class="text-muted">
                                                    @if($documento && $documento->data_documento)
                                                        {{ $documento->data_documento->format('d/m/Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </small>
                                                <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} fs-11">{{ $tipoDocumento }}</span>
                                            </div>
                                        </div>
                                        
                                        @if($fattura && $ddt)
                                            <!-- Se ci sono entrambi, mostra anche il DDT -->
                                            <div class="mb-1 mt-2 pt-2 border-top">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <iconify-icon icon="solar:document-bold" class="text-info small"></iconify-icon>
                                                    <small class="text-muted">DDT: {{ $ddt->numero }}</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($articolo->giacenza && $articolo->giacenza->sede)
                                        <div class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:map-point-bold" class="text-danger"></iconify-icon>
                                                <span class="fw-semibold">{{ $articolo->giacenza->sede->nome }}</span>
                                            </div>
                                            <small class="text-muted">{{ $articolo->giacenza->sede->citta }}</small>
                                        </div>
                                    @elseif($articolo->sede)
                                        <div class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <iconify-icon icon="solar:map-point-bold" class="text-danger"></iconify-icon>
                                                <span class="fw-semibold">{{ $articolo->sede->nome }}</span>
                                            </div>
                                            <small class="text-muted">{{ $articolo->sede->citta }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                            <iconify-icon icon="solar:menu-dots-bold" class="text-secondary"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('magazzino.articoli.show', $articolo->id) }}">
                                                    <iconify-icon icon="solar:eye-bold" class="text-primary me-2"></iconify-icon>
                                                    Visualizza
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#">
                                                    <iconify-icon icon="solar:pen-bold" class="text-warning me-2"></iconify-icon>
                                                    Modifica
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" wire:click="apriModalStampa({{ $articolo->id }})">
                                                    <iconify-icon icon="solar:printer-bold" class="text-success me-2"></iconify-icon>
                                                    Stampa Etichetta
                                                </button>
                                            </li>
                                            @if($articolo->stato_articolo === 'disponibile')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger" wire:click="scaricaArticolo({{ $articolo->id }})">
                                                        <iconify-icon icon="solar:box-remove-bold" class="text-danger me-2"></iconify-icon>
                                                        Scarica Articolo
                                                    </button>
                                                </li>
                                            @elseif($articolo->stato_articolo === 'scaricato')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-success" wire:click="ripristinaArticolo({{ $articolo->id }})">
                                                        <iconify-icon icon="solar:box-add-bold" class="text-success me-2"></iconify-icon>
                                                        Ripristina Articolo
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isOrologioCategory ? '6' : '8' }}" class="text-center py-4">
                                    <iconify-icon icon="solar:magnifer-zoom-out-bold" class="fs-48 text-muted mb-3"></iconify-icon>
                                    <h5 class="text-muted">Nessun articolo trovato</h5>
                                    <p class="text-muted">Prova a modificare i filtri di ricerca per trovare gli articoli</p>
                                    <button class="btn btn-primary" wire:click="resetFilters">
                                        <iconify-icon icon="solar:refresh-bold" class="me-1"></iconify-icon>
                                        Reset Filtri
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginazione Corretta Larkon -->
        @if($articoli->hasPages())
            <div class="card-footer border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0 small">
                            <iconify-icon icon="solar:info-circle-bold" class="me-1"></iconify-icon>
                            Mostrando <strong>{{ $articoli->firstItem() }}</strong> - <strong>{{ $articoli->lastItem() }}</strong> 
                            di <strong>{{ number_format($articoli->total()) }}</strong> articoli
                        </p>
                    </div>
                    <nav aria-label="Page navigation example">
                        {{ $articoli->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Scarico Parziale -->
    @if($showModalScarico && $articoloDaScaricare)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:box-remove-bold" class="text-danger me-2"></iconify-icon>
                            Scarico Parziale
                        </h5>
                        <button type="button" class="btn-close" wire:click="chiudiModalScarico"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                                    <strong>Articolo:</strong> {{ $articoloDaScaricare->codice }} - {{ $articoloDaScaricare->descrizione }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Giacenza Disponibile</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <iconify-icon icon="solar:box-bold"></iconify-icon>
                                    </span>
                                    <input type="text" class="form-control" value="{{ $giacenzaDisponibile }}" readonly>
                                    <span class="input-group-text">pezzi</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Quantità da Scaricare</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <iconify-icon icon="solar:minus-bold"></iconify-icon>
                                    </span>
                                    <input type="number" class="form-control" wire:model.live="quantitaDaScaricare" 
                                           min="1" max="{{ $giacenzaDisponibile }}">
                                    <span class="input-group-text">pezzi</span>
                                </div>
                                @error('quantitaDaScaricare')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        @if($quantitaDaScaricare > 0 && $quantitaDaScaricare <= $giacenzaDisponibile)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <iconify-icon icon="solar:danger-triangle-bold" class="me-2"></iconify-icon>
                                        <strong>Giacenza Residua:</strong> {{ $giacenzaDisponibile - $quantitaDaScaricare }} pezzi
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiModalScarico">
                            <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                            Annulla
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="confermaScaricoParziale"
                                @if($quantitaDaScaricare <= 0 || $quantitaDaScaricare > $giacenzaDisponibile) disabled @endif>
                            <iconify-icon icon="solar:box-remove-bold" class="me-1"></iconify-icon>
                            Scarica {{ $quantitaDaScaricare }} pezzi
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Stampa Etichetta -->
    @if($showModalStampa && $articoloDaStampare)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="solar:printer-bold-duotone" class="me-2"></iconify-icon>
                            Stampa Etichetta
                        </h5>
                        <button type="button" wire:click="chiudiModalStampa" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="fw-bold">Articolo: {{ $articoloDaStampare->codice }}</h6>
                            <p class="text-muted mb-0">{{ $articoloDaStampare->descrizione }}</p>
                        </div>

                        <div class="mb-3">
                            <label for="stampanteSelezionata" class="form-label fw-semibold">
                                <iconify-icon icon="solar:printer-bold-duotone" class="me-1"></iconify-icon>
                                Stampante
                            </label>
                            @if(!empty($stampantiDisponibili))
                                <select class="form-select" id="stampanteSelezionata" wire:model.live="stampanteSelezionata">
                                    <option value="">Seleziona una stampante...</option>
                                    @foreach($stampantiDisponibili as $stampante)
                                        <option value="{{ $stampante['id'] }}">
                                            {{ $stampante['nome'] }} ({{ $stampante['modello'] }}) - {{ $stampante['ip_address'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <iconify-icon icon="solar:info-circle-bold" class="me-1"></iconify-icon>
                                    Sono mostrate solo le stampanti compatibili con questo articolo
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <iconify-icon icon="solar:danger-triangle-bold" class="me-2"></iconify-icon>
                                    <strong>Nessuna stampante disponibile</strong><br>
                                    Non ci sono stampanti compatibili con questo articolo o non hai i permessi necessari.
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Formato Prezzo</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model.live="formatoPrezzo" value="euro" id="formatoEuro">
                                    <label class="form-check-label" for="formatoEuro">
                                        Euro (€)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model.live="formatoPrezzo" value="codificato" id="formatoCodificato">
                                    <label class="form-check-label" for="formatoCodificato">
                                        Codificato (es. 345X3P3)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="prezzoEtichetta" class="form-label fw-semibold">
                                Prezzo per Etichetta
                                @if($formatoPrezzo === 'euro')
                                    <small class="text-muted">(formato: 123,45)</small>
                                @else
                                    <small class="text-muted">(formato: 345X3P3)</small>
                                @endif
                            </label>
                            <div class="input-group">
                                @if($formatoPrezzo === 'euro')
                                    <span class="input-group-text">€</span>
                                @endif
                                <input type="text" 
                                       class="form-control" 
                                       id="prezzoEtichetta"
                                       wire:model.live="prezzoEtichetta"
                                       @if($formatoPrezzo === 'euro')
                                           placeholder="123,45"
                                       @else
                                           placeholder="345X3P3"
                                       @endif>
                            </div>
                            @if($formatoPrezzo === 'euro')
                                <div class="form-text">
                                    <iconify-icon icon="solar:info-circle-bold" class="me-1"></iconify-icon>
                                    Usa la virgola come separatore decimale
                                </div>
                            @else
                                <div class="form-text">
                                    <iconify-icon icon="solar:info-circle-bold" class="me-1"></iconify-icon>
                                    Formato libero per codici speciali
                                </div>
                            @endif
                        </div>

                        @if(!empty($prezzoEtichetta))
                            <div class="alert alert-info">
                                <iconify-icon icon="solar:eye-bold" class="me-2"></iconify-icon>
                                <strong>Anteprima:</strong> 
                                @if($formatoPrezzo === 'euro')
                                    €{{ number_format((float)str_replace(',', '.', preg_replace('/[^\d,.]/', '', $prezzoEtichetta)), 2, ',', '.') }}
                                @else
                                    {{ $prezzoEtichetta }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="chiudiModalStampa">
                            <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                            Annulla
                        </button>
                        <button type="button" class="btn btn-success" wire:click="confermaStampaEtichetta"
                                @if(empty($prezzoEtichetta) || empty($stampanteSelezionata) || empty($stampantiDisponibili)) disabled @endif>
                            <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                            Stampa Etichetta
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
    
</div>
