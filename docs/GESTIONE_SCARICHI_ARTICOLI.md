# Gestione Scarichi Articoli

## 📋 **REGOLE FONDAMENTALI:**

### ❌ **MAI SALVARE/MOSTRARE:**
- ❌ `data_scarico` - NON ESISTE
- ❌ `data_vendita` - NON ESISTE  
- ❌ Qualsiasi data relativa allo scarico

### ✅ **COSA SALVARE:**
- ✅ `stato_articolo` - ENUM che indica lo stato
- ✅ `scarico_id` - Link opzionale a tabella scarichi (solo per tracciabilità interna)

---

## 📊 **STATI ARTICOLO:**

```php
'stato_articolo' => [
    'disponibile',        // In magazzino, utilizzabile
    'in_prodotto_finito', // Usato in PF, giacenza = 0, ma tracciabile
    'scaricato',          // Scaricato singolarmente (vendita/furto/danni)
    'scaricato_in_pf',    // Scaricato come parte di un PF venduto
]
```

---

## 🔄 **FLUSSO COMPLETO:**

### 1️⃣ **CREAZIONE PRODOTTO FINITO:**

```
Articolo 5-234 (Mag. 2 - Oro):
├─ Prima:  giacenza_residua = 1, stato_articolo = 'disponibile'
├─ Azione: Crea PF-9-00272 usando 5-234
├─ Sistema:
│  ├─ Scarica giacenza: giacenza_residua = 0
│  └─ Aggiorna stato: stato_articolo = 'in_prodotto_finito'
└─ Dopo:  giacenza_residua = 0, stato_articolo = 'in_prodotto_finito'

Visualizzazione nella lista articoli:
├─ Giacenza mostrata: 1 (quantità originale, non residua)
├─ Badge colore: Arancione (warning)
└─ Badge testo: "In un PF" (cliccabile → dettaglio PF)
```

### 2️⃣ **MODIFICA PRODOTTO FINITO:**

```
Modifica PF-9-00272:
├─ Sistema:
│  ├─ 1. Ripristina componenti vecchi:
│  │   ├─ giacenza_residua = 1
│  │   └─ stato_articolo = 'disponibile'
│  ├─ 2. Verifica disponibilità nuovi componenti
│  ├─ 3. Scarica nuovi componenti:
│  │   ├─ giacenza_residua = 0
│  │   └─ stato_articolo = 'in_prodotto_finito'
└─ Risultato: Componenti vecchi liberati, nuovi bloccati
```

### 3️⃣ **SCARICO PRODOTTO FINITO (Futuro):**

```
Scarica PF-9-00272:
├─ Articolo PF-9-00272:
│  ├─ giacenza_residua = 0
│  └─ stato_articolo = 'scaricato'
│
├─ Componente 5-234:
│  ├─ giacenza_residua = 0 (già scaricato)
│  └─ stato_articolo = 'scaricato_in_pf' (aggiorna da 'in_prodotto_finito')
│
└─ Badge mostrato: "Scaricato in PF-9-00272" (grigio)
```

---

## 🎨 **VISUALIZZAZIONE NELLA LISTA ARTICOLI:**

### **Articolo Disponibile:**
```
Giacenza: 5 [Badge Verde]
Stato: "Giacente" [Badge Verde]
```

### **Articolo in Prodotto Finito:**
```
Giacenza: 1 [Badge Arancione] ← Mostra quantità originale
Stato: "In un PF" [Badge Arancione cliccabile]
Tooltip: "Usato in: PF-9-00272 - Collana oro..."
```

### **Articolo Scaricato Singolarmente:**
```
Giacenza: 0 [Badge Rosso]
Stato: "Scaricato" [Badge Rosso]
```

### **Articolo Scaricato in PF (Futuro):**
```
Giacenza: 0 [Badge Grigio]
Stato: "Scaricato in PF-9-00272" [Badge Grigio]
Tooltip: "Prodotto finito scaricato"
```

---

## 📈 **REPORT E STATISTICHE:**

### **Valore Magazzino per Categoria:**

```php
Magazzino 2 (Oro):
├─ Disponibili: 50 pezzi × €500 = €25,000
├─ In lavorazione: 5 pezzi × €500 = €2,500 (in PF)
├─ Totale valore: €27,500
└─ Note: I pezzi in lavorazione sono ancora "nostri" fino allo scarico del PF
```

### **Scarichi per Categoria:**

```php
// Quanti articoli di oro ho scaricato?
$oroScaricato = Articolo::where('categoria_merceologica_id', 2)
    ->whereIn('stato_articolo', ['scaricato', 'scaricato_in_pf'])
    ->count();

// Dettaglio:
// - 10 pezzi scaricati singolarmente
// - 5 pezzi scaricati come componenti di PF
// Totale: 15 pezzi oro scaricati
```

### **Articoli in Lavorazione:**

```php
// Quanti articoli sono bloccati in prodotti finiti?
$inLavorazione = Articolo::where('stato_articolo', 'in_prodotto_finito')
    ->with('prodotto_finito')
    ->get();
```

---

## ✅ **VANTAGGI DELLA SOLUZIONE:**

1. **NO Confusione Date:**
   - ❌ Nessuna data_scarico da gestire/nascondere
   - ✅ Solo stati chiari e descrittivi

2. **Tracciabilità Completa:**
   - ✅ Sai sempre dove sono i componenti
   - ✅ Puoi tracciare l'intera filiera: componente → PF → scarico PF

3. **Report Accurati:**
   - ✅ Valore per categoria sempre corretto
   - ✅ Scarichi tracciati per origine (singolo o in PF)

4. **Inventario Semplice:**
   - ✅ Conti solo articoli "fisici" disponibili
   - ✅ Non conti componenti già assemblati
   - ✅ Sistema riflette scaffale fisico

5. **UX Chiara:**
   - ✅ Badge colorati immediati
   - ✅ Nessuna data confusa
   - ✅ Click per dettagli quando serve

---

## 🔐 **SICUREZZA E INTEGRITÀ:**

### **Vincoli:**
- ✅ Un articolo `in_prodotto_finito` DEVE avere `prodotto_finito_id` popolato
- ✅ Un articolo `scaricato_in_pf` DEVE avere `scarico_id` popolato
- ✅ Giacenza residua = 0 per articoli non disponibili

### **Controlli:**
- ✅ Verifica disponibilità prima di assemblare
- ✅ Ripristino automatico se annulli/modifichi PF
- ✅ Aggiornamento cascata stati quando scarichi PF

---

## 📝 **TODO FUTURI:**

- [ ] Implementare scarico PF (aggiorna componenti a `scaricato_in_pf`)
- [ ] Report "Scarichi per categoria merceologica"
- [ ] Dashboard "Valore bloccato in lavorazione"
- [ ] Export Excel con stati articoli
- [ ] Filtro avanzato per `stato_articolo`

---

**Data creazione:** 17 Ottobre 2025  
**Versione:** 1.0  
**Autore:** Sistema Athena v2





