/**
 * Session Timeout Warning System
 * 
 * Monitora la sessione e avvisa l'utente prima della scadenza
 * con possibilità di rinnovare o fare logout
 */

class SessionTimeoutManager {
    constructor() {
        // Tempo sessione in minuti (dal config Laravel)
        this.sessionLifetime = window.sessionLifetime || 120; // 120 minuti default
        
        // Avvisa 5 minuti prima della scadenza
        this.warningTime = 5;
        
        // Tempo in millisecondi
        this.sessionLifetimeMs = this.sessionLifetime * 60 * 1000;
        this.warningTimeMs = this.warningTime * 60 * 1000;
        
        // Timer
        this.warningTimer = null;
        this.countdownInterval = null;
        
        // Flag per evitare popup multipli
        this.warningShown = false;
        
        this.init();
    }
    
    init() {
        console.log(`🔐 Session Timeout: ${this.sessionLifetime} minuti`);
        console.log(`⏰ Avviso tra: ${this.sessionLifetime - this.warningTime} minuti`);
        
        // Resetta timer ad ogni attività utente
        this.setupActivityListeners();
        
        // Avvia timer iniziale
        this.resetTimer();
    }
    
    setupActivityListeners() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        // Throttle: max 1 reset ogni 30 secondi per performance
        let lastReset = 0;
        const throttleMs = 30000;
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                const now = Date.now();
                if (now - lastReset > throttleMs) {
                    this.resetTimer();
                    lastReset = now;
                }
            }, { passive: true });
        });
    }
    
    resetTimer() {
        // Pulisci timer precedenti
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
        }
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
        
        // Reset flag
        this.warningShown = false;
        
        // Imposta nuovo timer per il warning
        const timeUntilWarning = this.sessionLifetimeMs - this.warningTimeMs;
        
        this.warningTimer = setTimeout(() => {
            this.showWarning();
        }, timeUntilWarning);
    }
    
    showWarning() {
        if (this.warningShown) return;
        this.warningShown = true;
        
        let secondsLeft = this.warningTime * 60;
        
        // Crea modal Bootstrap nativo
        const modalHtml = `
            <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title d-flex align-items-center">
                                <iconify-icon icon="solar:clock-circle-bold-duotone" class="fs-1 text-warning me-3"></iconify-icon>
                                <span class="fs-4 fw-bold">Sessione in Scadenza</span>
                            </h5>
                        </div>
                        <div class="modal-body text-center py-4">
                            <p class="text-muted mb-4">La tua sessione sta per scadere per inattività</p>
                            
                            <div class="alert alert-warning border-0">
                                <strong>Sessione in scadenza tra:</strong>
                                <div class="fs-1 fw-bold mt-2" id="countdown-timer">
                                    <span id="minutes">5</span>:<span id="seconds">00</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-primary" id="continueBtn">Continua</button>
                            <button type="button" class="btn btn-danger" id="logoutBtn">Esci Ora</button>
                            <button type="button" class="btn btn-success" id="renewBtn">Rinnova Sessione</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Rimuovi modal esistente se presente
        const existingModal = document.getElementById('sessionTimeoutModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Aggiungi modal al body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostra modal
        const modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
        modal.show();
        
        // Avvia countdown
        this.startCountdown(secondsLeft);
        
        // Event listeners
        document.getElementById('renewBtn').addEventListener('click', () => {
            modal.hide();
            this.renewSession();
        });
        
        document.getElementById('logoutBtn').addEventListener('click', () => {
            modal.hide();
            this.logout();
        });
        
        document.getElementById('continueBtn').addEventListener('click', () => {
            modal.hide();
            this.resetTimer();
        });
        
        // Cleanup quando modal si chiude
        document.getElementById('sessionTimeoutModal').addEventListener('hidden.bs.modal', () => {
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
            }
            document.getElementById('sessionTimeoutModal').remove();
        });
    }
    
    startCountdown(totalSeconds) {
        const maxSeconds = totalSeconds;
        
        const updateDisplay = () => {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            
            const minutesEl = document.getElementById('minutes');
            const secondsEl = document.getElementById('seconds');
            const progressBar = document.getElementById('progress-bar');
            
            if (minutesEl && secondsEl) {
                minutesEl.textContent = minutes;
                secondsEl.textContent = seconds.toString().padStart(2, '0');
                
                // Aggiorna progress bar
                if (progressBar) {
                    const progress = ((maxSeconds - totalSeconds) / maxSeconds) * 100;
                    progressBar.style.width = `${progress}%`;
                }
                
                // Cambia colore quando mancano meno di 60 secondi
                const timerEl = document.getElementById('countdown-timer');
                if (timerEl) {
                    if (totalSeconds <= 60) {
                        timerEl.classList.add('text-danger');
                        timerEl.classList.remove('text-warning');
                    } else if (totalSeconds <= 120) {
                        timerEl.classList.add('text-warning');
                    }
                }
            }
            
            totalSeconds--;
            
            if (totalSeconds < 0) {
                clearInterval(this.countdownInterval);
                // Sessione scaduta - forza logout
                Swal.close();
                this.sessionExpired();
            }
        };
        
        updateDisplay();
        this.countdownInterval = setInterval(updateDisplay, 1000);
    }
    
    
    async renewSession() {
        try {
            // Chiama endpoint Laravel per rinnovare sessione
            const response = await fetch('/api/renew-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                Swal.fire({
                    title: '✅ Sessione Rinnovata',
                    text: 'La tua sessione è stata rinnovata con successo!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                });
                
                // Reset timer
                this.resetTimer();
            } else {
                console.error('Errore rinnovo sessione:', data.message);
                throw new Error(data.message || 'Errore rinnovo sessione');
            }
        } catch (error) {
            console.error('Errore renewSession:', error);
            Swal.fire({
                title: 'Errore',
                text: 'Impossibile rinnovare la sessione. Verrai disconnesso.',
                icon: 'error',
                timer: 3000,
            }).then(() => {
                this.logout();
            });
        }
    }
    
    logout() {
        Swal.fire({
            title: 'Disconnessione...',
            text: 'Reindirizzamento alla pagina di login',
            icon: 'info',
            timer: 1500,
            showConfirmButton: false,
        }).then(() => {
            // Submit logout form o redirect
            const logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                logoutForm.submit();
            } else {
                window.location.href = '/logout';
            }
        });
    }
    
    sessionExpired() {
        Swal.fire({
            title: '❌ Sessione Scaduta',
            text: 'La tua sessione è scaduta per inattività. Effettua nuovamente il login.',
            icon: 'error',
            confirmButtonText: 'Vai al Login',
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then(() => {
            window.location.href = '/login';
        });
    }
}

// Inizializza quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    // Verifica se l'utente è autenticato
    if (document.querySelector('meta[name="user-authenticated"]')) {
        window.sessionManager = new SessionTimeoutManager();
    }
});

// Export per uso in altri moduli
export default SessionTimeoutManager;

