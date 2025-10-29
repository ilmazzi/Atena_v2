<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Meta & titolo (usa $title passato o fallback) --}}
    @include('layouts.partials/title-meta', ['title' => $title ?? 'App'])
    
    {{-- Meta per session timeout --}}
    @auth
        <meta name="user-authenticated" content="true">
        <meta name="session-lifetime" content="{{ config('session.lifetime') }}">
    @endauth

    {{-- Stili specifici delle pagine Blade tradizionali (facoltativo) --}}
    @yield('css')

    {{-- CSS globali del layout --}}
    @include('layouts.partials/head-css')

    {{-- Livewire 3: stili (consigliato in <head>) --}}
    @livewireStyles
</head>
<body>
<div class="wrapper">
    {{-- Topbar & nav principali --}}
    @include('layouts.partials/topbar', ['title' => $title ?? ''])
    @include('layouts.partials/main-nav')

    <div class="page-content">
        <div class="container-fluid">
            {{-- 
                Supporto doppio:
                - Page Component Livewire 3 con @layout → usa $slot
                - Pagine Blade tradizionali con @extends → usa @yield('content')
            --}}
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </div>

        @include('layouts.partials/footer')
    </div>
</div>

{{-- Sidebar destra e script comuni --}}
@include('layouts.partials/right-sidebar')
@include('layouts.partials/footer-scripts')

{{-- Vite asset globali del layout --}}
@vite(['resources/js/app.js','resources/js/layout.js'])

{{-- Session timeout manager --}}
@auth
<script>
    // Passa config sessione a JavaScript
    window.sessionLifetime = {{ config('session.lifetime') }};
</script>
@vite(['resources/js/session-timeout.js'])
@endauth

{{-- Livewire 3: script di configurazione --}}
@livewireScripts

{{-- Back to Top Button --}}
<button id="backToTopBtn" 
        class="btn btn-primary rounded-circle shadow-lg position-fixed bottom-0 end-0 m-4" 
        style="width: 50px; height: 50px; z-index: 1000; display: none; border: none;"
        title="Torna su">
    <iconify-icon icon="solar:double-alt-arrow-up-bold" style="font-size: 24px;"></iconify-icon>
</button>

<script>
    // Back to Top Button
    (function() {
        const backToTopBtn = document.getElementById('backToTopBtn');
        
        if (!backToTopBtn) return;
        
        // Mostra/nascondi il pulsante in base allo scroll
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });
        
        // Scroll smooth quando si clicca
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    })();
</script>
</body>
</html>
