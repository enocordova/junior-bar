<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Junior BAR</title>
    
    <script>
        // Configuração Centralizada
        window.AppConfig = {
            socketUrl: "http://" + window.location.hostname + ":3000"
        };
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class') font-sans antialiased selection:bg-yellow-500 selection:text-white">
    
    <header class="fixed top-0 left-0 w-full h-[70px] bg-white/95 backdrop-blur-sm border-b border-gray-100 flex items-center justify-between px-4 md:px-6 z-50">
        
        <div class="kds-font-ui text-xl tracking-[0.15em] font-black uppercase text-gray-900">
            Junior <span class="text-[10px] align-top text-gray-400 font-bold ml-0.5">BAR</span>
        </div>

        <nav class="flex gap-1 bg-gray-100 p-1 rounded-lg">
            <a href="/garcom" 
               class="px-4 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-widest transition-all {{ request()->is('garcom') ? 'bg-white text-black shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
               Garçom
            </a>
            <a href="/cozinha" 
               class="px-4 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-widest transition-all {{ request()->is('cozinha') ? 'bg-black text-white shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
               Cozinha
            </a>
        </nav>
        
    </header>

    <main class="pt-[90px] min-h-screen">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>