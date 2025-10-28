@extends('layouts.vertical')

@section('title', 'Dashboard Sistema')

@section('content')
<style>
.hover-bg-light:hover {
    background-color: rgba(0,0,0,0.05) !important;
    transition: background-color 0.2s ease;
}
</style>
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <iconify-icon icon="solar:home-bold-duotone" class="text-primary me-2"></iconify-icon>
                            Dashboard Sistema
                        </h2>
                        <p class="text-muted mb-0">Panoramica completa del sistema Athena</p>
                    </div>
                    <div>
                        <button onclick="location.reload()" class="btn btn-primary">
                            <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                            Aggiorna
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiche Generali -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-light border-primary border-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <iconify-icon icon="solar:box-bold-duotone" class="fs-1 text-primary"></iconify-icon>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-0 text-primary">{{ number_format(\App\Models\Articolo::count()) }}</h4>
                        <p class="mb-0 text-muted">Articoli Totali</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light border-success border-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <iconify-icon icon="solar:buildings-bold-duotone" class="fs-1 text-success"></iconify-icon>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-0 text-success">{{ number_format(\App\Models\Sede::count()) }}</h4>
                        <p class="mb-0 text-muted">Sedi</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light border-info border-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="fs-1 text-info"></iconify-icon>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-0 text-info">{{ number_format(\App\Models\Fornitore::count()) }}</h4>
                        <p class="mb-0 text-muted">Fornitori</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light border-warning border-2 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <iconify-icon icon="solar:file-text-bold-duotone" class="fs-1 text-warning"></iconify-icon>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-0 text-warning">{{ number_format(\App\Models\Ddt::count()) }}</h4>
                        <p class="mb-0 text-muted">DDT</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Moduli Principali -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-primary border-2">
                <h5 class="mb-0 text-primary">
                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                    Gestione Magazzino
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Gestisci articoli, giacenze e movimentazioni</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('articoli.index') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                        Articoli
                    </a>
                    <a href="{{ route('magazzino.index') }}" class="btn btn-success">
                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                        Magazzino
                    </a>
                    <a href="{{ route('magazzino.scarico') }}" class="btn btn-info">
                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                        Scarico Magazzino
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-success border-2">
                <h5 class="mb-0 text-success">
                    <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                    Sistema Inventario
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Scanner, monitoraggio e gestione inventario</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('inventario.dashboard') }}" class="btn btn-success">
                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                        Dashboard Inventario
                    </a>
                    <a href="{{ route('inventario.scanner') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                        Scanner Inventario
                    </a>
                    <a href="{{ route('inventario.sessioni') }}" class="btn btn-info">
                        <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                        Sessioni Inventario
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Moduli Secondari -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-info border-2">
                <h5 class="mb-0 text-info">
                    <iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon>
                    Fornitori
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Gestisci fornitori e relazioni</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('magazzino.index') }}" class="btn btn-info">
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon>
                        Gestione Magazzino
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-warning border-2">
                <h5 class="mb-0 text-warning">
                    <iconify-icon icon="solar:file-text-bold-duotone"></iconify-icon>
                    Documenti
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">DDT, fatture e documenti</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('documenti-acquisto.index') }}" class="btn btn-warning">
                        <iconify-icon icon="solar:file-text-bold-duotone"></iconify-icon>
                        Documenti Acquisto
                    </a>
                    <a href="{{ route('magazzino.index') }}" class="btn btn-success">
                        <iconify-icon icon="solar:receipt-bold-duotone"></iconify-icon>
                        Magazzino
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-secondary border-2">
                <h5 class="mb-0 text-secondary">
                    <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                    Sistema
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Configurazioni e stampanti</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('stampanti.index') }}" class="btn btn-secondary">
                        <iconify-icon icon="solar:printer-bold-duotone"></iconify-icon>
                        Stampanti
                    </a>
                    <a href="{{ route('magazzino.scarico') }}" class="btn btn-danger">
                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                        Scarico Magazzino
                    </a>
                    <a href="{{ route('prodotti-finiti.index') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                        Prodotti Finiti
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiche Dettagliate -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-primary border-2">
                <h5 class="mb-0 text-primary">
                    <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                    Statistiche Articoli
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <div class="text-center">
                            <a href="{{ route('articoli.index', ['giacenza' => 'positiva']) }}" class="text-decoration-none d-block p-3 rounded hover-bg-light">
                                <h3 class="text-primary">{{ number_format(\App\Models\Articolo::whereHas('giacenze', function($q) { $q->where('quantita_residua', '>', 0); })->count()) }}</h3>
                                <p class="text-muted mb-0">
                                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                                    Con Giacenza
                                </p>
                            </a>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <a href="{{ route('articoli.index', ['giacenza' => 'zero']) }}" class="text-decoration-none d-block p-3 rounded hover-bg-light">
                                <h3 class="text-warning">{{ number_format(\App\Models\Articolo::whereHas('giacenze', function($q) { $q->where('quantita_residua', '=', 0); })->count()) }}</h3>
                                <p class="text-muted mb-0">
                                    <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                                    Giacenza Zero
                                </p>
                            </a>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <a href="{{ route('articoli.index', ['giacenza' => 'negativa']) }}" class="text-decoration-none d-block p-3 rounded hover-bg-light">
                                <h3 class="text-danger">{{ number_format(\App\Models\Articolo::whereHas('giacenze', function($q) { $q->where('quantita_residua', '<', 0); })->count()) }}</h3>
                                <p class="text-muted mb-0">
                                    <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
                                    Giacenza Negativa
                                </p>
                            </a>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center">
                            <a href="{{ route('articoli.index', ['giacenza' => 'nessuna']) }}" class="text-decoration-none d-block p-3 rounded hover-bg-light">
                                <h3 class="text-secondary">{{ number_format(\App\Models\Articolo::whereDoesntHave('giacenze')->count()) }}</h3>
                                <p class="text-muted mb-0">
                                    <iconify-icon icon="solar:box-remove-bold-duotone"></iconify-icon>
                                    Senza Giacenze
                                </p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-success border-2">
                <h5 class="mb-0 text-success">
                    <iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon>
                    Distribuzione per Sede
                </h5>
            </div>
            <div class="card-body">
                @foreach(\App\Models\Sede::withCount('articoli')->get() as $sede)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>{{ $sede->nome }}</span>
                    <span class="badge bg-primary">{{ number_format($sede->articoli_count) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Sezione OCR -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-dark border-2">
                <h5 class="mb-0 text-dark">
                    <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                    Sistema OCR
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Gestione documenti e riconoscimento ottico</p>
                <div class="d-grid gap-2 d-md-flex">
                    <a href="{{ route('ocr.index') }}" class="btn btn-dark">
                        <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                        Gestione OCR
                    </a>
                    <a href="{{ route('ocr.dashboard') }}" class="btn btn-secondary">
                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                        Dashboard OCR
                    </a>
                    <a href="{{ route('ocr.upload') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:upload-bold-duotone"></iconify-icon>
                        Carica Documenti
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
