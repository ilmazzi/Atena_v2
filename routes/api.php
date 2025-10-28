<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rinnovo sessione - usa solo middleware web per sessioni normali
Route::post('/renew-session', function (Request $request) {
    // Verifica che l'utente sia autenticato
    if (!auth()->check()) {
        return response()->json([
            'success' => false,
            'message' => 'Utente non autenticato',
        ], 401);
    }
    
    // Rigenera la sessione per rinnovarla
    $request->session()->regenerate();
    
    return response()->json([
        'success' => true,
        'message' => 'Sessione rinnovata',
        'expires_at' => now()->addMinutes(config('session.lifetime'))->toIso8601String(),
    ]);
})->middleware(['web', 'auth'])->name('api.renew-session');
