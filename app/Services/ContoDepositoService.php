<?php

namespace App\Services;

use App\Models\ContoDeposito;
use App\Models\MovimentoDeposito;
use App\Models\Articolo;
use App\Models\ProdottoFinito;
use App\Models\DdtDeposito;
use App\Models\DdtDepositoDettaglio;
use App\Models\Fattura;
use App\Services\NotificaService;
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
            // Se deposito inter-società, associa articolo al magazzino CD della società destinataria
            // SALVA il magazzino originale nei dettagli del movimento
            $magazzinoOriginaleId = $articolo->categoria_merceologica_id;
            
            if ($contoDeposito->isInterSocieta()) {
                $societaDestinataria = $contoDeposito->getSocietaDestinataria();
                if ($societaDestinataria) {
                    $magazzinoCD = $societaDestinataria->getMagazzinoContoDeposito();
                    if ($magazzinoCD) {
                        // Associa articolo al magazzino CD (mantiene sede_id originale)
                        $articolo->update([
                            'categoria_merceologica_id' => $magazzinoCD->id,
                            // sede_id rimane invariata (società mittente)
                        ]);
                    }
                }
            }
            
            // Crea movimento con dettagli magazzino originale
            $movimento = MovimentoDeposito::creaInvio(
                $contoDeposito,
                $articolo,
                $quantita,
                $costoUnitario,
                null, // DDT verrà associato successivamente
                "Invio in conto deposito {$contoDeposito->codice}"
            );
            
            // Salva magazzino originale nei dettagli del movimento (per reso)
            if (isset($magazzinoOriginaleId)) {
                $movimento->update([
                    'dettagli' => array_merge($movimento->dettagli ?? [], [
                        'magazzino_originale_id' => $magazzinoOriginaleId,
                    ])
                ]);
            }

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
            // Se deposito inter-società, PF rimane nella sede originale ma viene associato
            // al magazzino CD della società destinataria (per visualizzazione)
            // NOTA: I PF potrebbero non avere categoria_merceologica_id, verificare struttura
            
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
                // Vendita ProdottoFinito - scaricare componenti
                \Log::info("🏆 Vendita PF ID {$item->id}: scarico componenti...");
                
                // Carica componenti con articoli
                $item->load(['componentiArticoli.articolo']);
                
                // Scarica ogni componente dal deposito
                foreach ($item->componentiArticoli as $componente) {
                    $articoloComponente = $componente->articolo;
                    $quantitaDaScaricare = $componente->quantita * $quantita; // quantità componente x quantità PF venduti
                    
                    \Log::info("📦 Scarico articolo {$articoloComponente->codice}: {$quantitaDaScaricare} unità");
                    
                    // Registra movimento di scarico per il componente
                    MovimentoDeposito::creaVendita(
                        $contoDeposito,
                        $articoloComponente,
                        $quantitaDaScaricare,
                        $componente->costo_unitario,
                        $fattura,
                        "Scarico componente da vendita PF {$item->codice}"
                    );
                    
                    // Aggiorna quantità in deposito del componente
                    $articoloComponente->aggiornaQuantitaInDeposito();
                    
                    // Aggiorna giacenza se necessario
                    if ($articoloComponente->giacenza) {
                        $articoloComponente->giacenza->update([
                            'quantita_residua' => max(0, $articoloComponente->giacenza->quantita_residua - $quantitaDaScaricare)
                        ]);
                    }
                }
                
                // Marca il PF come venduto
                $item->update(['stato' => 'venduto']);
                $item->aggiornaStatoDeposito();
                
                \Log::info("✅ PF {$item->codice} venduto e componenti scaricati");
            }

            // Aggiorna statistiche deposito
            $contoDeposito->aggiornaStatistiche();
            
            // Invia notifica vendita solo se deposito inter-società
            if ($contoDeposito->isInterSocieta()) {
                try {
                    $notificaService = new NotificaService();
                    $notificaService->notificaVendita($contoDeposito, $movimento);
                } catch (\Exception $e) {
                    \Log::warning("Errore creazione notifica vendita: " . $e->getMessage());
                    // Non bloccare il processo per errori di notifica
                }
            }

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
     * Gestisce il reso manuale di articoli specifici (prima della scadenza)
     * 
     * @param ContoDeposito $contoDeposito
     * @param array $articoli Array di ['articolo_id' => id, 'quantita' => qta]
     * @param array $prodottiFiniti Array di ['prodotto_finito_id' => id]
     * @return Collection Movimenti reso creati
     */
    public function gestisciResoManuale(ContoDeposito $contoDeposito, array $articoli = [], array $prodottiFiniti = []): Collection
    {
        if (empty($articoli) && empty($prodottiFiniti)) {
            throw new \InvalidArgumentException('Seleziona almeno un articolo o prodotto finito da restituire');
        }

        return DB::transaction(function () use ($contoDeposito, $articoli, $prodottiFiniti) {
            $movimentiReso = collect();

            // Reso articoli selezionati
            foreach ($articoli as $articoloData) {
                $articolo = Articolo::findOrFail($articoloData['articolo_id']);
                
                // Verifica che l'articolo sia in deposito
                if ($articolo->conto_deposito_corrente_id !== $contoDeposito->id) {
                    throw new \Exception("L'articolo {$articolo->codice} non è in questo deposito");
                }

                // Verifica quantità disponibile
                $quantitaDisponibile = $articolo->quantita_in_deposito ?? 0;
                $quantitaDaRestituire = $articoloData['quantita'] ?? $quantitaDisponibile;
                
                if ($quantitaDaRestituire > $quantitaDisponibile) {
                    throw new \Exception("Quantità richiesta ({$quantitaDaRestituire}) superiore a quella disponibile ({$quantitaDisponibile}) per {$articolo->codice}");
                }

                // Calcola costo unitario (dal movimento di invio o costo totale articolo)
                $movimentoInvio = $contoDeposito->movimenti()
                    ->where('articolo_id', $articolo->id)
                    ->where('tipo_movimento', 'invio')
                    ->first();
                
                $costoUnitario = $movimentoInvio ? $movimentoInvio->costo_unitario : ($articolo->costo_totale ?? 0);

                // Crea movimento reso
                $movimento = MovimentoDeposito::create([
                    'conto_deposito_id' => $contoDeposito->id,
                    'articolo_id' => $articolo->id,
                    'tipo_movimento' => 'reso',
                    'quantita' => $quantitaDaRestituire,
                    'costo_unitario' => $costoUnitario,
                    'costo_totale' => $quantitaDaRestituire * $costoUnitario,
                    'data_movimento' => now()->toDateString(),
                    'note' => "Reso manuale articolo {$articolo->codice}",
                    'eseguito_da' => auth()->id(),
                ]);

                // Aggiorna quantità in deposito dell'articolo
                $articolo->quantita_in_deposito = max(0, $quantitaDisponibile - $quantitaDaRestituire);
                
                // Se quantità = 0, rimuovi il deposito corrente
                if ($articolo->quantita_in_deposito == 0) {
                    $articolo->conto_deposito_corrente_id = null;
                }
                
                // Ripristina magazzino originale se deposito inter-società (sempre, anche se reso parziale)
                if ($contoDeposito->isInterSocieta()) {
                    // Recupera magazzino originale dal movimento di invio
                    $movimentoInvio = $contoDeposito->movimenti()
                        ->where('articolo_id', $articolo->id)
                        ->where('tipo_movimento', 'invio')
                        ->first();
                    
                    $magazzinoOriginaleId = null;
                    
                    if ($movimentoInvio && isset($movimentoInvio->dettagli['magazzino_originale_id'])) {
                        // Usa magazzino salvato nei dettagli
                        $magazzinoOriginaleId = $movimentoInvio->dettagli['magazzino_originale_id'];
                    } else {
                        // Fallback: trova magazzino nella sede mittente (primo disponibile)
                        $sedeMittente = $contoDeposito->sedeMittente;
                        if ($sedeMittente) {
                            // Cerca magazzini attivi nella sede mittente (escludi CD)
                            $magazzinoOriginale = $sedeMittente->categorieMerceologiche()
                                ->where('attivo', true)
                                ->where('codice', 'NOT LIKE', 'CD-%') // Escludi magazzini CD
                                ->orderBy('id')
                                ->first();
                            
                            if ($magazzinoOriginale) {
                                $magazzinoOriginaleId = $magazzinoOriginale->id;
                            }
                        }
                    }
                    
                    // Se trovato, ripristina il magazzino originale
                    if ($magazzinoOriginaleId) {
                        $articolo->categoria_merceologica_id = $magazzinoOriginaleId;
                    }
                }
                
                $articolo->save();
                $movimentiReso->push($movimento);
                
                // Invia notifica reso solo se deposito inter-società
                if ($contoDeposito->isInterSocieta()) {
                    try {
                        $notificaService = new NotificaService();
                        $notificaService->notificaReso($contoDeposito, $movimento);
                    } catch (\Exception $e) {
                        \Log::warning("Errore creazione notifica reso: " . $e->getMessage());
                        // Non bloccare il processo per errori di notifica
                    }
                }
            }

            // Reso prodotti finiti selezionati
            foreach ($prodottiFiniti as $pfData) {
                $prodottoFinito = ProdottoFinito::findOrFail($pfData['prodotto_finito_id']);
                
                // Verifica che il PF sia in deposito
                if (!$prodottoFinito->in_conto_deposito || $prodottoFinito->conto_deposito_corrente_id !== $contoDeposito->id) {
                    throw new \Exception("Il prodotto finito {$prodottoFinito->codice} non è in questo deposito");
                }

                // Calcola costo (dal movimento di invio o costo totale PF)
                $movimentoInvio = $contoDeposito->movimenti()
                    ->where('prodotto_finito_id', $prodottoFinito->id)
                    ->where('tipo_movimento', 'invio')
                    ->first();
                
                $costoUnitario = $movimentoInvio ? $movimentoInvio->costo_unitario : ($prodottoFinito->costo_totale ?? 0);

                // Crea movimento reso
                $movimento = MovimentoDeposito::create([
                    'conto_deposito_id' => $contoDeposito->id,
                    'prodotto_finito_id' => $prodottoFinito->id,
                    'tipo_movimento' => 'reso',
                    'quantita' => 1,
                    'costo_unitario' => $costoUnitario,
                    'costo_totale' => $costoUnitario,
                    'data_movimento' => now()->toDateString(),
                    'note' => "Reso manuale prodotto finito {$prodottoFinito->codice}",
                    'eseguito_da' => auth()->id(),
                ]);

                // Rimuovi PF dal deposito
                $prodottoFinito->update([
                    'in_conto_deposito' => false,
                    'conto_deposito_corrente_id' => null
                ]);
                
                $movimentiReso->push($movimento);
                
                // Invia notifica reso solo se deposito inter-società
                if ($contoDeposito->isInterSocieta()) {
                    try {
                        $notificaService = new NotificaService();
                        $notificaService->notificaReso($contoDeposito, $movimento);
                    } catch (\Exception $e) {
                        \Log::warning("Errore creazione notifica reso PF: " . $e->getMessage());
                    }
                }
            }

            // Aggiorna statistiche deposito
            $contoDeposito->aggiornaStatistiche();
            
            // Verifica se il deposito è completamente vuoto
            if ($contoDeposito->getArticoliRimanenti() == 0) {
                $contoDeposito->update(['stato' => 'chiuso']);
            } elseif ($contoDeposito->articoli_venduti > 0 || $contoDeposito->articoli_rientrati > 0) {
                // Se ci sono vendite o resi, lo stato diventa parziale
                $contoDeposito->update(['stato' => 'parziale']);
            }

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
                $prodottoFinito = ProdottoFinito::with(['componentiArticoli.articolo.categoriaMerceologica'])
                    ->find($movimento->prodotto_finito_id);
                
                $prodottiFinitiRimanenti->push([
                    'prodotto_finito' => $prodottoFinito,
                    'costo_unitario' => $movimento->costo_unitario,
                    'componenti' => $prodottoFinito->componentiArticoli->map(function ($componente) {
                        return [
                            'articolo' => $componente->articolo,
                            'quantita' => $componente->quantita,
                            'costo_unitario' => $componente->costo_unitario,
                            'costo_totale' => $componente->costo_totale,
                            'stato' => $componente->stato,
                        ];
                    })
                ]);
            }
        }

        return $prodottiFinitiRimanenti;
    }

    /**
     * Genera DDT di invio per il deposito
     * 
     * @param ContoDeposito $contoDeposito
     * @return DdtDeposito
     */
    public function generaDdtInvio(ContoDeposito $contoDeposito): DdtDeposito
    {
        return DB::transaction(function () use ($contoDeposito) {
            // Usa data_invio se disponibile, altrimenti oggi
            $dataDocumento = $contoDeposito->data_invio ?? now()->toDateString();
            $anno = $contoDeposito->data_invio ? $contoDeposito->data_invio->year : now()->year;

            // Genera numero DDT progressivo automatico
            $numeroDdt = DdtDeposito::generaNumeroDdt();

            // Crea DDT Deposito
            $ddtDeposito = DdtDeposito::create([
                'numero' => $numeroDdt,
                'data_documento' => $dataDocumento,
                'anno' => $anno,
                'conto_deposito_id' => $contoDeposito->id,
                'tipo' => 'invio',
                'sede_mittente_id' => $contoDeposito->sede_mittente_id,
                'sede_destinataria_id' => $contoDeposito->sede_destinataria_id,
                'stato' => 'creato',
                'causale' => 'Conto deposito',
                'valore_dichiarato' => $contoDeposito->valore_totale_invio ?? 0,
                'articoli_totali' => $contoDeposito->articoli_inviati ?? 0,
                'note' => "DDT invio conto deposito {$contoDeposito->codice}",
                'creato_da' => auth()->id(),
            ]);


            // Aggiungi dettagli per ogni movimento di invio
            $movimentiInvio = $contoDeposito->movimenti()
                ->where('tipo_movimento', 'invio')
                ->with(['articolo', 'prodottoFinito'])
                ->get();

            foreach ($movimentiInvio as $movimento) {
                $item = $movimento->getItem();
                
                DdtDepositoDettaglio::create([
                    'ddt_deposito_id' => $ddtDeposito->id,
                    'articolo_id' => $movimento->articolo_id,
                    'prodotto_finito_id' => $movimento->prodotto_finito_id,
                    'codice_item' => $item->codice,
                    'descrizione' => $item->descrizione,
                    'quantita' => $movimento->quantita,
                    'valore_unitario' => $movimento->costo_unitario,
                    'valore_totale' => $movimento->costo_totale,
                ]);
            }

            // Aggiorna deposito con DDT
            $contoDeposito->update(['ddt_invio_id' => $ddtDeposito->id]);

            return $ddtDeposito;
        });
    }

    /**
     * Genera DDT di reso per il deposito
     * 
     * @param ContoDeposito $contoDeposito
     * @return DdtDeposito
     */
    public function generaDdtReso(ContoDeposito $contoDeposito): DdtDeposito
    {
        return DB::transaction(function () use ($contoDeposito) {
            // Genera numero DDT progressivo automatico
            $numeroDdt = DdtDeposito::generaNumeroDdt();

            // Crea DDT Deposito per reso
            $ddtDeposito = DdtDeposito::create([
                'numero' => $numeroDdt,
                'data_documento' => now()->toDateString(),
                'anno' => now()->year,
                'conto_deposito_id' => $contoDeposito->id,
                'tipo' => 'reso',
                'sede_mittente_id' => $contoDeposito->sede_destinataria_id, // Ora il destinatario diventa mittente
                'sede_destinataria_id' => $contoDeposito->sede_mittente_id, // E il mittente diventa destinatario
                'stato' => 'creato',
                'causale' => 'Reso conto deposito',
                'note' => "DDT reso conto deposito {$contoDeposito->codice}",
                'creato_da' => auth()->id(),
            ]);

            $articoliTotali = 0;
            $valoreTotale = 0;

            // Ottieni TUTTI i movimenti reso (sia manuali che automatici)
            $movimentiReso = $contoDeposito->movimenti()
                ->where('tipo_movimento', 'reso')
                ->with(['articolo', 'prodottoFinito'])
                ->get();

            if ($movimentiReso->isNotEmpty()) {
                // Usa i movimenti reso esistenti (manuali o automatici già creati)
                foreach ($movimentiReso as $movimento) {
                    $item = $movimento->getItem();
                    
                    DdtDepositoDettaglio::create([
                        'ddt_deposito_id' => $ddtDeposito->id,
                        'articolo_id' => $movimento->articolo_id,
                        'prodotto_finito_id' => $movimento->prodotto_finito_id,
                        'codice_item' => $item->codice,
                        'descrizione' => $item->descrizione,
                        'quantita' => $movimento->quantita,
                        'valore_unitario' => $movimento->costo_unitario,
                        'valore_totale' => $movimento->costo_totale,
                    ]);
                    
                    $articoliTotali += $movimento->quantita;
                    $valoreTotale += $movimento->costo_totale;
                }
            } else {
                // Se non ci sono movimenti reso, usa gli articoli rimanenti (per reso automatico completo)
                // Aggiungi dettagli per articoli rimanenti
                $articoliRimanenti = $this->getArticoliRimanentiInDeposito($contoDeposito);
                foreach ($articoliRimanenti as $articoloData) {
                    $valoreTotaleRiga = $articoloData['quantita'] * $articoloData['costo_unitario'];
                    
                    DdtDepositoDettaglio::create([
                        'ddt_deposito_id' => $ddtDeposito->id,
                        'articolo_id' => $articoloData['articolo']->id,
                        'codice_item' => $articoloData['articolo']->codice,
                        'descrizione' => $articoloData['articolo']->descrizione,
                        'quantita' => $articoloData['quantita'],
                        'valore_unitario' => $articoloData['costo_unitario'],
                        'valore_totale' => $valoreTotaleRiga,
                    ]);
                    
                    $articoliTotali += $articoloData['quantita'];
                    $valoreTotale += $valoreTotaleRiga;
                }

                // Aggiungi dettagli per PF rimanenti
                $pfRimanenti = $this->getProdottiFinitiRimanentiInDeposito($contoDeposito);
                foreach ($pfRimanenti as $pfData) {
                    DdtDepositoDettaglio::create([
                        'ddt_deposito_id' => $ddtDeposito->id,
                        'prodotto_finito_id' => $pfData['prodotto_finito']->id,
                        'codice_item' => $pfData['prodotto_finito']->codice,
                        'descrizione' => $pfData['prodotto_finito']->descrizione,
                        'quantita' => 1,
                        'valore_unitario' => $pfData['costo_unitario'],
                        'valore_totale' => $pfData['costo_unitario'],
                    ]);
                    
                    $articoliTotali += 1;
                    $valoreTotale += $pfData['costo_unitario'];
                }
            }

            // Aggiorna totali nel DDT
            $ddtDeposito->update([
                'articoli_totali' => $articoliTotali,
                'valore_dichiarato' => $valoreTotale,
            ]);

            // Aggiorna deposito con DDT reso
            $contoDeposito->update(['ddt_reso_id' => $ddtDeposito->id]);
            
            // Aggiorna tutti i movimenti reso con il riferimento al DDT
            // Nota: ddt_id punta a Ddt, ma DdtDeposito è separato
            // Per ora non aggiorniamo ddt_id, ma useremo un approccio alternativo
            // I movimenti saranno collegati al DDT attraverso ddtDeposito->movimenti()
            
            return $ddtDeposito;
        });
    }


    // Metodo generaNumeroDdt() rimosso - ora è nel modello DdtDeposito

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
