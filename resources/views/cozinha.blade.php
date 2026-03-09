@extends('layouts.kds')
@section('title', __('kds.kitchen'))
@section('body-class', 'bg-[#0c0c0e] text-white kds-font-ui')

@section('content')
<div x-data="kdsSystem()" x-init="init()" class="h-full bg-[#0c0c0e] w-full flex flex-col overflow-hidden">

    {{-- HEADER DA COZINHA --}}
    <div class="bg-[#27272a] border-b border-gray-700 z-30 shrink-0 shadow-md">

        {{-- Linha principal: Título | Logout | Relógio --}}
        <div class="h-14 flex items-center justify-between px-5 md:px-6">

            {{-- LADO ESQUERDO: Título --}}
            <h1 class="text-xl font-black tracking-tight text-[#7ed957] uppercase">
                {{ __('kds.kitchen') }}
            </h1>

            {{-- LADO DIREITO: Logout + Relógio --}}
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="bg-[#efa324] hover:bg-[#d98e1d] text-black font-black text-[10px] uppercase tracking-widest px-4 py-2 rounded-lg shadow-lg shadow-[#efa324]/20 active:scale-95 transition-all flex items-center gap-2 border border-[#efa324]"
                            title="{{ __('kds.logout') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden lg:inline">{{ __('kds.switch') }}</span>
                    </button>
                </form>

                <div class="h-8 w-px bg-gray-600/50"></div>

                <div class="text-right cursor-pointer active:scale-95 transition-transform select-none" @click="reconnect()" title="{{ __('kds.tap_to_refresh') }}">
                    <div class="text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock">--:--</div>
                    <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5 transition-colors duration-300"
                         :class="{ 'text-[#7ed957]': healthStatus === 'online', 'text-[#efa324]': healthStatus === 'partial', 'text-[#ef4444]': healthStatus === 'offline' }"
                         x-text="healthStatus === 'online' ? '● ' + __t('online') : healthStatus === 'partial' ? '◐ ' + __t('no_realtime') : '○ ' + __t('offline_status')"></div>
                </div>
            </div>
        </div>

        {{-- Linha de filtros: Categorias em botões pill --}}
        <div class="flex items-center gap-2 px-5 md:px-6 pb-3 overflow-x-auto scrollbar-hide">
            <button @click="filtro = 'todos'"
                    class="px-4 py-1.5 rounded-full font-bold text-xs whitespace-nowrap transition-all border uppercase tracking-wider flex items-center gap-1.5"
                    :class="filtro === 'todos' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white hover:border-gray-500'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                {{ __('kds.all_categories') }}
            </button>
            <button @click="filtro = 'cozinha'"
                    class="px-4 py-1.5 rounded-full font-bold text-xs whitespace-nowrap transition-all border uppercase tracking-wider flex items-center gap-1.5"
                    :class="filtro === 'cozinha' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white hover:border-gray-500'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg>
                {{ __('kds.kitchen_station') }}
            </button>
            <button @click="filtro = 'churrasqueira'"
                    class="px-4 py-1.5 rounded-full font-bold text-xs whitespace-nowrap transition-all border uppercase tracking-wider flex items-center gap-1.5"
                    :class="filtro === 'churrasqueira' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white hover:border-gray-500'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C9 7 6 9 6 13a6 6 0 0012 0c0-4-3-6-6-11z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 22v-4M8 18h8" /></svg>
                {{ __('kds.grill_station') }}
            </button>
            <button @click="filtro = 'bebidas'"
                    class="px-4 py-1.5 rounded-full font-bold text-xs whitespace-nowrap transition-all border uppercase tracking-wider flex items-center gap-1.5"
                    :class="filtro === 'bebidas' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white hover:border-gray-500'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                {{ __('kds.bar_station') }}
            </button>
        </div>
    </div>

    {{-- ÁREA DE SCROLL --}}

    <div class="flex-1 overflow-y-auto p-4 scrollbar-hide pb-40 bg-[#0c0c0e] overscroll-contain touch-pan-y">

        {{-- ESTADO VAZIO --}}
        <div x-show="pedidosFiltrados.length === 0" class="flex flex-col items-center justify-center h-full text-center px-6 select-none">
            <svg class="w-16 h-16 text-gray-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <p class="text-gray-500 text-lg font-bold uppercase tracking-widest">{{ __('kds.no_active_orders_long') }}</p>
            <p class="text-gray-700 text-xs mt-1">{{ __('kds.orders_appear_auto') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 auto-rows-max items-start">

            <template x-for="pedido in pedidosFiltrados" :key="pedido.id">
                
                {{-- CARD DE PEDIDO --}}
                <div x-show="!pedidosFinalizando.includes(pedido.id)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="relative bg-[#1e1e1e] border-t-4 rounded-b-lg shadow-lg flex flex-col group transition-all duration-300"
                     :class="{
                        'border-[#7ed957]': pedido.status === 'pendente' && !isUpdated(pedido.id),
                        'border-[#bf7854]': pedido.status === 'preparo' && !isUpdated(pedido.id),
                        'border-[#efa324] animate-pulse': isLateBorder(pedido.created_at) && !isUpdated(pedido.id),
                        'border-[#efa324] ring-4 ring-[#efa324]/20 z-10 scale-[1.02]': isUpdated(pedido.id)
                     }">
                    
                    {{-- HEADER DO CARD --}}
                    <div class="p-4 bg-white/5 flex justify-between items-start border-b border-white/5 shrink-0">
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-tighter leading-none">
                                <span x-text="__t('table')"></span> <span x-text="pedido.mesa"></span>
                            </h2>
                            <div class="flex flex-col gap-1 mt-2">
                                <template x-if="isUpdated(pedido.id)">
                                    <span class="text-[10px] font-black bg-[#efa324] text-black px-2 py-0.5 rounded uppercase tracking-widest animate-bounce w-max" x-text="'⚠ ' + __t('changed')"></span>
                                </template>
                                <template x-if="!isUpdated(pedido.id) && pedido.status === 'pendente' && isRecentItem(pedido.created_at)">
                                    <span class="text-[10px] font-black bg-[#7ed957] text-black px-2 py-0.5 rounded uppercase tracking-widest animate-pulse w-max" x-text="'★ ' + __t('new_order')"></span>
                                </template>
                                <span class="text-xs text-gray-500 font-mono" x-text="'#' + pedido.id" x-show="!isUpdated(pedido.id) && !(pedido.status === 'pendente' && isRecentItem(pedido.created_at))"></span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div :class="getSlaClasses(pedido.created_at)" x-text="formatTimer(pedido.created_at)"></div>
                            <div class="text-[10px] uppercase font-bold tracking-wider mt-1" :class="pedido.status === 'preparo' ? 'text-[#bf7854] animate-pulse' : 'text-gray-500'" x-text="pedido.status === 'pendente' ? __t('waiting') : __t('preparing_status')"></div>
                        </div>
                    </div>

                    {{-- LISTA DE ITENS --}}
                    <div class="p-4 flex-1">
                        <ul class="space-y-4">
                            <template x-for="item in pedido.itens" :key="item.id">
                                <li class="flex justify-between items-start border-b border-gray-800 pb-3 last:border-0 transition-all duration-500"
                                    :class="{
                                        'opacity-40 grayscale': pedido.status === 'preparo' && (hasNewItems(pedido) && !isItemNew(item, pedido)),
                                        'bg-[#efa324]/10 p-2 rounded-lg -mx-2 border-l-4 border-[#efa324]': isItemNew(item, pedido)
                                    }">
                                    <div class="flex-1 pr-3">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xl md:text-2xl font-bold leading-tight block mb-1" :class="isItemNew(item, pedido) ? 'text-white' : 'text-gray-300'" x-text="item.nome_produto"></span>
                                            <template x-if="isItemNew(item, pedido) && pedido.status === 'preparo'">
                                                <span class="bg-[#efa324] text-black text-[9px] font-black px-1.5 py-0.5 rounded uppercase animate-pulse" x-text="__t('added')"></span>
                                            </template>
                                        </div>
                                        <template x-if="item.observacao">
                                            <div class="text-sm font-bold px-2 py-1 rounded inline-block mt-1 shadow-sm" :class="isItemNew(item, pedido) ? 'bg-[#efa324] text-black' : 'bg-gray-800 text-[#efa324]'">
                                                <span x-text="'⚠️ ' + item.observacao"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <span class="font-mono text-2xl px-3 py-2 rounded-lg font-black min-w-[3rem] text-center transition-colors duration-300"
                                        :class="{ 'bg-[#efa324] text-black shadow-[0_0_15px_rgba(239,163,36,0.4)] scale-110': isItemNew(item, pedido), 'bg-[#0c0c0e] text-[#7ed957] border border-gray-800': !isItemNew(item, pedido) }"
                                        x-text="item.quantidade"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- FOOTER DO CARD (BOTÃO) --}}
                    <div class="p-3 mt-auto bg-[#0c0c0e]/50 border-t border-gray-800 flex flex-col gap-3 shrink-0">
                        <button @click="avancarStatus(pedido)" 
                                class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-sm transition-all active:scale-[0.98] shadow-lg flex items-center justify-center gap-2"
                                :class="{ 'bg-[#2a2a30] hover:bg-[#33333a] text-white border border-gray-700': pedido.status === 'pendente', 'bg-[#bf7854] hover:bg-[#a66545] text-white shadow-[#bf7854]/20': pedido.status === 'preparo' }">
                            <span x-text="pedido.status === 'pendente' ? __t('start_prep') : __t('finish_order')"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- BARRA DE DESFAZER --}}
    <div x-show="undoData" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0" class="fixed bottom-6 left-0 w-full z-[150] px-4 flex justify-center pointer-events-none">
        <div class="pointer-events-auto bg-[#18181b] border border-gray-700 text-white w-full max-w-2xl rounded-2xl shadow-2xl p-4 flex items-center justify-between ring-1 ring-white/10 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="relative w-10 h-10 flex items-center justify-center">
                    {{-- Progresso circular com animação real de 3s --}}
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="15" fill="none" stroke="#27272a" stroke-width="3"></circle>
                        <circle cx="18" cy="18" r="15" fill="none" stroke="#7ed957" stroke-width="3" stroke-linecap="round"
                                stroke-dasharray="94.25" stroke-dashoffset="0"
                                style="animation: undo-progress 3s linear forwards"></circle>
                    </svg>
                    <span class="absolute text-sm font-black text-[#7ed957]" x-text="undoCountdown"></span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest" x-text="__t('finishing')"></p>
                    <p class="text-xl font-black leading-none mt-0.5 text-white"><span x-text="__t('table').toUpperCase()"></span> <span x-text="undoData?.mesa"></span></p>
                </div>
            </div>
            <button @click="desfazerFinalizacao()" class="bg-[#7ed957] text-black hover:bg-[#6ec24a] active:scale-95 transition-all px-6 py-2.5 rounded-xl font-black text-sm uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-[#7ed957]/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                {{ __('kds.undo') }}
            </button>
        </div>
    </div>
</div>
@endsection