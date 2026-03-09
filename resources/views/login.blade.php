<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('kds.login_btn') }} - {{ \App\Models\Configuracao::valor('nome_restaurante', 'Junior Bar') }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#18181b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Junior Bar">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-[#0c0c0e] min-h-dvh flex items-center justify-center p-4" style="min-height: 100dvh; padding-top: env(safe-area-inset-top, 16px); padding-bottom: env(safe-area-inset-bottom, 16px);">

    <div class="w-full max-w-sm bg-[#18181b] rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] border border-gray-800 overflow-hidden">

        {{-- Header do Card com Logo --}}
        <div class="bg-[#202025] px-8 pt-8 pb-6 border-b border-gray-700 flex flex-col items-center">
            <img src="/logo.png" alt="{{ \App\Models\Configuracao::valor('nome_restaurante', 'Junior Bar') }}" class="w-20 h-20 rounded-2xl shadow-lg shadow-black/30 mb-4 border border-gray-700">
            <h1 class="text-2xl font-black text-[#7ed957] tracking-tight uppercase">{{ \App\Models\Configuracao::valor('nome_restaurante', 'Junior Bar') }}</h1>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">{{ __('kds.access_control') }}</p>
        </div>

        <div class="p-8">
            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6 text-sm font-bold shadow-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                {{-- Campo Email --}}
                <div class="mb-5">
                    <label for="email" class="block text-gray-500 text-[10px] font-black uppercase tracking-widest mb-2">{{ __('kds.user') }}</label>
                    <div class="relative">
                        <div id="input-icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-all duration-300">
                            {{-- Ícone padrão (pessoa) --}}
                            <svg id="ico-default" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{-- Ícone garçom (pessoa) --}}
                            <svg id="ico-garcom" class="h-5 w-5 text-[#7ed957] hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{-- Ícone cozinha (fogo) --}}
                            <svg id="ico-cozinha" class="h-5 w-5 text-[#7ed957] hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                            </svg>
                            {{-- Ícone gerente (escudo) --}}
                            <svg id="ico-admin" class="h-5 w-5 text-[#7ed957] hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <input type="text" name="email" id="email" required
                            class="w-full pl-10 pr-4 py-3 bg-[#0c0c0e] border border-gray-700 rounded-xl text-white font-bold placeholder-gray-700 focus:outline-none focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all"
                            placeholder="{{ __('kds.login_hint') }}">
                    </div>
                </div>

                {{-- Campo Senha --}}
                <div class="mb-8">
                    <label for="password" class="block text-gray-500 text-[10px] font-black uppercase tracking-widest mb-2">{{ __('kds.password') }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="password" id="password" required
                            class="w-full pl-10 pr-12 py-3 bg-[#0c0c0e] border border-gray-700 rounded-xl text-white font-bold placeholder-gray-700 focus:outline-none focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="icon-eye" class="h-5 w-5 text-gray-600 hover:text-gray-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="icon-eye-off" class="h-5 w-5 text-gray-600 hover:text-gray-400 transition-colors hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 014.21-5.368M9.88 9.88a3 3 0 104.24 4.24" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Botão Entrar --}}
                <button type="submit"
                    class="w-full bg-[#7ed957] hover:bg-[#6ec24a] text-black font-black text-sm uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-[#7ed957]/20 active:scale-[0.98] transition-all flex justify-center items-center">
                    <span id="btn-label">{{ __('kds.login_btn') }}</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const ROLES = {
            garcom:  { label: 'Acessar {{ __("kds.waiter") }}',  icoId: 'ico-garcom' },
            cozinha: { label: 'Acessar {{ __("kds.kitchen") }}', icoId: 'ico-cozinha' },
            admin:   { label: 'Acessar {{ __("kds.manager") }}', icoId: 'ico-admin' }
        };
        const defaultLabel = '{{ __("kds.login_btn") }}';

        function detectRole(value) {
            const v = value.trim().toLowerCase();
            if (v.includes('cozinha') || v.includes('kitchen')) return 'cozinha';
            if (v.includes('garcom') || v.includes('garçom') || v.includes('waiter')) return 'garcom';
            if (v.includes('admin') || v.includes('gerente') || v.includes('bar')) return 'admin';
            return null;
        }

        function updateLoginUI(role) {
            const btnLabel = document.getElementById('btn-label');

            // Esconder todos os ícones do input
            ['ico-default', 'ico-garcom', 'ico-cozinha', 'ico-admin'].forEach(id => {
                document.getElementById(id).classList.add('hidden');
            });

            if (role && ROLES[role]) {
                document.getElementById(ROLES[role].icoId).classList.remove('hidden');
                btnLabel.textContent = ROLES[role].label;
            } else {
                document.getElementById('ico-default').classList.remove('hidden');
                btnLabel.textContent = defaultLabel;
            }
        }

        function togglePassword() {
            const input = document.getElementById('password');
            const iconEye = document.getElementById('icon-eye');
            const iconEyeOff = document.getElementById('icon-eye-off');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            iconEye.classList.toggle('hidden', isHidden);
            iconEyeOff.classList.toggle('hidden', !isHidden);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
                emailInput.addEventListener('input', function() {
                    updateLoginUI(detectRole(this.value));
                });
                if (emailInput.value) {
                    updateLoginUI(detectRole(emailInput.value));
                }
            }
        });
    </script>
</body>
</html>
