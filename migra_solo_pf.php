<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ProdottoFinito;
use App\Models\ComponenteProdotto;
use App\Models\Articolo;

echo "🏭 MIGRAZIONE SOLO PRODOTTI FINITI\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Pulisci
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('componenti_prodotto')->truncate();
DB::table('prodotti_finiti')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Migra PF
$prodottiFiniti = DB::connection('mssql_prod')
    ->table('elenco_articoli_magazzino')
    ->whereIn('id_magazzino', [9, 22])
    ->orderBy('id_pf')
    ->get();

echo "Prodotti finiti trovati: {$prodottiFiniti->count()}\n\n";

$componentiMigrati = 0;

foreach ($prodottiFiniti as $pf) {
    DB::table('prodotti_finiti')->insertOrIgnore([
        'id' => $pf->id_pf,
        'codice' => $pf->id_magazzino . '-' . $pf->carico,
        'descrizione' => $pf->descrizione,
        'tipologia' => $pf->id_magazzino == 9 ? 'prodotto_finito' : 'semilavorato',
        'magazzino_id' => $pf->id_magazzino,
        'costo_totale' => $pf->valore_magazzino ?? 0,
        'stato' => 'completato',
        'data_completamento' => $pf->data_documento ?? now(),
        'note' => "Migrato da MSSQL",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $prodottoFinito = ProdottoFinito::find($pf->id_pf);
    
    // Gestisci componenti
    $componenti = DB::connection('mssql_prod')
        ->table('mag_diba')
        ->where('id_pf', $pf->id_pf)
        ->get();
    
    foreach ($componenti as $comp) {
        $articoloComponente = Articolo::find($comp->id_articolo);
        
        if ($articoloComponente) {
            $esistente = ComponenteProdotto::where('prodotto_finito_id', $prodottoFinito->id)
                ->where('articolo_id', $articoloComponente->id)
                ->first();
            
            if (!$esistente) {
                ComponenteProdotto::create([
                    'prodotto_finito_id' => $prodottoFinito->id,
                    'articolo_id' => $articoloComponente->id,
                    'quantita' => 1,
                    'costo_unitario' => $articoloComponente->prezzo_acquisto ?? 0,
                    'costo_totale' => $articoloComponente->prezzo_acquisto ?? 0,
                    'stato' => 'prelevato',
                    'prelevato_il' => now(),
                    'prelevato_da' => 1,
                ]);
                $componentiMigrati++;
            }
        } else {
            echo "⚠️  Articolo {$comp->id_articolo} non trovato per PF {$pf->id_pf}\n";
        }
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ PF migrati: {$prodottiFiniti->count()}\n";
echo "✅ Componenti migrati: {$componentiMigrati}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";





