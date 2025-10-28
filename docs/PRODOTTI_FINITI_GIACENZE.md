# Gestione Giacenze nei Prodotti Finiti

## 📦 Come Funziona

### 🆕 Creazione Prodotto Finito

Quando crei un nuovo prodotto finito:

1. **Verifica Disponibilità** - Controlla che tutti i componenti abbiano giacenza sufficiente
2. **Crea Record** - Crea il record `prodotti_finiti` con stato `in_lavorazione`
3. **Registra Componenti** - Salva i componenti in `componenti_prodotto`
4. **Scarica Giacenze** - Decrementa la giacenza di ogni componente
5. **Crea Movimentazione** - Registra lo scarico in `movimentazioni` e `movimentazioni_dettagli`

**Esempio:**
- Articolo 5-97 ha giacenza = 1
- Crei prodotto finito usando 1x articolo 5-97
- ✅ Articolo 5-97 ora ha giacenza = 0 (scaricato per assemblaggio)

---

### ✏️ Modifica Prodotto Finito

Quando modifichi un prodotto finito esistente:

1. **Carica Prodotto** - Recupera il prodotto con i suoi componenti attuali
2. **Ripristina Giacenze Vecchie** ⚠️ IMPORTANTE - Prima ripristina le giacenze dei componenti esistenti
3. **Verifica Disponibilità Nuovi** - Controlla che i nuovi componenti abbiano giacenza sufficiente
4. **Aggiorna Dati** - Aggiorna descrizione, costi, ecc.
5. **Elimina Componenti Vecchi** - Rimuove i record vecchi da `componenti_prodotto`
6. **Registra Nuovi Componenti** - Salva i nuovi componenti
7. **Scarica Nuove Giacenze** - Decrementa la giacenza dei nuovi componenti

**Esempio:**
- Prodotto finito esistente usa 1x articolo 5-97 (giacenza attuale = 0)
- Vuoi modificarlo mantenendo 1x articolo 5-97
- ✅ Sistema ripristina giacenza 5-97 a 1
- ✅ Verifica disponibilità (1 disponibile, 1 richiesto → OK)
- ✅ Scarica nuovamente 1x articolo 5-97
- ✅ Giacenza finale = 0

**⚠️ Ordine Cruciale:**
L'ordine delle operazioni è **fondamentale**:
- ✅ CORRETTO: Ripristina → Verifica → Scarica
- ❌ SBAGLIATO: Verifica → Ripristina → Scarica (darebbe errore "giacenza insufficiente")

---

### ✅ Completamento Assemblaggio

Quando completi l'assemblaggio di un prodotto finito:

1. **Genera Codice Articolo** - Crea un nuovo codice per l'articolo finale
2. **Crea Articolo Finale** - Inserisce il nuovo articolo in `articoli`
3. **Crea Giacenza Iniziale** - Imposta giacenza = 1 per il nuovo articolo
4. **Aggiorna Prodotto** - Cambia stato a `completato` e collega l'articolo risultante
5. **Registra Completamento** - Salva data e utente che ha completato

**Risultato:**
- I componenti rimangono scaricati (giacenza = 0)
- Hai un nuovo articolo assemblato con giacenza = 1

---

## 🔄 Movimentazioni Create

### Durante Scarico Componente
```
Tabella: movimentazioni
- numero_documento: PF-{id}-{timestamp}-{counter}
- causale: 'assemblaggio_prodotto_finito'
- stato: 'confermata'

Tabella: movimentazioni_dettagli
- articolo_id: ID del componente scaricato
- quantita: Quantità scaricata
```

### Durante Ripristino (Modifica)
```
Tabella: movimentazioni
- numero_documento: RIP-{timestamp}-{counter}
- causale: 'annullamento_assemblaggio'
- stato: 'confermata'

Tabella: movimentazioni_dettagli
- articolo_id: ID del componente ripristinato
- quantita: Quantità ripristinata
```

---

## 🎯 Best Practices

### ✅ DO:
- Controlla sempre la giacenza disponibile prima di selezionare componenti
- Usa il filtro "Solo disponibili" per vedere solo articoli utilizzabili
- Verifica i badge colorati (🟢 Verde = disponibile, 🔴 Rosso = non disponibile)

### ❌ DON'T:
- Non tentare di aggiungere componenti con giacenza = 0
- Non modificare direttamente le tabelle `giacenze` senza passare dal service
- Non bypassare il sistema di movimentazioni

---

## 🐛 Debug

Se riscontri errori "Giacenza insufficiente":

1. **Verifica Log** - Controlla `storage/logs/laravel.log`
2. **Controlla Giacenza** - Verifica in `giacenze` la quantità residua
3. **Controlla Componenti** - Verifica in `componenti_prodotto` se l'articolo è già usato
4. **Verifica Movimentazioni** - Controlla in `movimentazioni` gli scarichi precedenti

**Log Utili:**
```
🔄 Inizio aggiornamento prodotto finito
♻️ Ripristino giacenze componenti esistenti
  ↩️ Ripristino articolo_id: X, quantita: Y
✅ Verifica disponibilità nuovi componenti
📦 Scarico componente articolo_id: X, quantita: Y
```

---

## 📊 Query Utili

### Verifica giacenze di un articolo
```sql
SELECT a.codice, g.quantita_residua, g.sede_id
FROM articoli a
JOIN giacenze g ON a.id = g.articolo_id
WHERE a.codice = '5-97';
```

### Verifica componenti di un prodotto finito
```sql
SELECT pf.codice, a.codice as componente_codice, cp.quantita
FROM prodotti_finiti pf
JOIN componenti_prodotto cp ON pf.id = cp.prodotto_finito_id
JOIN articoli a ON cp.articolo_id = a.id
WHERE pf.id = 1;
```

### Verifica movimentazioni di un articolo
```sql
SELECT m.numero_documento, m.causale, md.quantita, m.created_at
FROM movimentazioni m
JOIN movimentazioni_dettagli md ON m.id = md.movimentazione_id
WHERE md.articolo_id = (SELECT id FROM articoli WHERE codice = '5-97')
ORDER BY m.created_at DESC;
```





