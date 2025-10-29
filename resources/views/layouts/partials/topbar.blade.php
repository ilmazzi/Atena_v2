<header class="topbar">
    <div class="container-fluid">
        <div class="navbar-header">
            <div class="d-flex align-items-center">
                <!-- Menu Toggle Button -->
                <div class="topbar-item">
                    <button type="button" class="button-toggle-menu me-2">
                        <iconify-icon icon="solar:hamburger-menu-broken" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>

                <!-- Menu Toggle Button -->
                <div class="topbar-item">
                    <h4 class="fw-bold topbar-button pe-none text-uppercase mb-0">{{ $title ?? 'Larkon' }}</h4>
                </div>
            </div>

            <div class="d-flex align-items-center gap-1">

                <!-- Theme Color (Light/Dark) -->
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="light-dark-mode">
                        <iconify-icon icon="solar:moon-bold-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>


                <!-- Theme Setting -->
                <div class="topbar-item d-none d-md-flex">
                    <button type="button" class="topbar-button" id="theme-settings-btn" data-bs-toggle="offcanvas"
                            data-bs-target="#theme-settings-offcanvas" aria-controls="theme-settings-offcanvas">
                        <iconify-icon icon="solar:settings-bold-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>


                <!-- Notifiche -->
                <div class="dropdown topbar-item">
                    <a type="button" class="topbar-button position-relative" 
                       href="{{ route('notifiche.index') }}"
                       title="Notifiche">
                        <iconify-icon icon="solar:bell-bold-duotone" class="fs-24 align-middle"></iconify-icon>
                        @livewire('badge-notifiche')
                    </a>
                </div>

                <!-- User -->
                @auth
                <div class="dropdown topbar-item">
                    <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                  <div class="avatar-sm rounded-circle bg-primary d-flex align-items-center justify-content-center">
                                      <span class="text-white fw-bold">
                                          {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                      </span>
                                  </div>
                              </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">
                            <iconify-icon icon="solar:user-bold" class="me-1"></iconify-icon>
                            {{ auth()->user()->name ?? 'Utente' }}
                        </h6>
                        <div class="px-3 mb-2">
                            <small class="text-muted">{{ auth()->user()->email ?? '' }}</small>
                        </div>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <iconify-icon icon="solar:logout-2-bold" class="me-1"></iconify-icon>
                            <span class="align-middle">Logout</span>
                        </a>
                        
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
                @endauth

            </div>
        </div>
    </div>
</header>

