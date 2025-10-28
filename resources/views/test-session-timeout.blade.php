@extends('layouts.vertical', ['title' => 'Test Session Timeout'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <iconify-icon icon="solar:clock-circle-bold-duotone" class="me-2"></iconify-icon>
                        Test Sistema Session Timeout
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">ℹ️ Informazioni Sessione</h6>
                        <ul class="mb-0">
                            <li><strong>Durata sessione:</strong> {{ config('session.lifetime') }} minuti</li>
                            <li><strong>Avviso a:</strong> {{ config('session.lifetime') - 5 }} minuti (5 min prima scadenza)</li>
                            <li><strong>Driver sessione:</strong> {{ config('session.driver') }}</li>
                        </ul>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-lg-6">
                            <div class="card bg-light hover-shadow">
                                <div class="card-body">
                                    <h6 class="text-primary">
                                        <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                                        Come Funziona
                                    </h6>
                                    <ol class="small">
                                        <li>Il sistema monitora la tua attività (mouse, keyboard, scroll)</li>
                                        <li>5 minuti prima della scadenza, mostra un popup elegante con timer</li>
                                        <li>Puoi scegliere di:
                                            <ul class="mt-2">
                                                <li><span class="badge bg-success">🔄 Rinnovare</span> la sessione</li>
                                                <li><span class="badge bg-danger">🚪 Uscire</span> subito</li>
                                                <li><span class="badge bg-info">▶️ Continuare</span> a lavorare</li>
                                            </ul>
                                        </li>
                                        <li>Se non scegli, al termine del countdown verrai disconnesso</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card bg-warning bg-opacity-10 border-warning hover-shadow">
                                <div class="card-body">
                                    <h6 class="text-warning">
                                        <iconify-icon icon="solar:play-circle-bold" class="me-2"></iconify-icon>
                                        Test Rapido
                                    </h6>
                                    <p class="small mb-3">Vuoi testare subito il nuovo popup elegante?</p>
                                    <button type="button" class="btn btn-warning btn-lg w-100" onclick="testSessionWarning()">
                                        <iconify-icon icon="solar:play-bold" class="me-2"></iconify-icon>
                                        Mostra Popup Test
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <div class="card bg-primary bg-opacity-5 border-primary">
                                <div class="card-body">
                                    <h6 class="text-primary">
                                        <iconify-icon icon="solar:magic-stick-bold" class="me-2"></iconify-icon>
                                        Nuove Caratteristiche Eleganti
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <iconify-icon icon="solar:clock-circle-bold" class="text-warning me-2"></iconify-icon>
                                                <span class="small fw-bold">Timer Animato</span>
                                            </div>
                                            <p class="small text-muted">Countdown con progress bar e animazioni fluide</p>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <iconify-icon icon="solar:palette-bold" class="text-info me-2"></iconify-icon>
                                                <span class="small fw-bold">Design Moderno</span>
                                            </div>
                                            <p class="small text-muted">Stile Larkon con gradienti e ombre eleganti</p>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <iconify-icon icon="solar:smartphone-bold" class="text-success me-2"></iconify-icon>
                                                <span class="small fw-bold">Responsive</span>
                                            </div>
                                            <p class="small text-muted">Perfetto su desktop, tablet e mobile</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-dark">
                            <iconify-icon icon="solar:settings-bold" class="me-2"></iconify-icon>
                            Controlli Manuali
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100" onclick="checkSessionStatus()">
                                    <iconify-icon icon="solar:refresh-bold" class="me-1"></iconify-icon>
                                    Verifica Stato
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-success w-100" onclick="renewSessionNow()">
                                    <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>
                                    Rinnova Ora
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-info w-100" onclick="resetTimerManually()">
                                    <iconify-icon icon="solar:restart-bold" class="me-1"></iconify-icon>
                                    Reset Timer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function testSessionWarning() {
        if (window.sessionManager) {
            window.sessionManager.showWarning();
        } else {
            alert('Session manager non inizializzato');
        }
    }
    
    function checkSessionStatus() {
        fetch('/api/user', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.ok) {
                Swal.fire({
                    title: '✅ Sessione Attiva',
                    text: 'La tua sessione è ancora valida',
                    icon: 'success',
                    timer: 2000
                });
            } else {
                Swal.fire({
                    title: '❌ Sessione Scaduta',
                    text: 'La sessione non è più valida',
                    icon: 'error'
                });
            }
        });
    }
    
    function renewSessionNow() {
        fetch('/api/renew-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: '✅ Sessione Rinnovata',
                text: 'La sessione è stata rinnovata con successo!',
                icon: 'success',
                timer: 2000
            });
            
            if (window.sessionManager) {
                window.sessionManager.resetTimer();
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Errore',
                text: 'Impossibile rinnovare la sessione',
                icon: 'error'
            });
        });
    }
    
    function resetTimerManually() {
        if (window.sessionManager) {
            window.sessionManager.resetTimer();
            Swal.fire({
                title: '🔄 Timer Resettato',
                text: 'Il timer della sessione è stato resettato',
                icon: 'info',
                timer: 1500
            });
        }
    }
</script>
@endpush

