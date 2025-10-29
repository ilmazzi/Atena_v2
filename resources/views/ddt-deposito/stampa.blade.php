<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDT Deposito {{ $ddtDeposito->numero }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .info-section {
            flex: 1;
            margin-right: 20px;
        }
        
        .info-section:last-child {
            margin-right: 0;
        }
        
        .info-title {
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .deposito-badge {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tipo-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .tipo-invio { background: #d4edda; color: #155724; }
        .tipo-reso { background: #fff3cd; color: #856404; }
        .tipo-rimando { background: #d1ecf1; color: #0c5460; }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-primary { color: #007bff; }
        .text-success { color: #28a745; }
        .text-warning { color: #ffc107; }
        
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .footer-section {
            display: inline-block;
            width: 48%;
            vertical-align: top;
        }
        
        .signature-box {
            border: 1px solid #ddd;
            height: 80px;
            margin-top: 10px;
            padding: 10px;
        }
        
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 10px;
        }
        
        @media print {
            body { 
                background: white; 
                -webkit-print-color-adjust: exact;
            }
            
            .no-print { 
                display: none; 
            }
            
            .page-break { 
                page-break-after: always; 
            }
        }
        
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .status-indicator {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-creato { background: #e9ecef; color: #495057; }
        .status-stampato { background: #d1ecf1; color: #0c5460; }
        .status-in-transito { background: #fff3cd; color: #856404; }
        .status-ricevuto { background: #cce5f4; color: #004085; }
        .status-confermato { background: #d4edda; color: #155724; }
        .status-chiuso { background: #343a40; color: white; }
    </style>
</head>
<body>
    {{-- Azioni stampa (nascoste in stampa) --}}
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Stampa
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            ✖️ Chiudi
        </button>
    </div>

    {{-- Header --}}
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ config('app.name', 'ATHENA v2') }}</div>
            <div>Sistema Gestione Conti Deposito</div>
        </div>
        
        <div class="document-title">
            📄 Documento di Trasporto - Deposito
        </div>
    </div>

    {{-- Informazioni documento --}}
    <div class="document-info">
        <div class="info-section">
            <div class="info-title">📋 Informazioni DDT</div>
            <div class="info-row">
                <span class="info-label">Numero:</span>
                <strong class="text-primary">{{ $ddtDeposito->numero }}</strong>
            </div>
            <div class="info-row">
                <span class="info-label">Data:</span>
                {{ $ddtDeposito->data_documento->format('d/m/Y') }}
            </div>
            <div class="info-row">
                <span class="info-label">Tipo:</span>
                <span class="tipo-badge tipo-{{ $ddtDeposito->tipo }}">
                    {{ $ddtDeposito->tipo_label }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Stato:</span>
                <span class="status-indicator status-{{ str_replace(['_', ' '], '-', strtolower($ddtDeposito->stato)) }}">
                    {{ $ddtDeposito->stato_label }}
                </span>
            </div>
            @if($ddtDeposito->data_stampa)
                <div class="info-row">
                    <span class="info-label">Stampato:</span>
                    {{ $ddtDeposito->data_stampa->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>

        <div class="info-section">
            <div class="info-title">📦 Conto Deposito</div>
            <div class="info-row">
                <span class="info-label">Codice:</span>
                <strong>{{ $ddtDeposito->contoDeposito->codice }}</strong>
            </div>
            <div class="info-row">
                <span class="info-label">Tipo:</span>
                <span class="deposito-badge">Conto Deposito</span>
            </div>
            <div class="info-row">
                <span class="info-label">Data Invio:</span>
                {{ $ddtDeposito->contoDeposito->data_invio->format('d/m/Y') }}
            </div>
            <div class="info-row">
                <span class="info-label">Scadenza:</span>
                {{ $ddtDeposito->contoDeposito->data_scadenza->format('d/m/Y') }}
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">🏢 Trasporto</div>
            <div class="info-row">
                <span class="info-label">Da:</span>
                <strong>{{ $ddtDeposito->sedeMittente->nome }}</strong>
            </div>
            <div class="info-row">
                <span class="info-label">A:</span>
                <strong>{{ $ddtDeposito->sedeDestinataria->nome }}</strong>
            </div>
            <div class="info-row">
                <span class="info-label">Causale:</span>
                {{ $ddtDeposito->causale }}
            </div>
            @if($ddtDeposito->numero_colli)
                <div class="info-row">
                    <span class="info-label">Colli:</span>
                    {{ $ddtDeposito->numero_colli }}
                </div>
            @endif
            @if($ddtDeposito->corriere)
                <div class="info-row">
                    <span class="info-label">Corriere:</span>
                    {{ $ddtDeposito->corriere }}
                </div>
            @endif
        </div>
    </div>

    {{-- Dettagli articoli --}}
    <div>
        <h3 style="color: #007bff; margin-bottom: 15px;">
            📋 Dettagli Articoli ({{ $ddtDeposito->articoli_totali }} item)
        </h3>
        
        <table class="table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="10%">Tipo</th>
                    <th width="15%">Codice</th>
                    <th width="35%">Descrizione</th>
                    <th width="8%" class="text-center">Q.tà</th>
                    <th width="12%" class="text-right">Valore Unit.</th>
                    <th width="15%" class="text-right">Valore Tot.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ddtDeposito->dettagli as $index => $dettaglio)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            @if($dettaglio->isArticolo())
                                <span class="tipo-badge" style="background: #e3f2fd; color: #1565c0;">ART</span>
                            @else
                                <span class="tipo-badge" style="background: #fff8e1; color: #e65100;">PF</span>
                            @endif
                        </td>
                        <td><strong>{{ $dettaglio->codice_item }}</strong></td>
                        <td>{{ $dettaglio->descrizione }}</td>
                        <td class="text-center">
                            <strong>{{ $dettaglio->quantita }}</strong>
                            @if($dettaglio->quantita_ricevuta && $dettaglio->quantita_ricevuta != $dettaglio->quantita)
                                <br><small class="text-warning">(Ric: {{ $dettaglio->quantita_ricevuta }})</small>
                            @endif
                        </td>
                        <td class="text-right">€{{ number_format($dettaglio->valore_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">
                            <strong>€{{ number_format($dettaglio->valore_totale, 2, ',', '.') }}</strong>
                            @if($dettaglio->confermato)
                                <br><small class="text-success">✓ Confermato</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Riassunto valori --}}
    <div class="summary-box">
        <div class="summary-row">
            <span>Totale Articoli:</span>
            <strong>{{ $ddtDeposito->articoli_totali }}</strong>
        </div>
        <div class="summary-row">
            <span>Valore Dichiarato:</span>
            <strong>€{{ number_format($ddtDeposito->valore_dichiarato, 2, ',', '.') }}</strong>
        </div>
        @if($ddtDeposito->dettagli->where('confermato', true)->count() > 0)
            <div class="summary-row">
                <span>Articoli Confermati:</span>
                <strong class="text-success">{{ $ddtDeposito->dettagli->where('confermato', true)->count() }}/{{ $ddtDeposito->articoli_totali }}</strong>
            </div>
        @endif
    </div>

    {{-- Note --}}
    @if($ddtDeposito->note)
        <div style="margin: 20px 0;">
            <h4 style="color: #007bff; margin-bottom: 10px;">📝 Note</h4>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                {{ $ddtDeposito->note }}
            </div>
        </div>
    @endif

    {{-- Footer con firme --}}
    <div class="footer">
        <div class="footer-section">
            <div class="info-title">👤 Mittente</div>
            <div>{{ $ddtDeposito->sedeMittente->nome }}</div>
            @if($ddtDeposito->creatoDa)
                <div><small>Creato da: {{ $ddtDeposito->creatoDa->name }}</small></div>
            @endif
            <div class="signature-box">
                <div>Firma e Timbro:</div>
            </div>
        </div>

        <div class="footer-section" style="margin-left: 4%;">
            <div class="info-title">📝 Destinatario</div>
            <div>{{ $ddtDeposito->sedeDestinataria->nome }}</div>
            @if($ddtDeposito->confermatoDa)
                <div><small>Confermato da: {{ $ddtDeposito->confermatoDa->name }}</small></div>
            @endif
            <div class="signature-box">
                <div>Firma per ricevuta:</div>
            </div>
        </div>
    </div>

    {{-- Informazioni tecniche --}}
    <div style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
        <div>📄 Documento generato automaticamente da {{ config('app.name') }} il {{ now()->format('d/m/Y H:i') }}</div>
        <div>🔒 ID DDT: {{ $ddtDeposito->id }} | Tipo: {{ $ddtDeposito->tipo }} | Stato: {{ $ddtDeposito->stato }}</div>
        @if($ddtDeposito->numero_tracking)
            <div>📦 Tracking: {{ $ddtDeposito->numero_tracking }}</div>
        @endif
    </div>

    <script>
        // Auto-stampa se richiesto
        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
        
        // Marca come stampato se primo accesso
        @if($ddtDeposito->stato === 'creato')
            fetch('/ddt-deposito/{{ $ddtDeposito->id }}/marca-stampato', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            });
        @endif
    </script>
</body>
</html>
