<?php

namespace App\Http\Controllers;

use App\Models\FatturaVendita;
use Illuminate\Http\Request;

/**
 * FatturaVenditaController - Controller per Fatture di Vendita
 * 
 * Gestisce anteprima e stampa delle fatture di vendita dai conti deposito
 */
class FatturaVenditaController extends Controller
{
    /**
     * Anteprima Fattura Vendita
     */
    public function show(FatturaVendita $fatturaVendita)
    {
        $fatturaVendita->load([
            'contoDeposito',
            'sede',
            'ddtInvio',
            'movimenti.articolo',
            'movimenti.prodottoFinito'
        ]);

        return view('fatture-vendita.dettaglio', compact('fatturaVendita'));
    }

    /**
     * Stampa Fattura Vendita
     */
    public function stampa(FatturaVendita $fatturaVendita)
    {
        $fatturaVendita->load([
            'contoDeposito',
            'sede',
            'ddtInvio',
            'movimenti.articolo.categoriaMerceologica',
            'movimenti.prodottoFinito.categoriaMerceologica'
        ]);

        return view('fatture-vendita.stampa', compact('fatturaVendita'));
    }
}
