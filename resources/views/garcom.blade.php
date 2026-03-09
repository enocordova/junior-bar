@extends('layouts.kds')
@section('title', __('kds.waiter'))
@section('body-class', 'bg-[#0c0c0e] font-sans overflow-hidden')

@section('content')
{{-- CONTAINER PRINCIPAL --}}
<div x-data="garcomPro()" x-init="init()" x-cloak class="h-full flex flex-col overflow-hidden relative bg-[#0c0c0e]">

    {{-- HEADER --}}
    <div class="h-16 bg-[#27272a] border-b border-gray-700 flex items-center justify-between px-5 md:px-6 z-30 shrink-0 shadow-md relative">
        
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-black tracking-tight text-[#7ed957] uppercase">
                {{ __('kds.waiter') }}
            </h1>

            <div class="hidden md:flex gap-3 border-l border-gray-600 pl-6">
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#7ed957]"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">
                        {{ __('kds.free_tables') }}: <span x-text="mesasLivres" class="text-white"></span>
                    </span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#bf7854] animate-pulse"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">
                        {{ __('kds.occupied_tables') }}: <span x-text="mesasOcupadas" class="text-white"></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            {{-- Botão Wi-Fi --}}
            <button @click="showWifi = true" 
                    class="bg-[#18181b] border border-gray-600 text-white p-2 rounded-lg active:scale-95 transition-all mr-2 hover:border-[#7ed957] group"
                    title="{{ __('kds.free_wifi') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-[#7ed957] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
            </button>

            {{-- Formulário de Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="bg-[#efa324] hover:bg-[#d98e1d] text-black font-black text-[10px] uppercase tracking-widest px-4 py-2 rounded-lg shadow-lg shadow-[#efa324]/20 active:scale-95 transition-all flex items-center gap-2 border border-[#efa324]"
                        title="{{ __('kds.logout') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden lg:inline">{{ __('kds.logout') }}</span>
                </button>
            </form>

            <div class="h-8 w-px bg-gray-700"></div>

            {{-- Relógio (toque para reconectar) --}}
            <div class="text-right cursor-pointer active:scale-95 transition-transform select-none" @click="reconnect()" title="{{ __('kds.tap_to_refresh') }}">
                <div class="text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock">--:--</div>
                <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5 transition-colors duration-300 flex items-center justify-end gap-1.5"
                     :class="{ 'text-[#7ed957]': healthStatus === 'online', 'text-[#efa324]': healthStatus === 'partial', 'text-[#ef4444]': healthStatus === 'offline' }">
                    <span class="w-2 h-2 rounded-full" :class="{ 'bg-[#7ed957] animate-pulse': healthStatus === 'online', 'bg-[#efa324] animate-pulse': healthStatus === 'partial', 'bg-[#ef4444]': healthStatus === 'offline' }"></span>
                    <span x-text="healthStatus === 'online' ? __t('online') : healthStatus === 'partial' ? __t('no_realtime') : __t('offline_status')"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- BANNER FILA OFFLINE --}}
    <div x-show="filaOffline.length > 0" x-transition
         class="bg-[#efa324] text-black px-4 py-2.5 flex items-center justify-between shrink-0 z-20">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                <span class="font-black text-xs uppercase tracking-wider" x-text="filaOffline.length + ' ' + __t('orders_in_queue')"></span>
            </div>
            <button @click="processarFilaOffline()" class="bg-black/20 hover:bg-black/30 text-black font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-lg active:scale-95 transition-all">
                {{ __('kds.resend') }}
            </button>
        </div>
        <button @click="askConfirm(__t('discard_queue'), () => descartarFilaOffline())" class="text-black/60 hover:text-black p-1 active:scale-95" title="{{ __('kds.discard_queue') }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{--
        [CORREÇÃO DE LAYOUT]
        Wrapper Flexível: Ocupa todo o espaço restante abaixo do Header.
        O conteúdo interno usa 'absolute inset-0' relativo a ESTE wrapper, não à tela toda.
    --}}
    <div class="flex-1 relative overflow-hidden w-full">

        {{-- FASE 1: GRADE DE MESAS --}}
        {{-- FIX: Removido 'top-16', agora posicionado no wrapper --}}
        <div x-show="!mesaSelecionada" 
             x-transition:enter="transition ease-out duration-300"
             class="absolute inset-0 z-10 bg-[#0c0c0e] flex flex-col"> 
            
            <div class="flex-1 overflow-y-auto p-4 scrollbar-hide pb-20">
                <template x-if="listaMesas.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-gray-500 animate-pulse">
                        <svg class="w-12 h-12 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                        <span class="text-xs uppercase font-bold tracking-widest">{{ __('kds.loading_tables') }}</span>
                    </div>
                </template>

                <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
                    <template x-for="mesa in listaMesas" :key="mesa.numero">
                        <button @click="selecionarMesa(mesa)"
                                class="aspect-square rounded-2xl flex flex-col items-center justify-center border transition-all duration-200 touch-manipulation relative overflow-hidden group shadow-lg"
                                :class="mesasAlertadas.includes(mesa.numero)
                                    ? 'bg-[#18181b] border-[#7ed957] text-white ring-2 ring-[#7ed957]/40 shadow-[0_0_25px_rgba(126,217,87,0.35)] animate-pulse'
                                    : hasReadyOrder(mesa)
                                        ? 'bg-[#18181b] border-[#7ed957] text-white shadow-[0_0_15px_rgba(126,217,87,0.2)]'
                                        : mesa.ocupada
                                            ? 'bg-[#18181b] border-[#bf7854] text-white shadow-[0_0_10px_rgba(191,120,84,0.2)]'
                                            : 'bg-[#18181b] border-gray-800 text-gray-400 hover:border-[#7ed957] hover:text-white'">
                            
                            <span class="text-2xl font-black font-mono z-10" x-text="mesa.numero"></span>
                            
                            <template x-if="mesa.label">
                                <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mt-1 z-10" x-text="mesa.label"></span>
                            </template>
                            
                            <template x-if="mesa.ocupada">
                                <div class="flex flex-col items-center mt-1 z-10">
                                    <span x-show="hasReadyOrder(mesa)" class="text-[8px] font-black text-[#7ed957] uppercase tracking-wider bg-[#7ed957]/15 px-1.5 py-0.5 rounded" x-text="__t('ready')"></span>
                                    <span x-show="!hasReadyOrder(mesa)" class="text-[8px] font-bold text-[#bf7854] uppercase tracking-wider bg-[#bf7854]/10 px-1.5 py-0.5 rounded" x-text="__t('occupied')"></span>
                                </div>
                            </template>
                            <div class="absolute inset-0 bg-[#7ed957] opacity-0 group-active:opacity-20 transition-opacity"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- FASE EXTRA: MONITOR DA MESA --}}
        {{-- FIX: Removido 'top-16', agora posicionado no wrapper --}}
        <div x-show="painelMesaOpen" 
             x-transition:enter="transition ease-out duration-200"
             class="absolute inset-0 z-40 bg-[#0c0c0e] flex flex-col">

            <div class="h-16 bg-[#18181b] border-b border-gray-800 flex items-center justify-between px-6 shrink-0">
                <button @click="fecharMonitor()"
                        class="shrink-0 h-14 w-14 rounded-xl bg-[#18181b] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] hover:bg-[#1f1f23] active:scale-95 transition-all flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>

                <div class="text-right">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ __('kds.table_status') }}</span>
                    <h2 class="text-3xl font-black text-white leading-none font-mono" x-text="__t('table').toUpperCase() + ' ' + mesaSelecionada"></h2>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <template x-if="mesaDadosAtuais && mesaDadosAtuais.pedidos">
                    <template x-for="pedido in mesaDadosAtuais.pedidos" :key="pedido.id">
                        <div class="bg-[#18181b] rounded-xl border-l-4 p-4 shadow-sm"
                             :class="{
                                'border-[#efa324]': pedido.status === 'pendente',
                                'border-[#bf7854]': pedido.status === 'preparo',
                                'border-[#7ed957]': pedido.status === 'pronto'
                             }">
                            <div class="flex justify-between items-center mb-3 border-b border-gray-800 pb-2">
                                <span class="text-xs font-bold text-gray-500" x-text="'PEDIDO #' + pedido.id"></span>
                                <div class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest"
                                     :class="{
                                        'bg-[#efa324]/10 text-[#efa324]': pedido.status === 'pendente',
                                        'bg-[#bf7854]/10 text-[#bf7854] animate-pulse': pedido.status === 'preparo',
                                        'bg-[#7ed957]/10 text-[#7ed957]': pedido.status === 'pronto'
                                     }">
                                     <span x-text="pedido.status === 'pendente' ? __t('sent_kitchen') : (pedido.status === 'preparo' ? __t('preparing') : __t('ready'))"></span>
                                </div>
                            </div>
                            <ul class="space-y-2">
                                <template x-for="item in pedido.itens" :key="item.id">
                                    <li class="flex justify-between items-start">
                                        <div class="flex gap-3">
                                            <span class="font-bold text-gray-400" x-text="item.quantidade + 'x'"></span>
                                            <div>
                                                <span class="text-gray-200 font-medium text-sm block" x-text="item.nome_produto"></span>
                                                <template x-if="item.observacao">
                                                    <div class="text-[10px] text-[#efa324] font-bold mt-0.5 flex items-center gap-1">
                                                        <span>⚠️</span> <span x-text="item.observacao"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="pedido.status === 'pronto'">
                                            <svg class="w-5 h-5 text-[#7ed957]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </template>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </template>
                
                <template x-if="!mesaDadosAtuais || !mesaDadosAtuais.pedidos || mesaDadosAtuais.pedidos.length === 0">
                    <div class="text-center py-10 text-gray-600">
                        <p class="text-xs uppercase font-bold">{{ __('kds.no_active_orders') }}</p>
                    </div>
                </template>
            </div>

            <div class="p-4 bg-[#18181b] border-t border-gray-800 shrink-0 shadow-[0_-5px_15px_rgba(0,0,0,0.3)]">
                <div class="flex justify-between items-end mb-4 px-1">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ __('kds.total_bill') }}</span>
                    <span class="text-3xl font-black text-[#7ed957] font-mono" x-text="formatMoney(mesaDadosAtuais?.total || 0)"></span>
                </div>
                
                <button @click="abrirCardapio(mesaSelecionada)" 
                        class="w-full py-4 bg-[#7ed957] text-black font-black rounded-xl uppercase tracking-widest text-sm shadow-lg shadow-[#7ed957]/20 flex items-center justify-center gap-2 hover:bg-[#6ec24a] active:scale-[0.98] transition-all">
                    <span>{{ __('kds.add_new_items') }}</span>
                </button>
            </div>
        </div>

        {{-- FASE 2: CARDÁPIO --}}
        {{-- FIX: Agora usa 'absolute inset-0' dentro do wrapper para ocupar o espaço exato --}}
        <div x-show="mesaSelecionada && !painelMesaOpen" 
             class="absolute inset-0 z-20 flex flex-col h-full bg-[#0c0c0e]">
            
            <div class="bg-[#27272a] shadow-md z-30 shrink-0 pb-3 border-b border-gray-700">
                <div class="px-4 py-3 flex gap-3 items-center">
                    <button @click="voltar()" class="shrink-0 h-14 w-14 rounded-xl bg-[#18181b] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] flex items-center justify-center active:scale-95 transition-all">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </button>

                    <div class="flex-1 relative">
                        <input type="text" x-model="search" placeholder="{{ __('kds.search_product') }}"
                               class="w-full pl-4 pr-4 h-14 bg-[#18181b] border border-gray-700 text-white rounded-xl font-bold text-lg placeholder-gray-600 focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all">
                    </div>

                    <button @click="trocarMesa()" class="shrink-0 flex flex-col items-center justify-center bg-[#18181b] border border-gray-600 text-[#7ed957] w-14 h-14 rounded-xl active:scale-95 transition-transform shadow-lg group">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-white opacity-60 group-hover:opacity-100">{{ __('kds.table') }}</span>
                        <span class="text-2xl font-black leading-none font-mono" x-text="mesaSelecionada"></span>
                    </button>
                </div>

                <div class="flex overflow-x-auto gap-2 px-4 scrollbar-hide snap-x">
                    <button @click="filtroCategoria = ''" 
                            class="px-5 py-2 rounded-full font-bold text-xs whitespace-nowrap transition-all border snap-start uppercase tracking-wider"
                            :class="filtroCategoria === '' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white'">
                        {{ __('kds.all') }}
                    </button>
                    <template x-for="cat in categoriasUnicas" :key="cat">
                        <button @click="filtroCategoria = cat" 
                                class="px-5 py-2 rounded-full font-bold text-xs whitespace-nowrap transition-all border snap-start uppercase tracking-wider"
                                :class="filtroCategoria === cat ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white'"
                                x-text="cat">
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-4 pt-4 scrollbar-hide space-y-3 bg-[#0c0c0e]"
                 :style="'padding-bottom:' + (carrinho.length > 0 ? '140px' : '16px') + ';transition:padding-bottom .3s ease'">
                <template x-for="produto in produtosFiltrados" :key="produto.id">
                    <button @click="addProduto(produto)" 
                            class="w-full bg-[#18181b] p-4 rounded-xl shadow-sm border border-gray-800 hover:border-[#7ed957] flex justify-between items-center active:scale-[0.98] transition-all group relative overflow-hidden touch-manipulation">
                        
                        <div class="text-left z-10">
                            <span class="block text-lg font-bold text-white leading-tight" x-text="produto.nome"></span>
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-wider bg-[#0c0c0e] border border-gray-800 px-2 py-0.5 rounded mt-1 inline-block" x-text="produto.categoria"></span>
                        </div>

                        <div class="flex flex-col items-end z-10">
                            <span class="font-mono font-black text-[#7ed957] text-xl" x-text="formatMoney(produto.preco)"></span>
                            <div x-show="getQtyCart(produto.id) > 0" x-transition.scale
                                 class="mt-1 bg-[#bf7854] text-white text-xs font-bold px-2 py-1 rounded-lg shadow-md flex items-center gap-1 border border-[#bf7854]/50">
                                <span x-text="getQtyCart(produto.id)"></span>
                                <span class="text-[8px] uppercase" x-text="__t('in_order')"></span>
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-[#7ed957] opacity-0 group-active:opacity-10 transition-opacity"></div>
                    </button>
                </template>
            </div>
        </div>

    </div> {{-- FIM DO WRAPPER FLEXÍVEL --}}

    {{-- CARRINHO (Mantido fora do Wrapper para cobrir a tela toda se necessário) --}}
    <div x-show="cartOpen" @click="cartOpen = false" x-transition.opacity class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[60]"></div>

    <div class="fixed bottom-0 left-0 w-full bg-[#18181b] z-[70] rounded-t-[2rem] shadow-[0_-10px_60px_-10px_rgba(0,0,0,0.5)] border-t border-gray-800 transition-transform duration-300 ease-out flex flex-col max-h-[90vh]"
         :class="cartOpen ? 'translate-y-0' : (carrinho.length > 0 && mesaSelecionada ? 'translate-y-[calc(100%-120px)]' : 'translate-y-full')">
        
        {{-- CABEÇALHO DO CARRINHO (puxador + info, min 120px = match translate) --}}
        <div @click="cartOpen = !cartOpen"
             class="shrink-0 bg-[#18181b] rounded-t-[2rem] cursor-pointer touch-manipulation hover:bg-[#1f1f23] active:bg-[#1f1f23] transition-colors z-20 relative flex flex-col"
             :class="cartOpen && 'border-b border-gray-800'"
             style="min-height: 120px">

            <div class="w-full flex justify-center pt-3 pb-1">
                <div class="w-10 h-1 bg-gray-600 rounded-full"></div>
            </div>

            <div class="flex-1 px-4 sm:px-6 pb-3 flex justify-between items-center gap-2">
                <div class="flex flex-col justify-center flex-1 min-w-0">
                    <span class="text-[9px] sm:text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-0.5 sm:mb-1 truncate">
                        {{ __('kds.estimated_total') }}
                    </span>
                    <span class="text-2xl sm:text-3xl font-black text-[#7ed957] font-mono tracking-tighter leading-none truncate"
                          x-text="formatMoney(totalCarrinho)">
                    </span>
                </div>

                <div class="flex items-center shrink-0">
                    <div class="bg-[#7ed957] text-black rounded-xl px-4 sm:px-5 py-3 flex items-center gap-2 sm:gap-3 shadow-lg shadow-[#7ed957]/20 active:scale-95 transition-transform"
                         x-show="!cartOpen">
                        <span class="font-black text-[10px] sm:text-xs uppercase tracking-widest whitespace-nowrap">
                            {{ __('kds.view_order') }}
                        </span>
                        <span class="bg-black text-[#7ed957] text-[10px] font-mono font-bold px-2 py-0.5 rounded"
                              :class="{ 'animate-badge': badgeAnim }"
                              x-text="totalItens">
                        </span>
                    </div>

                    <div class="bg-gray-800 rounded-full p-3 text-gray-400 border border-gray-700 hover:text-white transition-colors"
                         x-show="cartOpen"
                         style="display: none;">
                         <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                         </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-[#0c0c0e] scrollbar-hide" x-show="cartOpen">
            <template x-for="(item, index) in carrinho" :key="item.uuid">
                <div class="bg-[#18181b] p-4 rounded-2xl border border-gray-800 shadow-sm animate-fade-in-up">
                    <div class="flex justify-between items-start mb-3">
                        <span class="font-black text-white text-lg leading-tight w-2/3" x-text="item.nome"></span>
                        <span class="font-mono font-bold text-[#7ed957]" x-text="formatMoney(item.preco * item.qtd)"></span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <input type="text" x-model="item.obs" placeholder="{{ __('kds.any_notes') }}" class="w-full pl-3 pr-3 py-2 bg-[#2a2a30] border-none rounded-xl text-sm font-semibold text-white placeholder-gray-500 focus:ring-1 focus:ring-[#7ed957] transition-all">
                        <div class="flex items-center justify-between mt-1">
                            <button @click="removerItem(index)" class="text-red-500 text-xs font-bold uppercase tracking-wider hover:text-red-400 px-2 py-1">{{ __('kds.remove') }}</button>
                            <div class="flex items-center gap-4 bg-[#0c0c0e] border border-gray-800 rounded-xl p-1.5">
                                <button @click="decrementarItem(index)" class="w-10 h-10 bg-[#18181b] rounded-lg text-xl font-black text-gray-400 hover:text-white flex items-center justify-center touch-manipulation">-</button>
                                <span class="font-mono font-black text-xl min-w-[1.5rem] text-center text-white" x-text="item.qtd"></span>
                                <button @click="incrementarItem(index)" class="w-10 h-10 bg-[#7ed957] text-black rounded-lg text-xl font-black active:scale-95 flex items-center justify-center touch-manipulation">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div class="h-20"></div>
        </div>

        <div class="p-4 bg-[#18181b] border-t border-gray-800 absolute bottom-0 w-full" x-show="cartOpen">
            <button @click="enviarPedido()" :disabled="carrinho.length === 0 || loading" class="w-full h-16 bg-[#7ed957] text-black rounded-2xl font-black text-xl shadow-xl shadow-[#7ed957]/20 uppercase tracking-widest hover:bg-[#6ec24a] active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center gap-3">
                <span x-show="loading" class="animate-spin h-6 w-6 border-4 border-black border-t-transparent rounded-full"></span>
                <span x-text="loading ? __t('sending') : __t('send_order')"></span>
            </button>
        </div>
    </div>

    {{-- MODAL CONFIRMACAO --}}
    <div x-show="confirmModal.open" 
         style="display: none;"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition.opacity>
        <div @click.away="confirmModal.open = false" 
             class="bg-[#18181b] w-full max-w-sm rounded-3xl border border-gray-800 shadow-2xl p-6 text-center">
            <div class="mx-auto mb-4 w-16 h-16 bg-[#efa324]/10 rounded-full flex items-center justify-center border border-[#efa324]/20">
                <svg class="w-8 h-8 text-[#efa324]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-xl font-black text-white mb-2" x-text="confirmModal.title"></h3>
            <p class="text-gray-400 text-sm mb-6" x-text="confirmModal.message"></p>
            <div class="flex gap-3">
                <button @click="confirmModal.open = false" class="flex-1 h-12 rounded-xl bg-[#2a2a30] text-gray-400 font-bold hover:bg-[#33333a] hover:text-white">{{ __('kds.cancel') }}</button>
                <button @click="confirmModal.action(); confirmModal.open = false" class="flex-1 h-12 rounded-xl bg-[#7ed957] text-black font-black hover:bg-[#6ec24a]">{{ __('kds.confirm') }}</button>
            </div>
        </div>
    </div>

    {{-- MODAL WI-FI --}}
    <div x-show="showWifi" 
         style="display: none;"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
         x-transition.opacity>
        <div @click.away="showWifi = false" class="bg-white text-black w-full max-w-sm rounded-3xl p-8 flex flex-col items-center text-center relative">
            <h3 class="text-2xl font-black mb-2 uppercase tracking-tighter">{{ __('kds.free_wifi') }}</h3>
            <p class="text-sm text-gray-500 mb-6 font-bold">{{ __('kds.show_to_scan') }}</p>
            
            <div id="qrcode" class="p-2 border-4 border-black rounded-xl mb-6 shadow-xl min-h-[180px] flex items-center justify-center bg-white"></div>

            <div class="bg-gray-100 p-4 rounded-xl w-full mb-6">
                <p class="text-xs text-gray-500 uppercase font-bold">{{ __('kds.network') }}</p>
                <p class="text-lg font-black leading-none mb-3 break-words" x-text="window.AppConfig?.wifiSsid || __t('not_configured')"></p>
                <p class="text-xs text-gray-500 uppercase font-bold">{{ __('kds.wifi_password') }}</p>
                <p class="text-xl font-mono font-black leading-none break-words" x-text="window.AppConfig?.wifiPass || ''"></p>
            </div>
            <button @click="showWifi = false" class="w-full py-3 bg-black text-white font-bold rounded-xl uppercase tracking-widest text-sm shadow-lg">{{ __('kds.close') }}</button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endpush