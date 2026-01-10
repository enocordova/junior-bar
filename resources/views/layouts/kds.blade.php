<!DOCTYPE html>
<html lang="pt-pt" class="h-full bg-[#0c0c0e]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Junior BAR</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script>
        window.AppConfig = {
            socketUrl: "http://" + window.location.hostname + ":3000"
        };
    </script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .kds-font-mono { font-family: 'JetBrains Mono', monospace; }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #18181b; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #7ed957; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class') font-sans antialiased text-gray-100 bg-[#0c0c0e] selection:bg-[#7ed957] selection:text-black overflow-x-hidden">
    
    {{-- HEADER --}}
    <header class="fixed top-0 left-0 w-full h-[70px] bg-[#18181b] border-b border-gray-800 flex items-center justify-between px-4 md:px-6 z-50 shadow-md">
        
        {{-- LOGO (Visível para todos) --}}
        <div class="flex items-center gap-2">
            <div class="kds-font-ui text-xl tracking-tight font-black uppercase text-[#7ed957]">
                Junior <span class="text-[10px] align-top text-white font-bold ml-0.5 opacity-60">BAR</span>
            </div>
        </div>

        {{-- 
             LÓGICA DE NAVEGAÇÃO SEGURA 
             A <nav> só aparece se o usuário já estiver na rota '/gerente'.
             Garçons e Cozinheiros não verão nenhum botão aqui.
        --}}
        @if(request()->is('gerente*'))
        <nav class="flex gap-1 bg-[#0c0c0e] p-1 rounded-lg border border-gray-800 animate-fade-in">
            
            {{-- Link para abrir o GARÇOM --}}
            <a href="/garcom" target="_blank"
               class="px-4 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-widest transition-all text-gray-500 hover:text-[#7ed957] hover:bg-[#18181b] flex items-center gap-2">
               <span>Garçom</span>
               <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>

            {{-- Link para abrir a COZINHA --}}
            <a href="/cozinha" target="_blank"
               class="px-4 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-widest transition-all text-gray-500 hover:text-[#7ed957] hover:bg-[#18181b] flex items-center gap-2">
               <span>Cozinha</span>
               <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
            
            {{-- Indicador de que você está no Painel Admin --}}
            <div class="px-4 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-widest bg-[#2a2a30] text-white border border-gray-700 cursor-default ml-1">
               Painel Gerente
            </div>
        </nav>
        @endif
        
    </header>

    <main class="pt-[70px] min-h-screen bg-[#0c0c0e]">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>