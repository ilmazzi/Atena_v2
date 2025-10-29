@extends('layouts.vertical', ['title' => 'Dettaglio Fattura Vendita'])

@section('title', 'Dettagli Fattura Vendita - ' . $fatturaVendita->numero)

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <iconify-icon icon="solar:document-text-bold-duotone" class="me-2"></iconify-icon>
                        Fattura Vendita {{ $fatturaVendita->numero }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('conti-deposito.index') }}">Conti Deposito</a></li>
                            @if($fatturaVendita->contoDeposito)
                                <li class="breadcrumb-item"><a href="{{ route('conti-deposito.gestisci', $fatturaVendita->contoDeposito->id) }}">{{ $fatturaVendita->contoDeposito->codice }}</a></li>
                            @endif
                            <li class="breadcrumb-item active">Fattura {{ $fatturaVendita->numero }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('fatture-vendita.stampa', $fatturaVendita->id) }}" 
                       class="btn btn-primary" 
                       target="_blank">
                        <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                        Stampa Fattura
                    </a>
                    @if($fatturaVendita->contoDeposito)
                        <a href="{{ route('conti-deposito.gestisci', $fatturaVendita->contoDeposito->id) }}" 
                           class="btn btn-outline-secondary">
                            <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                            Torna al Deposito
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Informazioni Fattura --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
                        Informazioni Fattura
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5"><strong>Numero:</strong></div>
                        <div class="col-7">{{ $fatturaVendita->numero }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Data Documento:</strong></div>
                        <div class="col-7">{{ $fatturaVendita->data_documento->format('d/m/Y') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Anno:</strong></div>
                        <div class="col-7">{{ $fatturaVendita->anno }}</div>
                    </div>
                    @if($fatturaVendita->sede)
                        <div class="row mb-2">
                            <div class="col-5"><strong>Sede:</strong></div>
                            <div class="col-7">{{ $fatturaVendita->sede->nome }}</div>
                        </div>
                    @endif
                    @if($fatturaVendita->contoDeposito)
                        <div class="row mb-2">
                            <div class="col-5"><strong>Conto Deposito:</strong></div>
                            <div class="col-7">
                                <a href="{{ route('conti-deposito.gestisci', $fatturaVendita->contoDeposito->id) }}">
                                    {{ $fatturaVendita->contoDeposito->codice }}
                                </a>
                            </div>
                        </div>
                    @endif
                    @if($fatturaVendita->ddtInvio)
                        <div class="row mb-2">
                            <div class="col-5"><strong>DDT Invio:</strong></div>
                            <div class="col-7">
                                <a href="{{ route('ddt-deposito.show', $fatturaVendita->ddtInvio->id) }}">
                                    {{ $fatturaVendita->ddtInvio->numero }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="solar:user-bold" class="me-2"></iconify-icon>
                        Informazioni Cliente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5"><strong>Nome:</strong></div>
                        <div class="col-7">{{ $fatturaVendita->cliente_nome }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Cognome:</strong></div>
                        <div class="col-7">{{ $fatturaVendita->cliente_cognome }}</div>
                    </div>
                    @if($fatturaVendita->cliente_telefono)
                        <div class="row mb-2">
                            <div class="col-5"><strong>Telefono:</strong></div>
                            <div class="col-7">{{ $fatturaVendita->cliente_telefono }}</div>
                        </div>
                    @endif
                    @if($fatturaVendita->cliente_email)
                        <div class="row mb-2">
                            <div class="col-5"><strong>Email:</strong></div>
                            <div class="col-7">{{ $fatturaVendita->cliente_email }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Importi --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="solar:wallet-bold" class="me-2"></iconify-icon>
                        Importi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <small class="text-muted">Imponibile:</small>
                                <div class="h5 mb-0">€{{ number_format($fatturaVendita->imponibile, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <small class="text-muted">IVA:</small>
                                <div class="h5 mb-0">€{{ number_format($fatturaVendita->iva, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <small class="text-muted">Totale:</small>
                                <div class="h4 mb-0 text-success">€{{ number_format($fatturaVendita->totale, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <small class="text-muted">Articoli:</small>
                                <div class="h5 mb-0">{{ $fatturaVendita->numero_articoli }} ({{ $fatturaVendita->quantita_totale }} pz)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Articoli Venduti --}}
    @php
        $movimenti = $fatturaVendita->movimenti;
    @endphp
    @if($movimenti && $movimenti->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="solar:box-bold" class="me-2"></iconify-icon>
                            Articoli Venduti
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Codice</th>
                                        <th>Descrizione</th>
                                        <th class="text-center">Quantità</th>
                                        <th class="text-end">Costo Unitario</th>
                                        <th class="text-end">Totale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movimenti as $movimento)
                                        <tr>
                                            <td>
                                                @if($movimento->tipo_item === 'articolo')
                                                    <span class="badge bg-light-primary text-primary">Articolo</span>
                                                @else
                                                    <span class="badge bg-light-info text-info">Prodotto Finito</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($movimento->articolo)
                                                    <strong>{{ $movimento->articolo->codice }}</strong>
                                                @elseif($movimento->prodottoFinito)
                                                    <strong>{{ $movimento->prodottoFinito->codice }}</strong>
                                                @endif
                                            </td>
                                            <td>
                                                @if($movimento->articolo)
                                                    {{ Str::limit($movimento->articolo->descrizione, 50) }}
                                                @elseif($movimento->prodottoFinito)
                                                    {{ Str::limit($movimento->prodottoFinito->descrizione, 50) }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $movimento->quantita }}</td>
                                            <td class="text-end">€{{ number_format($movimento->costo_unitario, 2, ',', '.') }}</td>
                                            <td class="text-end"><strong>€{{ number_format($movimento->costo_totale, 2, ',', '.') }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">TOTALE</th>
                                        <th class="text-center">{{ $movimenti->sum('quantita') }}</th>
                                        <th></th>
                                        <th class="text-end"><strong>€{{ number_format($movimenti->sum('costo_totale'), 2, ',', '.') }}</strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Note --}}
    @if($fatturaVendita->note)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="solar:notes-bold" class="me-2"></iconify-icon>
                            Note
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $fatturaVendita->note }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

