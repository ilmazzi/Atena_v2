@extends('layouts.vertical', ['title' => 'Articoli Magazzino'])

@section('content')
<div class="container-xxl">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">
                    <i class="ri-box-3-line me-2"></i> Articoli Magazzino
                </h4>
                <p class="text-muted">Elenco completo articoli con giacenze</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1">{{ number_format($stats['totali']) }}</h4>
                    <p class="text-muted mb-0">Articoli Totali</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1">{{ number_format($stats['disponibili']) }}</h4>
                    <p class="text-muted mb-0">Disponibili</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1">€ {{ number_format($stats['valore_totale'], 2, ',', '.') }}</h4>
                    <p class="text-muted mb-0">Valore Totale</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Codice</th>
                                    <th>Descrizione</th>
                                    <th>Categoria</th>
                                    <th>Sede</th>
                                    <th>Giacenza</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($articoli as $articolo)
                                <tr>
                                    <td><strong>{{ $articolo->codice }}</strong></td>
                                    <td>{{ $articolo->descrizione }}</td>
                                    <td>{{ $articolo->categoria->nome ?? '-' }}</td>
                                    <td>{{ $articolo->sede->nome ?? '-' }}</td>
                                    <td>{{ $articolo->giacenza->quantita_residua ?? 0 }}</td>
                                    <td>
                                        <span class="badge bg-{{ $articolo->stato == 'disponibile' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($articolo->stato) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $articoli->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

