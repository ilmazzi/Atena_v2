<?php

namespace App\Services;

use App\Models\ContoDeposito;
use App\Models\MovimentoDeposito;
use App\Models\Articolo;
use App\Models\ProdottoFinito;
use App\Models\Ddt;
use App\Models\DdtDettaglio;
use App\Models\Fattura;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ContoDepositoService - Gestione business logic conti deposito
 * 
 * Centralizza tutta la logica per:
 * - Creazione e gestione depositi
 * - Invio articoli/PF in deposito
 * - Registrazione vendite
 * - Gestione resi e rinnovi
 */
class ContoDepositoService
{
    /**
     * Crea un nuovo conto deposito
     */
    public function creaContoDeposito(
        int $sedeMittenteId,
        int $sedeDestinatariaId,
        array $articoli = [],
        array $prodottiFiniti = [],
        ?string $note = null
    ): ContoDeposito {
        // Validazione
        if ($sedeMittenteId === $sedeDestinatariaId) {
            throw new \InvalidArgumentException('La sede mittente deve essere diversa dalla destinataria');
        }

        if (empty($articoli) && empty($prodottiFiniti)) {
            throw new \InvalidArgumentException('Deve essere specificato almeno un articolo o prodotto finito');
        }

        return DB::transaction(function () use ($sedeMittenteId, $sedeDestinatariaId, $articoli, $prodottiFiniti, $note) {
            // Crea il conto deposito
            $contoDeposito = ContoDeposito::create([
                'codice' => ContoDeposito::generaCodice(),
                'sede_mittente_id' => $sedeMittenteId,
                'sede_destinataria_id' => $sedeDestinatariaId,
                'data_invio' => now()->toDateString(),
                'data_scadenza' => now()->addYear()->toDateString(),
                'stato' => 'attivo',
                'note' => $note,
                'creato_da' => auth()->id(),
            ]);

            // Processa articoli
            foreach ($articoli as $articoloData) {
                $this->inviaArticoloInDeposito(
                    $contoDeposito,
                    $articoloData['articolo_id'],
                    $articoloData['quantita'],
                    $articoloData['costo_unitario'] ?? null
                );
            }

            // Processa prodotti finiti
            foreach ($prodottiFiniti as $pfData) {
                $this->inviaProdottoFinitoInDeposito(
                    $contoDeposito,
                    $pfData['prodotto_finito_id'],
                    $pfData['costo_unitario'] ?? null
                );
            }

            // Aggiorna statistiche
            $contoDeposito->aggiornaStatistiche();

            return $contoDeposito;
        });
    }

    /**
     * Invia un articolo in conto deposito
     */
    public function inviaArticoloInDeposito(
        ContoDeposito $contoDeposito,
        int $articoloId,
        int $quantita,
        ?float $costoUnitario = null
    ): MovimentoDeposito {
        $articolo = Articolo::with('giacenza')->findOrFail($articoloId);

        // Validazioni
        $qtaDisponibile = $articolo->getQuantitaDisponibile();
        if ($quantita > $qtaDisponibile) {
            throw new \InvalidArgumentException("Quantità richiesta ({$quantita}) superiore alla disponibile ({$qtaDisponibile})");
        }

        $costoUnitario = $costoUnitario ?? $articolo->prezzo_acquisto ?? 0;

        return DB::transaction(function () use ($contoDeposito, $articolo, $quantita, $costoUnitario) {
            // Crea movimento
            $movimento = MovimentoDeposito::creaInvio(
                $contoDeposito,
                $articolo,
                $quantita,
                $costoUnitario,
                null, // DDT verrà associato successivamente
                "Invio in conto deposito {$contoDeposito->codice}"
            );

            // Aggiorna articolo
            $articolo->aggiornaQuantitaInDeposito();

            return $movimento;
        });
    }

    /**
     * Invia un prodotto finito in conto deposito
     */
    public function inviaProdottoFinitoInDeposito(
        ContoDeposito $contoDeposito,
        int $prodottoFinitoId,
        ?float $costoUnitario = null
    ): MovimentoDeposito {
        $prodottoFinito = ProdottoFinito::findOrFail($prodottoFinitoId);

        // Validazioni
        if (!$prodottoFinito->isDisponibilePerDeposito()) {
            throw new \InvalidArgumentException("Il prodotto finito {$prodottoFinito->codice} non è disponibile per il deposito");
        }

        $costoUnitario = $costoUnitario ?? $prodottoFinito->costo_totale ?? 0;

        return DB::transaction(function () use ($contoDeposito, $prodottoFinito, $costoUnitario) {
            // Crea movimento
            $movimento = MovimentoDeposito::creaInvio(
                $contoDeposito,
                $prodottoFinito,
                1, // I PF sono sempre quantità 1
                $costoUnitario,
                null, // DDT verrà associato successivamente
                "Invio PF in conto deposito {$contoDeposito->codice}"
            );

            // Aggiorna prodotto finito
            $prodottoFinito->aggiornaStatoDeposito();

            return $movimento;
        });
    }

    /**
     * Registra una vendita dal conto deposito
     */
    public function registraVendita(
        ContoDeposito $contoDeposito,
        $item, // Articolo o ProdottoFinito
        int $quantita,
        ?Fattura $fattura = null
    ): MovimentoDeposito {
        $isArticolo = $item instanceof Articolo;
        $costoUnitario = $isArticolo ? $item->prezzo_acquisto : $item->costo_totale;

        return DB::transaction(function () use ($contoDeposito, $item, $quantita, $costoUnitario, $fattura, $isArticolo) {
            // Crea movimento vendita
            $movimento = MovimentoDeposito::creaVendita(
                $contoDeposito,
                $item,
                $quantita,
                $costoUnitario,
                $fattura,
                "Vendita da conto deposito {$contoDeposito->codice}"
            );

            // Aggiorna item
            if ($isArticolo) {
                $item->aggiornaQuantitaInDeposito();
                
                // Se venduto tutto, aggiorna giacenza
                if ($quantita >= $item->quantita_in_deposito) {
                    $item->giacenza->update([
                        'quantita_residua' => max(0, $item->giacenza->quantita_residua - $quantita)
                    ]);
                }
            } else {
                $item->update(['stato' => 'venduto']);
                $item->aggiornaStatoDeposito();
            }

            // Aggiorna statistiche deposito
            $contoDeposito->aggiornaStatistiche();

            return $movimento;
        });
    }

    /**
     * Gestisce il reso automatico alla scadenza
     */
    public function gestisciResoScadenza(ContoDeposito $contoDeposito): Collection
    {
        if (!$contoDeposito->isScaduto()) {
            throw new \InvalidArgumentException('Il deposito non è ancora scaduto');
        }

        return DB::transaction(function () use ($contoDeposito) {
            $movimentiReso = collect();

            // Reso articoli rimanenti
            $articoliRimanenti = $this->getArticoliRimanentiInDeposito($contoDeposito);
            foreach ($articoliRimanenti as $articoloData) {
                $movimento = MovimentoDeposito::create([
                    'conto_deposito_id' => $contoDeposito->id,
                    'articolo_id' => $articoloData['articolo']->id,
                    'tipo_movimento' => 'reso',
                    'quantita' => $articoloData['quantita'],
                    'costo_unitario' => $articoloData['costo_unitario'],
                    'costo_totale' => $articoloData['quantita'] * $articoloData['costo_unitario'],
                    'data_movimento' => now()->toDateString(),
                    'note' => "Reso automatico scadenza {$contoDeposito->codice}",
                ]);

                $articoloData['articolo']->aggiornaQuantitaInDeposito();
                $movimentiReso->push($movimento);
            }

            // Reso prodotti finiti rimanenti
            $prodottiFinitiRimanenti = $this->getProdottiFinitiRimanentiInDeposito($contoDeposito);
            foreach ($prodottiFinitiRimanenti as $pfData) {
                $movimento = MovimentoDeposito::create([
                    'conto_deposito_id' => $contoDeposito->id,
                    'prodotto_finito_id' => $pfData['prodotto_finito']->id,
                    'tipo_movimento' => 'reso',
                    'quantita' => 1,
                    'costo_unitario' => $pfData['costo_unitario'],
                    'costo_totale' => $pfData['costo_unitario'],
                    'data_movimento' => now()->toDateString(),
                    'note' => "Reso automatico scadenza {$contoDeposito->codice}",
                ]);

                $pfData['prodotto_finito']->update(['in_conto_deposito' => false]);
                $movimentiReso->push($movimento);
            }

            // Aggiorna stato deposito
            $contoDeposito->update(['stato' => 'chiuso']);
            $contoDeposito->aggiornaStatistiche();

            return $movimentiReso;
        });
    }

    /**
     * Crea un nuovo deposito identico (rimando dopo reso)
     */
    public function creaRimandoDopoReso(ContoDeposito $depositoOriginale): ContoDeposito
    {
        if (!$depositoOriginale->isChiuso()) {
            throw new \InvalidArgumentException('Il deposito deve essere chiuso per poter essere rimandato');
        }

        return DB::transaction(function () use ($depositoOriginale) {
            // Crea nuovo deposito
            $nuovoDeposito = ContoDeposito::create([
                'codice' => ContoDeposito::generaCodice(),
                'sede_mittente_id' => $depositoOriginale->sede_mittente_id,
                'sede_destinataria_id' => $depositoOriginale->sede_destinataria_id,
                'data_invio' => now()->toDateString(),
                'data_scadenza' => now()->addYear()->toDateString(),
                'stato' => 'attivo',
                'deposito_precedente_id' => $depositoOriginale->id,
                'note' => "Rimando del deposito {$depositoOriginale->codice}",
                'creato_da' => auth()->id(),
            ]);

            // Ricrea gli stessi movimenti del deposito originale
            $movimentiOriginali = $depositoOriginale->movimentiReso;
            foreach ($movimentiOriginali as $movimentoOriginale) {
                $item = $movimentoOriginale->getItem();
                
                MovimentoDeposito::creaRimando(
                    $nuovoDeposito,
                    $item,
                    $movimentoOriginale->quantita,
                    $movimentoOriginale->costo_unitario,
                    null, // DDT verrà associato successivamente
                    "Rimando da deposito {$depositoOriginale->codice}"
                );

                // Aggiorna stato item
                if ($item instanceof Articolo) {
                    $item->aggiornaQuantitaInDeposito();
                } else {
                    $item->aggiornaStatoDeposito();
                }
            }

            // Aggiorna statistiche
            $nuovoDeposito->aggiornaStatistiche();

            return $nuovoDeposito;
        });
    }

    /**
     * Ottieni articoli rimanenti in un deposito
     */
    public function getArticoliRimanentiInDeposito(ContoDeposito $contoDeposito): Collection
    {
        $movimentiInvio = $contoDeposito->movimenti()
            ->where('tipo_movimento', 'invio')
            ->whereNotNull('articolo_id')
            ->get()
            ->groupBy('articolo_id');

        $movimentiVendita = $contoDeposito->movimenti()
            ->where('tipo_movimento', 'vendita')
            ->whereNotNull('articolo_id')
            ->get()
            ->groupBy('articolo_id');

        $articoliRimanenti = collect();

        foreach ($movimentiInvio as $articoloId => $movimenti) {
            $qtaInviata = $movimenti->sum('quantita');
            $qtaVenduta = $movimentiVendita->get($articoloId, collect())->sum('quantita');
            $qtaRimanente = $qtaInviata - $qtaVenduta;

            if ($qtaRimanente > 0) {
                $articolo = Articolo::find($articoloId);
                $articoliRimanenti->push([
                    'articolo' => $articolo,
                    'quantita' => $qtaRimanente,
                    'costo_unitario' => $movimenti->first()->costo_unitario
                ]);
            }
        }

        return $articoliRimanenti;
    }

    /**
     * Ottieni prodotti finiti rimanenti in un deposito
     */
    public function getProdottiFinitiRimanentiInDeposito(ContoDeposito $contoDeposito): Collection
    {
        $movimentiInvio = $contoDeposito->movimenti()
            ->where('tipo_movimento', 'invio')
            ->whereNotNull('prodotto_finito_id')
            ->get();

        $movimentiVendita = $contoDeposito->movimenti()
            ->where('tipo_movimento', 'vendita')
            ->whereNotNull('prodotto_finito_id')
            ->get()
            ->pluck('prodotto_finito_id')
            ->toArray();

        $prodottiFinitiRimanenti = collect();

        foreach ($movimentiInvio as $movimento) {
            if (!in_array($movimento->prodotto_finito_id, $movimentiVendita)) {
                $prodottoFinito = ProdottoFinito::find($movimento->prodotto_finito_id);
                $prodottiFinitiRimanenti->push([
                    'prodotto_finito' => $prodottoFinito,
                    'costo_unitario' => $movimento->costo_unitario
                ]);
            }
        }

        return $prodottiFinitiRimanenti;
    }

    /**
     * Genera DDT di invio per il deposito
     */
    public function generaDdtInvio(ContoDeposito $contoDeposito): Ddt
    {
        return DB::transaction(function () use ($contoDeposito) {
            // Crea DDT
            $ddt = Ddt::create([
                'numero' => $this->generaNumeroDdt(),
                'data_documento' => $contoDeposito->data_invio,
                'anno' => $contoDeposito->data_invio->year,
                'fornitore_id' => null, // Non è un fornitore, è un trasferimento interno
                'sede_id' => $contoDeposito->sede_mittente_id,
                'tipo_documento' => 'trasferimento_deposito',
                'note' => "DDT invio conto deposito {$contoDeposito->codice}",
                'creato_da' => auth()->id(),
            ]);

            // Aggiungi dettagli per ogni movimento di invio
            $movimentiInvio = $contoDeposito->movimenti()
                ->where('tipo_movimento', 'invio')
                ->get();

            foreach ($movimentiInvio as $movimento) {
                DdtDettaglio::create([
                    'ddt_id' => $ddt->id,
                    'articolo_id' => $movimento->articolo_id,
                    'prodotto_finito_id' => $movimento->prodotto_finito_id,
                    'quantita' => $movimento->quantita,
                    'prezzo_unitario' => $movimento->costo_unitario,
                    'totale' => $movimento->costo_totale,
                    'descrizione' => $movimento->isArticolo() ? 
                        $movimento->articolo->descrizione : 
                        $movimento->prodottoFinito->descrizione,
                ]);

                // Aggiorna movimento con DDT
                $movimento->update(['ddt_id' => $ddt->id]);
            }

            // Aggiorna deposito con DDT
            $contoDeposito->update(['ddt_invio_id' => $ddt->id]);

            return $ddt;
        });
    }

    /**
     * Genera DDT di reso per il deposito
     */
    public function generaDdtReso(ContoDeposito $contoDeposito): Ddt
    {
        return DB::transaction(function () use ($contoDeposito) {
            // Crea DDT
            $ddt = Ddt::create([
                'numero' => $this->generaNumeroDdt(),
                'data_documento' => now()->toDateString(),
                'anno' => now()->year,
                'fornitore_id' => null,
                'sede_id' => $contoDeposito->sede_destinataria_id,
                'tipo_documento' => 'reso_deposito',
                'note' => "DDT reso conto deposito {$contoDeposito->codice}",
                'creato_da' => auth()->id(),
            ]);

            // Aggiungi dettagli per articoli rimanenti
            $articoliRimanenti = $this->getArticoliRimanentiInDeposito($contoDeposito);
            foreach ($articoliRimanenti as $articoloData) {
                DdtDettaglio::create([
                    'ddt_id' => $ddt->id,
                    'articolo_id' => $articoloData['articolo']->id,
                    'quantita' => $articoloData['quantita'],
                    'prezzo_unitario' => $articoloData['costo_unitario'],
                    'totale' => $articoloData['quantita'] * $articoloData['costo_unitario'],
                    'descrizione' => $articoloData['articolo']->descrizione,
                ]);
            }

            // Aggiungi dettagli per PF rimanenti
            $pfRimanenti = $this->getProdottiFinitiRimanentiInDeposito($contoDeposito);
            foreach ($pfRimanenti as $pfData) {
                DdtDettaglio::create([
                    'ddt_id' => $ddt->id,
                    'prodotto_finito_id' => $pfData['prodotto_finito']->id,
                    'quantita' => 1,
                    'prezzo_unitario' => $pfData['costo_unitario'],
                    'totale' => $pfData['costo_unitario'],
                    'descrizione' => $pfData['prodotto_finito']->descrizione,
                ]);
            }

            // Aggiorna deposito con DDT reso
            $contoDeposito->update(['ddt_reso_id' => $ddt->id]);

            return $ddt;
        });
    }


    /**
     * Genera numero DDT progressivo
     */
    private function generaNumeroDdt(): string
    {
        $anno = now()->year;
        $ultimoNumero = Ddt::where('anno', $anno)
            ->where('numero', 'like', "DEP-{$anno}-%")
            ->orderBy('numero', 'desc')
            ->value('numero');

        if ($ultimoNumero) {
            $numero = intval(substr($ultimoNumero, -4)) + 1;
        } else {
            $numero = 1;
        }

        return sprintf('DEP-%d-%04d', $anno, $numero);
    }

    /**
     * Ottieni statistiche depositi per dashboard
     */
    public function getStatisticheDepositi(): array
    {
        return [
            'depositi_attivi' => ContoDeposito::attivi()->count(),
            'depositi_in_scadenza' => ContoDeposito::inScadenza(30)->count(),
            'depositi_scaduti' => ContoDeposito::scaduti()->count(),
            'valore_totale_depositi' => ContoDeposito::attivi()->sum('valore_totale_invio'),
            'articoli_in_deposito' => MovimentoDeposito::whereHas('contoDeposito', function($query) {
                $query->where('stato', 'attivo');
            })->where('tipo_movimento', 'invio')->sum('quantita'),
        ];
    }
}
