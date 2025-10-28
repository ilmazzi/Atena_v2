<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDT {{ $ddt->numero }}</title>
    @vite(['resources/scss/app.scss'])
    <style>
        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15mm;
            background: white;
            font-size: 11px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid var(--bs-dark);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--bs-dark);
        }
        .header .info {
            margin-top: 10px;
            color: var(--bs-secondary);
            font-size: 14px;
        }
        .ddt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--bs-light);
            border-radius: 5px;
        }
        .ddt-section {
            flex: 1;
        }
        .ddt-section h6 {
            margin-bottom: 10px;
            color: var(--bs-primary);
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid var(--bs-border-color);
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: var(--bs-light);
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }
        td {
            font-size: 11px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: var(--bs-secondary);
        }
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            cursor: pointer;
            background-color: var(--bs-primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            z-index: 1000;
        }
        .totali {
            margin-top: 20px;
            text-align: right;
        }
        .totali table {
            width: 300px;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        🖨️ Stampa
    </button>

    <div class="header">
        <h1>DOCUMENTO DI TRASPORTO</h1>
        <div class="info">
            <strong>{{ $ddt->numero }}</strong> - {{ $ddt->data_documento->format('d/m/Y') }}
            <br>
            @if($ddt->tipo_documento === 'trasferimento_deposito')
                <span class="badge bg-primary">Trasferimento Conto Deposito</span>
            @elseif($ddt->tipo_documento === 'reso_deposito')
                <span class="badge bg-warning">Reso Conto Deposito</span>
            @else
                <span class="badge bg-secondary">{{ ucfirst($ddt->tipo_documento) }}</span>
            @endif
        </div>
    </div>

    <div class="ddt-info">
        <div class="ddt-section">
            <h6>Mittente</h6>
            <p><strong>{{ $ddt->sede->nome ?? 'Sede Non Specificata' }}</strong></p>
            @if($ddt->sede)
                <p>{{ $ddt->sede->indirizzo ?? '' }}</p>
                <p>{{ $ddt->sede->citta ?? '' }} {{ $ddt->sede->cap ?? '' }}</p>
            @endif
        </div>
        
        <div class="ddt-section">
            <h6>Destinatario</h6>
            @if($ddt->tipo_documento === 'trasferimento_deposito')
                @php
                    $deposito = \App\Models\ContoDeposito::where('ddt_invio_id', $ddt->id)->first();
                @endphp
                @if($deposito)
                    <p><strong>{{ $deposito->sedeDestinataria->nome }}</strong></p>
                    <p>{{ $deposito->sedeDestinataria->indirizzo ?? '' }}</p>
                    <p>{{ $deposito->sedeDestinataria->citta ?? '' }} {{ $deposito->sedeDestinataria->cap ?? '' }}</p>
                @endif
            @elseif($ddt->tipo_documento === 'reso_deposito')
                @php
                    $deposito = \App\Models\ContoDeposito::where('ddt_reso_id', $ddt->id)->first();
                @endphp
                @if($deposito)
                    <p><strong>{{ $deposito->sedeMittente->nome }}</strong></p>
                    <p>{{ $deposito->sedeMittente->indirizzo ?? '' }}</p>
                    <p>{{ $deposito->sedeMittente->citta ?? '' }} {{ $deposito->sedeMittente->cap ?? '' }}</p>
                @endif
            @else
                <p><strong>{{ $ddt->fornitore->ragione_sociale ?? 'N/A' }}</strong></p>
            @endif
        </div>
        
        <div class="ddt-section">
            <h6>Dettagli Documento</h6>
            <p><strong>Numero:</strong> {{ $ddt->numero }}</p>
            <p><strong>Data:</strong> {{ $ddt->data_documento->format('d/m/Y') }}</p>
            <p><strong>Anno:</strong> {{ $ddt->anno }}</p>
            @if($ddt->creatoDa)
                <p><strong>Creato da:</strong> {{ $ddt->creatoDa->name }}</p>
            @endif
        </div>
    </div>

    @if($ddt->note)
        <div class="alert alert-info">
            <strong>Note:</strong> {{ $ddt->note }}
        </div>
    @endif

    @if($ddt->dettagli->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 100px;">Codice</th>
                    <th>Descrizione</th>
                    <th style="width: 100px;">Categoria</th>
                    <th style="width: 80px;">Qtà</th>
                    <th style="width: 100px;">Prezzo Unit.</th>
                    <th style="width: 100px;">Totale</th>
                </tr>
            </thead>
            <tbody>
                @php $totaleGenerale = 0; @endphp
                @foreach($ddt->dettagli as $dettaglio)
                    @php 
                        $item = $dettaglio->articolo ?? $dettaglio->prodottoFinito;
                        $totaleRiga = $dettaglio->quantita * $dettaglio->prezzo_unitario;
                        $totaleGenerale += $totaleRiga;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: var(--bs-primary);">
                            {{ $item->codice ?? 'N/A' }}
                        </td>
                        <td>
                            <div style="font-weight: bold; margin-bottom: 2px;">
                                {{ $dettaglio->descrizione ?? $item->descrizione ?? 'N/A' }}
                            </div>
                            @if($dettaglio->articolo)
                                <div style="font-size: 9px; color: var(--bs-secondary);">
                                    Articolo
                                </div>
                            @else
                                <div style="font-size: 9px; color: var(--bs-secondary);">
                                    Prodotto Finito
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge bg-light-info text-info">
                                {{ $item->categoriaMerceologica->nome ?? 'N/A' }}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: bold;">
                            {{ $dettaglio->quantita }}
                        </td>
                        <td style="text-align: right;">
                            €{{ number_format($dettaglio->prezzo_unitario, 2, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: bold;">
                            €{{ number_format($totaleRiga, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totali">
            <table>
                <tr>
                    <td style="text-align: right; font-weight: bold; font-size: 14px; background-color: var(--bs-light);">
                        <strong>TOTALE GENERALE:</strong>
                    </td>
                    <td style="text-align: right; font-weight: bold; font-size: 14px; color: var(--bs-success);">
                        <strong>€{{ number_format($totaleGenerale, 2, ',', '.') }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 50px; color: var(--bs-secondary);">
            <h3>Nessun articolo nel DDT</h3>
        </div>
    @endif

    <div class="footer">
        Athena v2 - Sistema di Gestione Magazzino<br>
        DDT {{ $ddt->numero }} - {{ $ddt->dettagli->count() }} articoli - 
        Totale: €{{ number_format($ddt->dettagli->sum(function($d) { return $d->quantita * $d->prezzo_unitario; }), 2, ',', '.') }}
        <br><br>
        <div style="margin-top: 40px; border-top: 1px solid #ccc; padding-top: 20px;">
            <div style="display: flex; justify-content: space-between;">
                <div style="text-align: center; width: 30%;">
                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 50px;"></div>
                    <small>Firma Mittente</small>
                </div>
                <div style="text-align: center; width: 30%;">
                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 50px;"></div>
                    <small>Firma Trasportatore</small>
                </div>
                <div style="text-align: center; width: 30%;">
                    <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 50px;"></div>
                    <small>Firma Destinatario</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

