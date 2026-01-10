@extends('layouts.kds')
@section('title', 'Cozinha')
@section('body-class', 'bg-[#0c0c0e] text-white kds-font-ui overflow-hidden')

@section('content')
{{-- 
    CONTEXTO KDS:
    x-data: Inicializa o módulo JS central (cozinha.js)
    x-init: Dispara loaders, sockets, audio e relógio
--}}
<div x-data="kdsSystem()" x-init="init()" class="h-screen flex flex-col relative">

    {{-- 
        =======================================================================
        HEADER PADRONIZADO (DARK PRO IDENTIDADE VISUAL)
        =======================================================================
    --}}
    <div class="h-16 bg-[#4d4a52] border-b border-gray-700 flex items-center justify-between px-6 z-40 shrink-0 shadow-md">
        
        {{-- LADO ESQUERDO: Título --}}
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-black tracking-tight text-[#7ed957]">
                COZINHA
            </h1>
        </div>
        
        {{-- LADO DIREITO: Filtros | Divisória | Relógio + Status --}}
        <div class="flex items-center gap-6">
            
            {{-- SELETOR DE ESTAÇÃO (Custom Select Style) --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-3 w-3 text-[#7ed957]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
                
                <select x-model="filtro" class="appearance-none bg-[#18181b] text-white border border-gray-600 rounded-lg text-[10px] font-bold uppercase tracking-widest pl-9 pr-8 py-2 focus:outline-none focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all cursor-pointer hover:border-gray-500 shadow-sm min-w-[160px]">
                    <option value="todos">Todas as Estações</option>
                    <option value="Lanches">Cozinha (Lanches)</option>
                    <option value="Bebidas">Bar (Bebidas)</option>
                </select>
                
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 group-hover:text-white">
                    <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                </div>
            </div>

            {{-- DIVISÓRIA VERTICAL --}}
            <div class="h-8 w-px bg-gray-600/50"></div>

            {{-- BLOCO RELÓGIO & STATUS --}}
            <div class="text-right">
                <div class="text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock">--:--</div>
                <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5 transition-colors duration-300" 
                     :class="online ? 'text-[#7ed957]' : 'text-[#ef4444]'" 
                     x-text="online ? '● ONLINE' : '○ OFFLINE'"></div>
            </div>
        </div>
    </div>

    {{-- 
        =======================================================================
        GRID PRINCIPAL: Renderização dos Pedidos
        =======================================================================
    --}}
    <div class="flex-1 overflow-y-auto p-4 scrollbar-hide pb-24 bg-[#0c0c0e]">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 auto-rows-max">
            
            <template x-for="pedido in pedidosFiltrados" :key="pedido.id">
                
                {{-- CARD DE PEDIDO --}}
                <div x-show="!pedidosFinalizando.includes(pedido.id)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90"
                     class="relative bg-[#1e1e1e] border-t-4 rounded-b-lg shadow-lg flex flex-col min-h-[350px] group transition-all duration-300"
                     :class="{
                        /* ESTADOS DE BORDA */
                        'border-[#7ed957]': pedido.status === 'pendente' && !isUpdated(pedido.id),
                        'border-[#bf7854]': pedido.status === 'preparo' && !isUpdated(pedido.id),
                        'border-[#efa324] animate-pulse': isLateBorder(pedido.created_at) && !isUpdated(pedido.id),
                        'border-[#efa324] ring-4 ring-[#efa324]/20 z-10 scale-[1.02]': isUpdated(pedido.id)
                     }">
                    
                    {{-- HEADER DO CARD --}}
                    <div class="p-4 bg-white/5 flex justify-between items-start border-b border-white/5">
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-tighter leading-none">
                                Mesa <span x-text="pedido.mesa"></span>
                            </h2>
                            
                            <div class="flex flex-col gap-1 mt-2">
                                {{-- Badge: Alterado --}}
                                <template x-if="isUpdated(pedido.id)">
                                    <span class="text-[10px] font-black bg-[#efa324] text-black px-2 py-0.5 rounded uppercase tracking-widest animate-bounce w-max">
                                        ⚠ Alterado
                                    </span>
                                </template>
                                
                                {{-- Badge: Novo --}}
                                <template x-if="!isUpdated(pedido.id) && pedido.status === 'pendente' && isRecentItem(pedido.created_at)">
                                    <span class="text-[10px] font-black bg-[#7ed957] text-black px-2 py-0.5 rounded uppercase tracking-widest animate-pulse w-max">
                                        ★ Novo Pedido
                                    </span>
                                </template>

                                {{-- ID --}}
                                <span class="text-xs text-gray-500 font-mono" 
                                      x-text="'#' + pedido.id" 
                                      x-show="!isUpdated(pedido.id) && !(pedido.status === 'pendente' && isRecentItem(pedido.created_at))">
                                </span>
                            </div>
                        </div>

                        {{-- Timer --}}
                        <div class="text-right">
                            <div :class="getSlaClasses(pedido.created_at)" x-text="formatTimer(pedido.created_at)"></div>
                            <div class="text-[10px] uppercase font-bold tracking-wider mt-1"
                                 :class="pedido.status === 'preparo' ? 'text-[#bf7854] animate-pulse' : 'text-gray-500'"
                                 x-text="pedido.status === 'pendente' ? 'AGUARDANDO' : 'PREPARANDO'">
                            </div>
                        </div>
                    </div>

                    {{-- LISTA DE ITENS --}}
                    <div class="p-4 flex-grow overflow-y-auto custom-scroll">
                        <ul class="space-y-4">
                            <template x-for="item in pedido.itens" :key="item.id">
                                <li class="flex justify-between items-start border-b border-gray-800 pb-3 last:border-0 transition-all duration-500"
                                    :class="{
                                        /* Foco nos novos itens (opacidade nos antigos) */
                                        'opacity-40 grayscale': pedido.status === 'preparo' && (hasNewItems(pedido) && !isItemNew(item, pedido)),
                                        
                                        /* Destaque LARANJA para Adicionais */
                                        'bg-[#efa324]/10 p-2 rounded-lg -mx-2 border-l-4 border-[#efa324]': isItemNew(item, pedido)
                                    }">
                                    
                                    <div class="flex-1 pr-3">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xl md:text-2xl font-bold leading-tight block mb-1" 
                                                :class="isItemNew(item, pedido) ? 'text-white' : 'text-gray-300'"
                                                x-text="item.nome_produto"></span>
                                            
                                            {{-- Tag ADICIONADO --}}
                                            <template x-if="isItemNew(item, pedido) && pedido.status === 'preparo'">
                                                <span class="bg-[#efa324] text-black text-[9px] font-black px-1.5 py-0.5 rounded uppercase animate-pulse">
                                                    ADICIONADO
                                                </span>
                                            </template>
                                        </div>

                                        <template x-if="item.observacao">
                                            <div class="text-sm font-bold px-2 py-1 rounded inline-block mt-1 shadow-sm"
                                                 :class="isItemNew(item, pedido) ? 'bg-[#efa324] text-black' : 'bg-gray-800 text-[#efa324]'">
                                                <span x-text="'⚠️ ' + item.observacao"></span>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    {{-- Quantidade --}}
                                    <span class="font-mono text-2xl px-3 py-2 rounded-lg font-black min-w-[3rem] text-center transition-colors duration-300"
                                        :class="{
                                            'bg-[#efa324] text-black shadow-[0_0_15px_rgba(239,163,36,0.4)] scale-110': isItemNew(item, pedido),
                                            'bg-[#0c0c0e] text-[#7ed957] border border-gray-800': !isItemNew(item, pedido)
                                        }"
                                        x-text="item.quantidade"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- ACTIONS FOOTER --}}
                    <div class="p-3 mt-auto bg-[#0c0c0e]/50 border-t border-gray-800 flex flex-col gap-3">
                        <button @click="avancarStatus(pedido)" 
                                class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-sm transition-all active:scale-[0.98] shadow-lg flex items-center justify-center gap-2"
                                :class="{
                                    'bg-[#2a2a30] hover:bg-[#33333a] text-white border border-gray-700': pedido.status === 'pendente',
                                    'bg-[#bf7854] hover:bg-[#a66545] text-white shadow-[#bf7854]/20': pedido.status === 'preparo'
                                }">
                            <span x-text="pedido.status === 'pendente' ? 'INICIAR PREPARO' : '✔ FINALIZAR PEDIDO'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- 
        =======================================================================
        BARRA DE "DESFAZER" (MODO DARK PRO)
        =======================================================================
    --}}
    <div x-show="undoData" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-6 left-0 w-full z-50 px-4 flex justify-center pointer-events-none">
        
        <div class="pointer-events-auto bg-[#18181b] border border-gray-700 text-white w-full max-w-2xl rounded-2xl shadow-2xl p-4 flex items-center justify-between ring-1 ring-white/10 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="relative w-10 h-10 flex items-center justify-center">
                     <svg class="animate-spin w-full h-full text-[#7ed957]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="absolute text-[10px] font-bold text-[#7ed957]">3s</span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Finalizando</p>
                    <p class="text-xl font-black leading-none mt-0.5 text-white">MESA <span x-text="undoData?.mesa"></span></p>
                </div>
            </div>
            <button @click="desfazerFinalizacao()" class="bg-[#7ed957] text-black hover:bg-[#6ec24a] active:scale-95 transition-all px-6 py-2.5 rounded-xl font-black text-sm uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-[#7ed957]/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                DESFAZER
            </button>
        </div>
    </div>

</div>
@endsection