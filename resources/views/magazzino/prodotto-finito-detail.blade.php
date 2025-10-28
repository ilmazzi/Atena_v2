@extends('layouts.vertical')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            @foreach($breadcrumbs as $breadcrumb)
                @if($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        @if($breadcrumb['url'])
                            <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                        @else
                            {{ $breadcrumb['title'] }}
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $pageTitle }}</h1>
                    <p class="text-muted mb-0">Dettaglio completo del prodotto finito</p>
                </div>
                <div>
                    <a href="{{ route('prodotti-finiti.index') }}" class="btn btn-secondary">
                        <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                        Torna all'elenco
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Informazioni Principali -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:box-bold-duotone" class="text-primary me-2"></iconify-icon>
                        Informazioni Prodotto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Codice</label>
                            <p class="mb-0">{{ $prodottoFinito->codice }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipologia</label>
                            <p class="mb-0">
                                <span class="badge bg-primary">{{ ucfirst($prodottoFinito->tipologia) }}</span>
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrizione</label>
                            <p class="mb-0">{{ $prodottoFinito->descrizione }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoria</label>
                            <p class="mb-0">{{ $prodottoFinito->categoria->nome ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Stato</label>
                            <p class="mb-0">
                                @if($prodottoFinito->stato === 'completato')
                                    <span class="badge bg-success">Completato</span>
                                @elseif($prodottoFinito->stato === 'in_lavorazione')
                                    <span class="badge bg-warning">In Lavorazione</span>
                                @elseif($prodottoFinito->stato === 'venduto')
                                    <span class="badge bg-info">Venduto</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($prodottoFinito->stato) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:calculator-bold-duotone" class="text-success me-2"></iconify-icon>
                        Costi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-muted">Materiali</label>
                            <p class="mb-0 fw-bold">€ {{ number_format($prodottoFinito->costo_materiali ?? 0, 2, ',', '.') }}</p>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Lavorazione</label>
                            <p class="mb-0 fw-bold">€ {{ number_format($prodottoFinito->costo_lavorazione ?? 0, 2, ',', '.') }}</p>
                        </div>
                        <div class="col-12">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0 fw-bold">TOTALE</label>
                                <p class="mb-0 fs-5 fw-bold text-primary">€ {{ number_format($prodottoFinito->costo_totale ?? 0, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dati Gioielleria -->
    @if($prodottoFinito->oro_totale || $prodottoFinito->brillanti_totali || $prodottoFinito->pietre_totali)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:diamond-bold-duotone" class="text-warning me-2"></iconify-icon>
                        Caratteristiche Gioielleria
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($prodottoFinito->oro_totale)
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Oro Totale</label>
                            <p class="mb-0">
                                <span class="badge bg-warning text-dark">{{ $prodottoFinito->oro_totale }}</span>
                            </p>
                        </div>
                        @endif
                        @if($prodottoFinito->brillanti_totali)
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Brillanti Totali</label>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $prodottoFinito->brillanti_totali }}</span>
                            </p>
                        </div>
                        @endif
                        @if($prodottoFinito->pietre_totali)
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pietre Totali</label>
                            <p class="mb-0">
                                <span class="badge bg-success">{{ $prodottoFinito->pietre_totali }}</span>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Componenti Utilizzati -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:bag-4-bold-duotone" class="text-primary me-2"></iconify-icon>
                        Componenti Utilizzati ({{ $prodottoFinito->componentiArticoli->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($prodottoFinito->componentiArticoli->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th width="80">Quantità</th>
                                        <th width="120">Costo Unit.</th>
                                        <th width="120">Costo Tot.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prodottoFinito->componentiArticoli as $componente)
                                        <tr>
                                            <td><strong>{{ $componente->articolo->codice }}</strong></td>
                                            <td>{{ $componente->articolo->descrizione }}</td>
                                            <td class="text-center">{{ $componente->quantita }}</td>
                                            <td class="text-end">€ {{ number_format($componente->costo_unitario ?? 0, 2, ',', '.') }}</td>
                                            <td class="text-end">€ {{ number_format($componente->costo_totale ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <iconify-icon icon="solar:box-minimalistic-bold" class="fs-1 mb-2"></iconify-icon>
                            <p>Nessun componente utilizzato</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Articolo Risultante -->
    @if($prodottoFinito->articoloRisultante)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-2"></iconify-icon>
                        Articolo Risultante
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Codice Articolo</label>
                            <p class="mb-0">{{ $prodottoFinito->articoloRisultante->codice }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Giacenza</label>
                            <p class="mb-0">
                                @if($prodottoFinito->articoloRisultante->giacenza)
                                    <span class="badge bg-success">{{ $prodottoFinito->articoloRisultante->giacenza->quantita_residua }} pz</span>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Note -->
    @if($prodottoFinito->note)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:document-text-bold-duotone" class="text-info me-2"></iconify-icon>
                        Note
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $prodottoFinito->note }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection




