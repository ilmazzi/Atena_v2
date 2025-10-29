<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Articolo;
use App\Models\ProdottoFinito;
use Illuminate\Http\Request;

/**
 * Controller per gestione movimentazioni interne tra sedi
 */
class MovimentazioneInternaController extends Controller
{
    /**
     * Mostra pagina creazione movimentazione
     */
    public function index()
    {
        $sedi = Sede::attive()->get();
        
        return view('movimentazioni-interne.index', compact('sedi'));
    }
    
    /**
     * Mostra DDT di movimentazione per stampa
     */
    public function stampaDdt($movimentazioneId)
    {
        $movimentazione = \App\Models\Movimentazione::with([
            'articolo',
            'magazzinoOrigine.sede',
            'magazzinoDestinazione.sede',
            'user'
        ])->findOrFail($movimentazioneId);
        
        return view('movimentazioni-interne.stampa-ddt', compact('movimentazione'));
    }
    
    /**
     * Download PDF DDT movimentazione
     */
    public function downloadDdt($movimentazioneId)
    {
        $movimentazione = \App\Models\Movimentazione::with([
            'articolo',
            'magazzinoOrigine.sede', 
            'magazzinoDestinazione.sede',
            'user'
        ])->findOrFail($movimentazioneId);
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('movimentazioni-interne.stampa-ddt', compact('movimentazione'));
        
        $filename = "DDT-MOV-{$movimentazione->numero_ddt}-" . now()->format('Y-m-d') . ".pdf";
        
        return $pdf->download($filename);
    }
}
