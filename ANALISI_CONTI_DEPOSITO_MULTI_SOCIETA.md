# 📊 Analisi Conti Deposito Multi-Società

## 🎯 Situazione Attuale

### Struttura Sedi (5 sedi):
- **CAVOUR** (ID: 1) - LECCO
- **MONASTERO** (ID: 2) - BELLAGIO  
- **MAZZINI** (ID: 3) - BELLAGIO
- **JOLLY** (ID: 4) - LECCO
- **ROMA** (ID: 5) - ROMA

### Struttura Società (2 società):
1. **De Pascalis s.p.a.** (Società 1)
   - Cavour (Lecco)
   - Jolly (Lecco)
   - Mazzini (Bellagio)
   - Monastero (Bellagio)
   - **Totale: 4 punti vendita**

2. **Luigi De Pascalis** (Società 2)
   - Roma
   - **Totale: 1 punto vendita**

**✅ Nessun problema di condivisione sedi**: Ogni sede appartiene a una sola società

## 🔄 Requisiti Funzionali

### 1. Spostamento Fisico Articoli tra Società
- Quando articoli vanno in conto deposito da Società A → Società B:
  - ❌ NON devono più essere conteggiati nel magazzino Società A
  - ✅ Devono apparire in un "Magazzino Conto Deposito" Società B
  - ✅ Rimangono tracciabili ma "disabilitati" nella vista Società A

### 2. Magazzini Conto Deposito
- Ogni società ha un proprio magazzino conto deposito
- Gli articoli in conto deposito sono visibili solo alla società destinataria
- La società mittente vede gli articoli ma disabilitati (non contati nel totale)

### 3. Numerazione Progressiva per Società
- DDT Invio: `DEP-SOCIETA-YYYY-NNNN` (es: DEP-A-2025-0001)
- DDT Reso: `RES-SOCIETA-YYYY-NNNN` (es: RES-A-2025-0001)
- Conti Deposito: `CD-SOCIETA-YYYY-NNNN` (es: CD-A-2025-0001)

### 4. Tracking Vendite
- Quando articolo venduto → tracciare riferimento al DDT invio originale
- Collegamento Fattura → DDT Invio → Articolo

### 5. Notifiche
- Vendita: Notifica società mittente con DDT/fattura
- Reso: Notifica società mittente con DDT reso
- Canali: Email + In-App (notifiche Laravel)

### 6. DDT Reso Parziali/Totali
- ✅ Già implementato con movimenti reso manuali/automatici

## 🏗️ Proposta Architettura

### FASE 1: Aggiunta Struttura Società

#### Tabella `societa`
```sql
- id
- codice (es: DEP, LDP) 
- ragione_sociale (De Pascalis s.p.a. / Luigi De Pascalis)
- partita_iva
- email_notifiche (array JSON: email per notifiche)
- configurazione (JSON: prefisso numerazione, etc.)
- attivo
```

#### Modifiche `sedi`
- Aggiungere campo `societa_id` (FK NOT NULL)
- Mapping diretto: ogni sede appartiene a una sola società
- Indice per performance query filtri per società

### FASE 2: Magazzini Conto Deposito

#### Nuova categoria merceologica "CONTO DEPOSITO"
- Creata automaticamente per ogni società
- Es: "CD-SocietàA", "CD-SocietàB", "CD-SocietàC"
- Nascosta nelle viste normali magazzino

#### Modifiche `articoli`
- Campo `magazzino_conto_deposito_id` (FK nullable)
- Se valorizzato → articolo è in conto deposito
- Non conteggiato nel magazzino normale

### FASE 3: Spostamento Articoli

#### Quando articolo va in conto deposito:
1. **Società Mittente**:
   - `articoli.sede_id` rimane = sede_mittente_id
   - `articoli.magazzino_conto_deposito_id` = magazzino_CD_societa_destinataria
   - `articoli.conto_deposito_corrente_id` = conto_deposito.id
   - **VISUALIZZAZIONE**: Articolo visibile ma:
     - 🔴 Righe disabilitate/grigie
     - ⚠️ Badge "In Conto Deposito presso [Società B]"
     - 📉 **NON conteggiato in statistiche magazzino**

2. **Società Destinataria**:
   - Crea "copia virtuale" in magazzino conto deposito
   - OPPURE: Filtro in vista che mostra articoli di altre società in CD
   - Visibile nel "Magazzino Conto Deposito" della società

### FASE 4: Numerazione Progressiva per Società

#### Modifiche Modelli:
- `ContoDeposito::generaCodice()` → aggiunge prefisso società
- `DdtDeposito::generaNumeroDdt()` → aggiunge prefisso società
- Sistema tiene traccia ultimo numero per società/anno

### FASE 5: Notifiche

#### Sistema Notifiche:
- **Laravel Notifications** (email + database)
- **Channel Email**: Invia email con allegati DDT/Fattura
- **Channel Database**: Notifiche in-app
- **Eventi**:
  - `ArticoloVendutoInDeposito`
  - `ArticoloRestituitoDaDeposito`
  - `DdtResoGenerato`

### FASE 6: Tracking DDT Invio nelle Vendite

#### Modifiche `fatture`:
- Campo `ddt_deposito_invio_id` (FK nullable)
- Collegamento: Fattura → DDT Deposito Invio → Conto Deposito

## 🤔 Domande da Risolvere

### Q1: Come gestire articoli in conto deposito nella vista societa mittente?
**Opzione A**: Mostrare righe disabilitate (stile grigio, non cliccabili)
**Opzione B**: Separare in tab "Articoli in Conto Deposito" (solo vista)
**Opzione C**: Filter avanzato "Escludi articoli in CD"

### Q2: Dove visualizzare articoli per società destinataria?
**Opzione A**: Magazzino separato "Conto Deposito" nella stessa vista
**Opzione B**: Vista dedicata "Gestione Conti Deposito" (già esiste)
**Opzione C**: Entrambe (filtro in magazzino + vista dedicata)

### Q3: Storage articoli in CD per società destinataria?
**Opzione A**: Non duplicare, solo filtro vista con `conto_deposito_corrente_id`
**Opzione B**: Creare "copia logica" in tabella `articoli_conto_deposito`
**Opzione C**: Usare `articoli.sede_id` temporaneo + flag

**Raccomandazione**: Opzione A - più semplice, meno duplicazione

## 📋 Piano Implementazione

### STEP 1: Struttura Base Società
- [ ] Creare tabella `societa`
- [ ] Aggiungere campo `societa_id` in `sedi` (ogni sede appartiene a una società)
- [ ] Seeder per 2 società:
  - De Pascalis s.p.a. (ID: 1)
  - Luigi De Pascalis (ID: 2)
- [ ] Mappare sedi esistenti:
  - Cavour, Jolly, Mazzini, Monastero → Società 1
  - Roma → Società 2

### STEP 2: Magazzini Conto Deposito
- [ ] Creare categoria "Conto Deposito" per ogni società
- [ ] Sistema auto-generazione magazzini CD

### STEP 3: Logica Spostamento Articoli
- [ ] Modificare `ContoDepositoService::inviaArticoloInDeposito()`
- [ ] Aggiornare `sede_id` e `magazzino_conto_deposito_id`
- [ ] Filtri vista per escludere articoli in CD dal conteggio

### STEP 4: Numerazione Progressiva
- [ ] Modificare `ContoDeposito::generaCodice()`
- [ ] Modificare `DdtDeposito::generaNumeroDdt()`
- [ ] Tabella/seeder per tracciare ultimi numeri per società

### STEP 5: Notifiche
- [ ] Creare Notifications (Email + Database)
- [ ] Eventi da notificare
- [ ] Template email
- [ ] Dashboard notifiche in-app

### STEP 6: Tracking DDT Invio
- [ ] Aggiungere campo `ddt_deposito_invio_id` in `fatture`
- [ ] Aggiornare vendita conto deposito per collegare DDT

### STEP 7: UI/UX
- [ ] Vista articoli: gestire righe "In Conto Deposito" (disabilitate)
- [ ] Vista società destinataria: filtro "Articoli in Conto Deposito"
- [ ] Dashboard notifiche
- [ ] Badge e indicatori visivi

## ⚠️ Note Importanti

1. **Mapping Società-Sedi**: Ogni sede appartiene a una sola società (no conflitti)
2. **Migrazione dati esistenti**: 
   - Mappare tutti i conti deposito esistenti alle società basandosi su `sede_mittente_id`
   - Identificare società mittente dal mapping sedi
3. **Performance**: Filtri complessi su articoli in CD devono essere ottimizzati
4. **Sicurezza**: Filtrare automaticamente per società dell'utente (basato su `sedi_permesse`)
5. **Rilevamento Società Utente**: Derivare da `sedi_permesse` dell'utente → tutte le sedi devono appartenere alla stessa società

## 💡 Raccomandazioni

1. **Implementazione graduale**: STEP 1-3 prima, poi STEP 4-7
2. **Filtri vista**: Usare scope Laravel per articoli in CD
3. **Notifiche asincrone**: Usare queue Laravel per email
4. **Caching**: Cache per statistiche magazzino (escludere CD)

---

**Prossimo passo**: Discutere domande aperte e poi procedere con implementazione

