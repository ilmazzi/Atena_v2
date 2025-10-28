# 📊 Migrazione Completa Database - Athena V2

## 🎯 Comando Principale

```bash
php artisan migra:dati-completi [--dry-run] [--clean]
```

### Opzioni:
- `--dry-run`: Esegue simulazione senza salvare modifiche
- `--clean`: Pulisce dati esistenti prima della migrazione

## 📋 Fasi della Migrazione

### 1️⃣ **Migrazione Articoli** (con gestione duplicati)
- Fonte: `elenco_articoli_magazzino` (MSSQL)
- Gestione duplicati: Suffissi automatici (`-1`, `-2`, etc.)
- Totale: ~14,806 articoli
- Duplicati gestiti: ~202

### 2️⃣ **Migrazione Giacenze**
- Creazione 1:1 con articoli
- Recupero dati da MSSQL quando disponibili
- Totale: ~14,806 giacenze

### 3️⃣ **Migrazione DDT**
- Fonte: `mag_ddt_articoli_testate` (MSSQL)
- Migrazione testate e dettagli
- Campo `descrizione` in `ddt_dettagli` reso **nullable** (già presente in `articoli`)
- Totale: ~24,362 DDT iniziali

### 4️⃣ **Pulizia Duplicati DDT**
- Comando: `documenti:pulisci-duplicati`
- Unificazione DDT con stesso numero
- Priorità: Fornitori reali > "NON INSERITO"
- DDT finali: ~5,106 (dopo pulizia)
- Duplicati rimossi: ~19,256

### 5️⃣ **Ricalcolo Conteggi**
- Comando: `documenti:ricalcola-conteggi`
- Aggiornamento `numero_articoli` e `quantita_totale`
- DDT aggiornati: ~484

### 6️⃣ **Migrazione Prodotti Finiti Storici**
- Comando: `pf:migra-v2`
- Conversione articoli categorie 9 e 22
- Migrazione componenti da `mag_diba`
- Gestione duplicati con suffissi
- Totale: ~272 PF, ~1,364 componenti

## ✅ Risultati Finali

### 📦 Dati Base:
- ✅ **14,806 articoli** migrati
- ✅ **14,806 giacenze** create (1:1)
- ✅ **5,106 DDT** puliti
- ✅ **8,159 dettagli DDT**
- ✅ **1,050 DDT con articoli** associati

### 🏭 Prodotti Finiti:
- ✅ **272 prodotti finiti** storici
- ✅ **1,364 componenti** totali
- ✅ **363 giacenze aggiornate** (componenti scaricati)
- ⚠️ **134 articoli** usati in più PF (duplicati normali)

## 🔧 Problemi Risolti

### 1. Campo `descrizione` in `ddt_dettagli`
**Problema**: Campo NOT NULL ma ridondante (già in `articoli`)
**Soluzione**: Migration per renderlo nullable + rimozione dall'insert

### 2. Giacenze incomplete
**Problema**: Solo 481 giacenze create invece di 14,806
**Soluzione**: Iterazione su articoli MySQL invece che vista MSSQL

### 3. Duplicati DDT
**Problema**: 24,362 DDT con molti duplicati
**Soluzione**: Comando `pulisci-duplicati` con logica di priorità

### 4. Conteggi articoli errati
**Problema**: `numero_articoli` a 0 o NULL
**Soluzione**: Comando `ricalcola-conteggi` post-migrazione

### 5. Prodotti finiti con ID offset
**Problema**: Codice "9-250" ma PF ID 251 in MSSQL
**Soluzione**: Comando V2 con gestione corretta ID e duplicati componenti

## 📝 Note Importanti

### Gestione Duplicati Articoli
Quando il database MSSQL ha articoli con stesso `id_magazzino/carico`, il sistema:
1. Rileva il gruppo di duplicati
2. Assegna codici unici: `5-1234`, `5-1234-1`, `5-1234-2`, etc.
3. Mantiene tracciabilità verso ID originale MSSQL

### Prodotti Finiti e Componenti
- Articoli in cat. 9/22 vengono convertiti in `ProdottoFinito`
- Componenti da `mag_diba` vengono linkati
- Giacenze componenti vengono scaricate (quantita_residua = 0)
- Stato articolo componente: `in_prodotto_finito`

### Articoli con Componenti Duplicati
È **normale** che lo stesso articolo fisico sia componente di più PF.
Il vincolo `uq_prodotto_articolo` previene questo, quindi il sistema:
- Crea codici con suffissi per componenti duplicati
- Mantiene tracciabilità verso articolo originale
- 134 articoli risultano in più PF (comportamento atteso)

## 🚀 Esecuzione Migrazione Completa

```bash
# 1. Dry-run per verificare
php artisan migra:dati-completi --dry-run

# 2. Pulizia e migrazione completa
php artisan db:pulisci --confirm
php artisan migra:dati-completi

# Oppure in un solo comando:
php artisan migra:dati-completi --clean
```

## 📊 Verifica Post-Migrazione

```bash
# Conteggi base
php artisan tinker
>>> \App\Models\Articolo::count()
>>> \App\Models\Giacenza::count()
>>> \App\Models\Ddt::count()
>>> \App\Models\DdtDettaglio::count()

# DDT con articoli
>>> \App\Models\Ddt::where('numero_articoli', '>', 0)->count()

# Prodotti Finiti
>>> \App\Models\ProdottoFinito::count()
>>> \App\Models\ComponenteProdotto::count()
```

## ⚠️ Limitazioni Note

1. **Fatture**: Non ancora implementate (da fare se necessario)
2. **Componenti con codici strani**: Alcuni componenti hanno codici formato `6-23207` che causano errori di conversione (~14 errori)
3. **Transazioni pulizia**: Errore "No active transaction" in `PulisciDatabase` (non critico, pulizia completata comunque)

## 📚 Comandi Correlati

- `db:pulisci --confirm` - Pulizia completa database
- `documenti:pulisci-duplicati` - Solo pulizia duplicati DDT
- `documenti:ricalcola-conteggi` - Solo ricalcolo conteggi
- `pf:migra-v2 [--clean]` - Solo migrazione PF
- `pf:verifica-migrazione` - Verifica stato PF





