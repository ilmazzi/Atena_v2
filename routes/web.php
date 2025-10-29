<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MagazzinoViewController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\RoutingController;
use App\Http\Livewire\CaricoDocumento;
use App\Http\Livewire\ArticoliTable;
use App\Http\Livewire\DocumentiAcquistoTable;
use App\Http\Livewire\ProdottiFinitiTable;
use App\Http\Livewire\CreaProdottoFinito;
use App\Http\Livewire\ModificaProdottoFinito;
use App\Http\Livewire\ScaricoMagazzino;
use App\Http\Livewire\ScannerInventario;
use App\Http\Livewire\StampantiTable;
use App\Http\Livewire\InventarioDashboard;
use App\Http\Livewire\SessioniInventario;
use App\Http\Livewire\ScannerInventarioAvanzato;
use App\Http\Livewire\StoricoArticoli;
use App\Http\Livewire\InventarioMonitor;
use App\Http\Controllers\StampaController;
use App\Models\OcrDocument;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware([
    'auth',
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Athena routes
Route::middleware('auth')->group(function () {
    
    // Magazzino routes
    Route::prefix('magazzino')->group(function () {
        Route::get('/', [MagazzinoViewController::class, 'index'])->name('magazzino.index');
        Route::get('/articoli', [MagazzinoViewController::class, 'articoli'])->name('magazzino.articoli');
        Route::get('/articoli/{id}', [MagazzinoViewController::class, 'show'])->name('magazzino.articoli.show');
        Route::get('/scarico', ScaricoMagazzino::class)->name('magazzino.scarico');
        Route::get('/scanner', ScannerInventario::class)->name('magazzino.scanner');
    });

    // Amministrazione Magazzino routes
    Route::prefix('amministrazione-magazzino')->name('amministrazione-magazzino.')->group(function () {
        Route::get('/', function () {
            return view('amministrazione-magazzino.index');
        })->name('index');
    });
    
    // Stampanti routes
Route::prefix('stampanti')->group(function () {
    Route::get('/', StampantiTable::class)->name('stampanti.index');
});

        // Vetrine routes
        Route::prefix('vetrine')->group(function () {
            Route::get('/', function () {
                return view('vetrine.index');
            })->name('vetrine.index');
            Route::get('/{id}', function ($id) {
                return view('vetrine.show', ['vetrinaId' => $id]);
            })->name('vetrine.show');
            Route::get('/{id}/stampa', [\App\Http\Controllers\VetrinaController::class, 'stampaVetrina'])->name('vetrine.stampa');
            Route::get('/{id}/pdf', [\App\Http\Controllers\VetrinaController::class, 'downloadPdfVetrina'])->name('vetrine.pdf');
        });

        // Conti Deposito routes
        Route::prefix('conti-deposito')->group(function () {
            Route::get('/', function () {
                return view('conti-deposito.index');
            })->name('conti-deposito.index');
            Route::get('/{id}', function ($id) {
                return view('conti-deposito.gestisci', ['depositoId' => $id]);
            })->name('conti-deposito.show');
            Route::get('/{id}/gestisci', function ($id) {
                return view('conti-deposito.gestisci', ['depositoId' => $id]);
            })->name('conti-deposito.gestisci');
            Route::get('/resi/gestione', \App\Http\Livewire\GestioneResiDeposito::class)->name('conti-deposito.resi');
        });
        
        // Notifiche routes
        Route::prefix('notifiche')->group(function () {
            Route::get('/', \App\Http\Livewire\DashboardNotifiche::class)->name('notifiche.index');
        });

        // DDT Acquisti routes
        Route::prefix('ddt')->group(function () {
            Route::get('/{id}/stampa', [\App\Http\Controllers\DdtController::class, 'stampa'])->name('ddt.stampa');
        });

        // DDT Deposito routes (separati dai DDT acquisti)
        Route::prefix('ddt-deposito')->name('ddt-deposito.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DdtDepositoController::class, 'index'])->name('index');
            Route::get('/{ddtDeposito}', [\App\Http\Controllers\DdtDepositoController::class, 'show'])->name('show');
            Route::get('/{ddtDeposito}/stampa', [\App\Http\Controllers\DdtDepositoController::class, 'stampa'])->name('stampa');
            Route::get('/{ddtDeposito}/pdf', [\App\Http\Controllers\DdtDepositoController::class, 'scaricaPdf'])->name('pdf');
            Route::post('/{ddtDeposito}/conferma-ricezione', [\App\Http\Controllers\DdtDepositoController::class, 'confermaRicezione'])->name('conferma-ricezione');
            Route::post('/{ddtDeposito}/marca-spedito', [\App\Http\Controllers\DdtDepositoController::class, 'marcaSpedito'])->name('marca-spedito');
        });

        // Movimentazioni Interne routes
        Route::prefix('movimentazioni-interne')->name('movimentazioni-interne.')->group(function () {
            Route::get('/', [\App\Http\Controllers\MovimentazioneInternaController::class, 'index'])->name('index');
            Route::get('/{movimentazione}/stampa', [\App\Http\Controllers\MovimentazioneInternaController::class, 'stampaDdt'])->name('stampa');
            Route::get('/{movimentazione}/download', [\App\Http\Controllers\MovimentazioneInternaController::class, 'downloadDdt'])->name('download');
        });

        // Fatture Vendita routes
        Route::prefix('fatture-vendita')->name('fatture-vendita.')->group(function () {
            Route::get('/{fatturaVendita}', [\App\Http\Controllers\FatturaVenditaController::class, 'show'])->name('show');
            Route::get('/{fatturaVendita}/stampa', [\App\Http\Controllers\FatturaVenditaController::class, 'stampa'])->name('stampa');
        });

// Routes Inventario
Route::prefix('inventario')->group(function () {
    Route::get('/', InventarioDashboard::class)->name('inventario.dashboard');
    Route::get('/sessioni', SessioniInventario::class)->name('inventario.sessioni');
    Route::get('/scanner/{sessione?}', ScannerInventarioAvanzato::class)->name('inventario.scanner');
    Route::get('/monitor/{sessione?}', InventarioMonitor::class)->name('inventario.monitor');
    Route::get('/storico', StoricoArticoli::class)->name('inventario.storico');
    Route::get('/report/{sessione}', function($sessione) {
        return redirect()->route('inventario.monitor', ['sessione' => $sessione]);
    })->name('inventario.report');
});
    
    // Stampa routes
    Route::prefix('stampa')->group(function () {
        Route::get('/etichetta/{articolo}', [StampaController::class, 'stampaEtichetta'])->name('stampa.etichetta');
        Route::post('/batch', [StampaController::class, 'stampaBatch'])->name('stampa.batch');
        Route::get('/download/{articolo}', [StampaController::class, 'downloadZPL'])->name('stampa.download');
        Route::get('/anteprima/{articolo}', [StampaController::class, 'anteprimaEtichetta'])->name('stampa.anteprima');
        Route::get('/stampanti-disponibili', [StampaController::class, 'stampantiDisponibili'])->name('stampa.stampanti');
        Route::get('/test/{stampante}', [StampaController::class, 'testStampante'])->name('stampa.test');
    });
    
    // Alias per retrocompatibilità - usa Livewire component
    Route::get('/articoli', ArticoliTable::class)->name('articoli.index');
    Route::get('/articoli/{id}', [MagazzinoViewController::class, 'show'])->name('articoli.show');
    
    // Documenti di acquisto (DDT e Fatture) - Livewire
    Route::prefix('documenti-acquisto')->group(function () {
        Route::get('/', DocumentiAcquistoTable::class)->name('documenti-acquisto.index');
        Route::get('/nuovo', CaricoDocumento::class)->name('documenti-acquisto.nuovo');
    });
    
    // Prodotti Finiti - Livewire
    Route::prefix('prodotti-finiti')->group(function () {
        Route::get('/', ProdottiFinitiTable::class)->name('prodotti-finiti.index');
        Route::get('/nuovo', CreaProdottoFinito::class)->name('prodotti-finiti.nuovo');
            Route::get('/{id}/modifica', ModificaProdottoFinito::class)->name('prodotti-finiti.modifica');
        Route::get('/{id}', [MagazzinoViewController::class, 'dettaglioProdottoFinito'])->name('prodotti-finiti.dettaglio');
    });
    
    // Test session timeout (rimuovere in produzione)
    Route::get('/test-session', function () {
        return view('test-session-timeout');
    })->name('test.session');
    
    // OCR routes
    Route::prefix('ocr')->group(function () {
        Route::get('/', [OcrController::class, 'index'])->name('ocr.index');
        Route::get('/dashboard', [OcrController::class, 'dashboard'])->name('ocr.dashboard');
        Route::get('/upload', [OcrController::class, 'create'])->name('ocr.upload');
        Route::post('/upload', [OcrController::class, 'store'])->name('ocr.store');
        
        Route::get('/{document}/validate', function (OcrDocument $document) {
            return view('ocr.validate-livewire', compact('document'));
        })->name('ocr.validate');
        
        Route::get('/{document}/pdf', [OcrController::class, 'showPdf'])->name('ocr.documents.pdf');
        Route::get('/{document}/download', [OcrController::class, 'downloadPdf'])->name('ocr.download');
        Route::delete('/{document}', [OcrController::class, 'destroy'])->name('ocr.destroy');
    });
    
    // Gestione entità base (Società, Sedi, Magazzini)
    Route::prefix('gestione')->group(function () {
        Route::get('/societa', \App\Http\Livewire\GestioneSocieta::class)->name('gestione.societa');
        Route::get('/sedi', \App\Http\Livewire\GestioneSedi::class)->name('gestione.sedi');
        Route::get('/magazzini', \App\Http\Livewire\GestioneMagazzini::class)->name('gestione.magazzini');
    });
});

// Larkon dynamic routing system (per le demo pages rimaste)
Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
});
