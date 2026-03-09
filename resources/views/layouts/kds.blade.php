<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#18181b]">
<head>
    <meta charset="UTF-8">
    {{-- Viewport travado e ajustado para áreas seguras (notch do iPhone) --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Manifest & Meta Tags --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#18181b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Junior Bar">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">

    @php
        $nomeBar = \App\Models\Configuracao::valor('nome_restaurante', 'Junior BAR');
        $slaAmarelo = (int) \App\Models\Configuracao::valor('tempo_alerta_amarelo', '10');
        $slaVermelho = (int) \App\Models\Configuracao::valor('tempo_alerta_vermelho', '20');
        $wifiSsid = \App\Models\Configuracao::valor('wifi_ssid', 'Não Configurado');
        $wifiPass = \App\Models\Configuracao::valor('wifi_senha', '');
        $preset = config('app.country_preset', \App\Models\Configuracao::countryPreset());
    @endphp

    <title>@yield('title') | {{ $nomeBar }}</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

    {{-- Configuração Global JS --}}
    <script>
        window.AppConfig = {
            socketUrl: "{{ request()->getSchemeAndHttpHost() }}",
            timezone: "{{ config('app.timezone') }}",
            nomeBar: @json($nomeBar),
            slaAmareloSec: {{ $slaAmarelo * 60 }},
            slaVermelhoSec: {{ $slaVermelho * 60 }},
            wifiSsid: @json($wifiSsid),
            wifiPass: @json($wifiPass),
            // i18n
            locale: @json($preset['locale'] ?? 'pt'),
            dateLocale: @json($preset['date_locale'] ?? 'pt-PT'),
            currencySymbol: @json($preset['currency_symbol'] ?? '€'),
            currencyBefore: @json($preset['currency_before'] ?? true),
            decimalSep: @json($preset['decimal_sep'] ?? ','),
            thousandsSep: @json($preset['thousands_sep'] ?? '.')
        };

        // Traduções carregadas do backend para uso no JS
        window.KDS_LANG = @json(__('kds'));
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class') font-sans antialiased text-gray-100 bg-[#0c0c0e] selection:bg-[#7ed957] selection:text-black overflow-hidden">

    {{-- WRAPPER FLEXBOX — Header + Main dividem 100dvh sem conflitos --}}
    <div class="flex flex-col bg-[#18181b] safe-area-pt" style="height: 100dvh">

        {{-- HEADER PRINCIPAL — Branding + navegação apenas no Gerente --}}
        <header class="h-14 bg-[#18181b] border-b border-gray-800/50 flex items-center justify-between px-5 md:px-6 shrink-0">

            {{-- Logo — Primeira palavra em verde, resto em cinza claro --}}
            @php
                $nomeParts = explode(' ', $nomeBar);
                $nomeFirst = $nomeParts[0] ?? '';
                $nomeRest = implode(' ', array_slice($nomeParts, 1));
            @endphp
            <div class="kds-font-ui tracking-tight font-black uppercase flex items-baseline gap-1.5">
                <span class="text-xl text-[#7ed957]">{{ $nomeFirst }}</span>
                @if($nomeRest)
                <span class="text-sm text-gray-400 font-bold">{{ $nomeRest }}</span>
                @endif
            </div>

            {{-- Navegação — Apenas visível na rota Gerente --}}
            @if(request()->is('gerente*'))
            <nav class="flex items-center gap-1 bg-[#0c0c0e] p-1.5 rounded-xl border border-gray-800">
                <a href="/admin" class="p-2 rounded-lg text-gray-500 hover:text-white hover:bg-[#2a2a30] transition-all active:scale-95" title="Admin">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                </a>
                <a href="/garcom" target="_blank" class="p-2 rounded-lg text-gray-500 hover:text-[#7ed957] hover:bg-[#18181b] transition-all active:scale-95" title="{{ __('kds.waiter') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </a>
                <a href="/cozinha" target="_blank" class="p-2 rounded-lg text-gray-500 hover:text-[#7ed957] hover:bg-[#18181b] transition-all active:scale-95" title="{{ __('kds.kitchen') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path></svg>
                </a>
                <div class="w-px h-5 bg-gray-700 mx-0.5"></div>
                <form method="POST" action="{{ route('logout') }}" class="flex">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg text-gray-500 hover:text-[#efa324] hover:bg-[#efa324]/10 transition-all active:scale-95" title="{{ __('kds.logout') }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </form>
            </nav>
            @endif

        </header>

        {{-- Conteúdo Principal — Ocupa todo o espaço restante --}}
        <main class="flex-1 bg-[#0c0c0e] overflow-hidden relative">
            @yield('content')
        </main>

    </div>

    @stack('scripts')

    {{-- Banner de Instalação PWA (só aparece no browser, desaparece quando instalado) --}}
    <div id="pwa-install-banner" style="display:none;" class="fixed bottom-0 left-0 w-full z-[200] p-4 bg-[#18181b] border-t border-gray-700 shadow-[0_-10px_40px_rgba(0,0,0,0.5)] safe-area-pb">
        <div class="max-w-lg mx-auto flex items-center gap-4">
            <img src="/logo.png" alt="Logo" class="w-12 h-12 rounded-xl shrink-0">
            <div class="flex-1 min-w-0">
                <p class="text-white font-black text-sm leading-tight">{{ __('kds.install') }} {{ $nomeBar }}</p>
                <p id="pwa-install-hint" class="text-gray-400 text-[11px] font-medium mt-0.5 leading-tight"></p>
            </div>
            <button id="pwa-install-btn" class="shrink-0 bg-[#7ed957] text-black font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl shadow-lg shadow-[#7ed957]/20 active:scale-95 transition-all">
                {{ __('kds.install') }}
            </button>
            <button id="pwa-dismiss-btn" class="shrink-0 text-gray-500 hover:text-white p-2 transition-colors" title="{{ __('kds.close') }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Service Worker + PWA Install Logic --}}
    <script>
        // Registar Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js').catch(() => {});
            });
        }

        // PWA Install Prompt
        (function() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            // Se já está instalado (modo standalone), não mostra o banner
            if (isStandalone) return;

            // Se já dispensou hoje, não mostra
            const dismissed = localStorage.getItem('pwa-dismiss');
            if (dismissed && (Date.now() - parseInt(dismissed)) < 86400000) return;

            const banner = document.getElementById('pwa-install-banner');
            const installBtn = document.getElementById('pwa-install-btn');
            const dismissBtn = document.getElementById('pwa-dismiss-btn');
            const hint = document.getElementById('pwa-install-hint');
            let deferredPrompt = null;

            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            const lang = window.KDS_LANG || {};

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                hint.textContent = lang.pwa_add_home || 'Tap to add to home screen';
                banner.style.display = 'block';
            });

            if (isIOS) {
                hint.innerHTML = lang.pwa_ios_share || 'Tap <strong>Share</strong> then <strong>"Add to Home Screen"</strong>';
                installBtn.textContent = lang.pwa_how || 'How?';
                banner.style.display = 'block';
            }

            if (installBtn) {
                installBtn.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const result = await deferredPrompt.userChoice;
                        if (result.outcome === 'accepted') {
                            banner.style.display = 'none';
                        }
                        deferredPrompt = null;
                    } else if (isIOS) {
                        hint.innerHTML = lang.pwa_ios_detail || '1. Tap <strong>Share</strong> ↗<br>2. Tap <strong>"Add to Home Screen"</strong>';
                        installBtn.style.display = 'none';
                    }
                });
            }

            // Botão dispensar (esconde por 24h)
            if (dismissBtn) {
                dismissBtn.addEventListener('click', () => {
                    banner.style.display = 'none';
                    localStorage.setItem('pwa-dismiss', Date.now().toString());
                });
            }

            // Se o app for instalado, esconder o banner
            window.addEventListener('appinstalled', () => {
                banner.style.display = 'none';
                deferredPrompt = null;
            });
        })();
    </script>
</body>
</html>