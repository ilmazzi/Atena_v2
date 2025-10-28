<?php

namespace App\Http\Controllers;

use App\Models\Ddt;
use Illuminate\Http\Request;

class DdtController extends Controller
{
    /**
     * Stampa DDT
     */
    public function stampa($id)
    {
        $ddt = Ddt::with([
            'dettagli.articolo.categoriaMerceologica',
            'dettagli.prodottoFinito.categoriaMerceologica',
            'sede',
            'fornitore',
            'creatoDa'
        ])->findOrFail($id);

        return view('ddt.stampa', [
            'ddt' => $ddt,
        ]);
    }
}