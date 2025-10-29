<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ route('articoli.index') }}" class="logo-dark">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
            <img src="/images/logo-dark.png" class="logo-lg" alt="logo dark">
        </a>

        <a href="{{ route('articoli.index') }}" class="logo-light">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
            <img src="/images/logo-light.png" class="logo-lg" alt="logo light">
        </a>
    </div>

    <!-- Menu Toggle Button (sm-hover) -->
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">

            {{-- ============================================ --}}
            {{-- MAGAZZINO --}}
            {{-- ============================================ --}}
            <li class="menu-title">Magazzino</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('articoli.index') }}">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Articoli</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('magazzino.scarico') }}">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Scarico Magazzino</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('magazzino.scanner') }}">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:scanner-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Scanner Inventario</span>
                </a>
            </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('vetrine.index') }}">
                     <span class="nav-icon">
                    <iconify-icon icon="solar:shop-bold-duotone"></iconify-icon>
                     </span>
                <span class="nav-text">Vetrine</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-arrow" href="#sidebarContiDeposito" data-bs-toggle="collapse" role="button"
               aria-expanded="false" aria-controls="sidebarContiDeposito">
                     <span class="nav-icon">
                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                     </span>
                <span class="nav-text">Conti Deposito</span>
            </a>
            <div class="collapse" id="sidebarContiDeposito">
                <ul class="nav sub-navbar-nav">
                    <li class="sub-nav-item">
                        <a class="sub-nav-link" href="{{ route('conti-deposito.index') }}">
                            <iconify-icon icon="solar:list-bold" class="me-1"></iconify-icon>
                            Lista Depositi
                        </a>
                    </li>
                    <li class="sub-nav-item">
                        <a class="sub-nav-link" href="{{ route('conti-deposito.resi') }}">
                            <iconify-icon icon="solar:import-bold" class="me-1"></iconify-icon>
                            Gestione Resi
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('movimentazioni-interne.index') }}">
                     <span class="nav-icon">
                    <iconify-icon icon="solar:transfer-horizontal-bold-duotone"></iconify-icon>
                     </span>
                <span class="nav-text">Movimentazioni Interne</span>
            </a>
        </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarAcquisti" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarAcquisti">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:cart-large-2-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Acquisti</span>
                </a>
                <div class="collapse" id="sidebarAcquisti">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('documenti-acquisto.nuovo') }}">Nuovo Documento</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('documenti-acquisto.index') }}">Elenco Documenti</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarGiacenze" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarGiacenze">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:calculator-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Giacenze</span>
                </a>
                <div class="collapse" id="sidebarGiacenze">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Per Sede</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Per Ubicazione</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Inventario</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarProduzione" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarProduzione">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Produzione</span>
                </a>
                <div class="collapse" id="sidebarProduzione">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('prodotti-finiti.index') }}">Prodotti Finiti</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('prodotti-finiti.nuovo') }}">Nuovo Prodotto</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- ============================================ --}}
            {{-- DOCUMENTI --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Documenti</li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarOCR" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarOCR">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">OCR</span>
                </a>
                <div class="collapse" id="sidebarOCR">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('ocr.dashboard') }}">Dashboard OCR</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('ocr.upload') }}">Carica PDF</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Carica Batch</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('ocr.index') }}">Documenti</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarDDT" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarDDT">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:delivery-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">DDT</span>
                </a>
                <div class="collapse" id="sidebarDDT">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Elenco DDT</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Nuovo DDT</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarFatture" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarFatture">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:bill-list-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Fatture</span>
                </a>
                <div class="collapse" id="sidebarFatture">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Elenco Fatture</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Nuova Fattura</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- ============================================ --}}
            {{-- ANAGRAFE --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Anagrafe</li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Clienti</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                         <span class="nav-icon">
                              <iconify-icon icon="solar:shop-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Fornitori</span>
                </a>
            </li>

            {{-- ============================================ --}}
            {{-- GESTIONE --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Gestione</li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarGestione" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarGestione">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Configurazione</span>
                </a>
                <div class="collapse" id="sidebarGestione">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('gestione.societa') }}">
                                <iconify-icon icon="solar:buildings-2-bold" class="me-1"></iconify-icon>
                                Società
                            </a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('gestione.sedi') }}">
                                <iconify-icon icon="solar:map-point-bold" class="me-1"></iconify-icon>
                                Sedi
                            </a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('gestione.magazzini') }}">
                                <iconify-icon icon="solar:box-bold" class="me-1"></iconify-icon>
                                Magazzini
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- ============================================ --}}
            {{-- STAMPANTI --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Stampanti</li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('stampanti.index') }}">
                    <span class="nav-icon">
                   <iconify-icon icon="solar:printer-bold-duotone"></iconify-icon>
                    </span>
               <span class="nav-text">Gestione Stampanti</span>
           </a>
       </li>
       <li class="menu-title mt-2">Inventario</li>
       <li class="nav-item">
           <a class="nav-link" href="{{ route('inventario.dashboard') }}">
                    <span class="nav-icon">
                   <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                    </span>
               <span class="nav-text">Dashboard Inventario</span>
           </a>
       </li>
       <li class="nav-item">
           <a class="nav-link" href="{{ route('inventario.sessioni') }}">
                    <span class="nav-icon">
                   <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                    </span>
               <span class="nav-text">Sessioni Inventario</span>
           </a>
       </li>
       <li class="nav-item">
           <a class="nav-link" href="{{ route('inventario.monitor') }}">
                    <span class="nav-icon">
                   <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                    </span>
               <span class="nav-text">Monitor Inventario</span>
           </a>
       </li>
       <li class="nav-item">
           <a class="nav-link" href="{{ route('inventario.storico') }}">
                    <span class="nav-icon">
                   <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                    </span>
               <span class="nav-text">Storico Articoli</span>
           </a>
       </li>

            {{-- ============================================ --}}
            {{-- NOTIFICHE --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Notifiche</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifiche.index') }}">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:bell-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Dashboard Notifiche</span>
                </a>
            </li>

            {{-- ============================================ --}}
            {{-- SISTEMA --}}
            {{-- ============================================ --}}
            <li class="menu-title mt-2">Sistema</li>

            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarUtenti" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarUtenti">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:user-speak-rounded-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Utenti</span>
                </a>
                <div class="collapse" id="sidebarUtenti">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Ruoli</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="#">Permessi</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                         <span class="nav-icon">
                        <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                         </span>
                    <span class="nav-text">Impostazioni</span>
                </a>
            </li>

        </ul>
    </div>
</div>
