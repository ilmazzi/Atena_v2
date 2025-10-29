<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fattura Vendita {{ $fatturaVendita->numero }}</title>
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
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            font-weight: bold;
        }
        .header .info {
            margin-top: 10px;
            color: var(--bs-secondary);
            font-size: 14px;
        }
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--bs-light);
            border-radius: 5px;
            gap: 20px;
        }
        .info-section {
            flex: 1;
        }
        .info-title {
            margin-bottom: 10px;
            color: var(--bs-primary);
            font-weight: bold;
            font-size: 14px;
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 6px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 90px;
            color: var(--bs-secondary);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background: var(--bs-dark);
            color: white;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            font-weight: bold;
            font-size: 10px;
        }
        td {
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            padding: 8px;
            font-weight: bold;
        }
        .total-final {
            font-size: 16px;
            color: var(--bs-success);
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        🖨️ Stampa
    </button>

    <div class="header">
        <h1>FATTURA DI VENDITA</h1>
        <div class="info">
            Numero: <strong>{{ $fatturaVendita->numero }}</strong> | 
            Anno: {{ $fatturaVendita->anno }} | 
            Data: {{ $fatturaVendita->data_documento->format('d/m/Y') }}
        </div>
    </div>

    <div class="document-info">
        <div class="info-section">
            <div class="info-title">VENDITORE</div>
            @if($fatturaVendita->sede)
                <div class="info-row">
                    <span class="info-label">Sede:</span>
                    {{ $fatturaVendita->sede->nome }}
                </div>
                @if($fatturaVendita->sede->indirizzo)
                    <div class="info-row">
                        <span class="info-label">Indirizzo:</span>
                        {{ $fatturaVendita->sede->indirizzo }}
                    </div>
                @endif
            @endif
            @if($fatturaVendita->contoDeposito)
                <div class="info-row">
                    <span class="info-label">Conto Deposito:</span>
                    {{ $fatturaVendita->contoDeposito->codice }}
                </div>
            @endif
            @if($fatturaVendita->ddtInvio)
                <div class="info-row">
                    <span class="info-label">DDT Invio:</span>
                    {{ $fatturaVendita->ddtInvio->numero }}
                </div>
            @endif
        </div>

        <div class="info-section">
            <div class="info-title">CLIENTE</div>
            <div class="info-row">
                <span class="info-label">Nome:</span>
                {{ $fatturaVendita->cliente_nome }}
            </div>
            <div class="info-row">
                <span class="info-label">Cognome:</span>
                {{ $fatturaVendita->cliente_cognome }}
            </div>
            @if($fatturaVendita->cliente_telefono)
                <div class="info-row">
                    <span class="info-label">Telefono:</span>
                    {{ $fatturaVendita->cliente_telefono }}
                </div>
            @endif
            @if($fatturaVendita->cliente_email)
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    {{ $fatturaVendita->cliente_email }}
                </div>
            @endif
        </div>
    </div>

    @php
        $movimenti = $fatturaVendita->movimenti;
    @endphp
    @if($movimenti && $movimenti->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Codice</th>
                    <th>Descrizione</th>
                    <th class="text-center">Q.tà</th>
                    <th class="text-right">Prezzo Unit.</th>
                    <th class="text-right">Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimenti as $movimento)
                    <tr>
                        <td>
                            @if($movimento->tipo_item === 'articolo')
                                Articolo
                            @else
                                Prodotto Finito
                            @endif
                        </td>
                        <td>
                            @if($movimento->articolo)
                                {{ $movimento->articolo->codice }}
                            @elseif($movimento->prodottoFinito)
                                {{ $movimento->prodottoFinito->codice }}
                            @endif
                        </td>
                        <td>
                            @if($movimento->articolo)
                                {{ $movimento->articolo->descrizione }}
                            @elseif($movimento->prodottoFinito)
                                {{ $movimento->prodottoFinito->descrizione }}
                            @endif
                        </td>
                        <td class="text-center">{{ $movimento->quantita }}</td>
                        <td class="text-right">€{{ number_format($movimento->costo_unitario, 2, ',', '.') }}</td>
                        <td class="text-right"><strong>€{{ number_format($movimento->costo_totale, 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                Imponibile: €{{ number_format($fatturaVendita->imponibile, 2, ',', '.') }}
            </div>
            @if($fatturaVendita->iva > 0)
                <div class="total-row">
                    IVA: €{{ number_format($fatturaVendita->iva, 2, ',', '.') }}
                </div>
            @endif
            <div class="total-row total-final">
                TOTALE: €{{ number_format($fatturaVendita->totale, 2, ',', '.') }}
            </div>
        </div>
    @endif

    @if($fatturaVendita->note)
        <div class="footer">
            <strong>Note:</strong> {{ $fatturaVendita->note }}
        </div>
    @endif

    <div class="footer">
        <p>Documento generato il {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>

