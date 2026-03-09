@extends('layouts.kds')
@section('title', __('kds.manager'))
@section('body-class', 'bg-[#0c0c0e] font-sans overflow-hidden')

@section('content')

{{-- Container Principal --}}
<div x-data="gerenteApp()" x-init="init()" class="h-full flex flex-col overflow-hidden no-select relative bg-[#0c0c0e]">

    {{-- HEADER DO GERENTE --}}
    <div class="h-16 bg-[#27272a] border-b border-gray-700 flex items-center justify-between px-5 md:px-6 z-30 shrink-0 shadow-md relative">

        {{-- Lado Esquerdo: Título + Stats --}}
        <div class="flex items-center gap-4 md:gap-6 min-w-0">

            <div class="flex flex-col">
                <h1 class="text-lg font-black tracking-tight text-[#7ed957] uppercase leading-none truncate">{{ __('kds.manager') }}</h1>
                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest md:hidden truncate">{{ __('kds.table_control') }}</span>
            </div>

            {{-- Contadores Rápidos (Apenas Desktop) --}}
            <div class="hidden md:flex gap-3 border-l border-gray-600 pl-6">
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#7ed957]"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">{{ __('kds.free_tables') }}: <span x-text="contarLivres()" class="text-white"></span></span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#bf7854] animate-pulse"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">{{ __('kds.occupied_tables') }}: <span x-text="contarOcupadas()" class="text-white"></span></span>
                </div>
            </div>
        </div>

        {{-- Lado Direito: Relógio + Status --}}
        <div class="flex items-center shrink-0">
            <div class="text-right flex flex-col justify-center cursor-pointer active:scale-95 transition-transform select-none" @click="reconnect()" title="{{ __('kds.tap_to_refresh') }}">
                <div class="text-lg md:text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock"></div>
                <div class="text-[9px] md:text-[10px] font-bold uppercase tracking-widest mt-0.5 flex items-center justify-end gap-1.5"
                     :class="{ 'text-[#7ed957]': healthStatus === 'online', 'text-[#efa324]': healthStatus === 'partial', 'text-[#ef4444]': healthStatus === 'offline' }">
                    <span class="w-2 h-2 rounded-full" :class="{ 'bg-[#7ed957] animate-pulse': healthStatus === 'online', 'bg-[#efa324] animate-pulse': healthStatus === 'partial', 'bg-[#ef4444]': healthStatus === 'offline' }"></span>
                    <span x-text="healthStatus === 'online' ? __t('online') : healthStatus === 'partial' ? __t('no_realtime') : __t('offline_status')"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- 
        [FIX DE LAYOUT] WRAPPER FLEXÍVEL
        Ocupa todo o espaço restante abaixo do header. 
        Isola o contexto de rolagem para não interferir no cabeçalho.
    --}}
    <div class="flex-1 relative w-full overflow-hidden">

        {{-- LOADING STATE --}}
        {{-- Agora posicionado com absolute inset-0 dentro do wrapper --}}
        <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-[#0c0c0e] z-10">
            <div class="w-12 h-12 border-4 border-gray-800 border-t-[#7ed957] rounded-full animate-spin mb-4"></div>
            <span class="text-xs font-bold uppercase tracking-widest animate-pulse">{{ __('kds.syncing') }}</span>
        </div>

        {{-- GRID DE MESAS --}}
        {{-- Agora posicionado com absolute inset-0 para rolagem perfeita --}}
        <div x-show="ready" class="absolute inset-0 overflow-y-auto p-4 scrollbar-hide bg-[#0c0c0e]" style="scrollbar-width: none;">
            <template x-if="mesas.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-500 opacity-50">
                    <span class="text-sm font-bold uppercase tracking-widest">{{ __('kds.no_tables_config') }}</span>
                </div>
            </template>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3 pb-20">
                <template x-for="mesa in mesas" :key="mesa.numero">
                    <div @click="abrirMesa(mesa)" 
                         class="relative rounded-xl p-3 flex flex-col justify-between transition-all duration-200 h-32 border-2 bg-[#18181b]" 
                         :class="getMesaClasses(mesa)">
                        
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-60 text-gray-400" x-text="__t('table')"></span>
                                <template x-if="mesa.label">
                                    <span class="text-[9px] font-bold text-[#efa324] uppercase tracking-wider" x-text="mesa.label"></span>
                                </template>
                            </div>
                            <span class="text-xl font-black font-mono leading-none text-white" x-text="mesa.numero"></span>
                        </div>
                        
                        <template x-if="mesa.pedidos.length > 0">
                            <div class="flex flex-col items-end">
                                <span class="text-lg font-black font-mono tracking-tight text-[#7ed957]" x-text="formatMoney(mesa.total)"></span>
                                <div x-show="mesa.pedidos.some(p => p.status === 'pronto')" class="mt-1 flex items-center gap-1.5 bg-[#7ed957]/20 text-[#7ed957] border border-[#7ed957]/30 px-2 py-0.5 rounded text-[10px] font-black uppercase">
                                    ✓ <span x-text="__t('ready')"></span>
                                </div>
                                <div x-show="!mesa.pedidos.some(p => p.status === 'pronto')" class="mt-1 flex items-center gap-1.5 bg-[#bf7854]/20 text-[#bf7854] border border-[#bf7854]/30 px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                                    <span x-text="mesa.pedidos.reduce((acc, p) => acc + p.itens.length, 0)"></span>
                                    <span x-text="__t('items')"></span>
                                </div>
                                {{-- Garçom(s) que serve(m) a mesa --}}
                                <template x-if="mesa.garcons && mesa.garcons.length > 0">
                                    <div class="mt-1 text-[8px] font-bold text-gray-500 uppercase tracking-wide truncate max-w-full">
                                        👤 <span x-text="mesa.garcons.join(' · ')"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="mesa.pedidos.length === 0">
                            <div class="absolute inset-0 flex items-center justify-center opacity-20">
                                <div class="text-center">
                                    <span class="text-sm font-bold uppercase tracking-widest border border-gray-600 text-gray-500 px-2 py-1 rounded" x-text="__t('free')"></span>
                                    <div class="text-[9px] font-mono mt-1 text-gray-600" x-text="mesa.capacidade + ' ' + __t('seats')"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </div> {{-- FIM DO WRAPPER --}}

    {{-- =======================================================================
         MODAL DETALHES UNIFICADO (FIXED)
         ======================================================================= --}}
    {{-- Z-Index 55 mantido para ficar acima do Header (50) --}}
    <div x-show="mesaSelecionada"
         class="fixed inset-0 z-[55] flex items-end sm:items-center justify-center bg-black/90 backdrop-blur-sm p-0 sm:p-4 safe-area-pt"
         x-transition.opacity
         style="display: none;">

        <div @click.away="mesaSelecionada = null" class="bg-[#18181b] w-full max-w-2xl sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[95vh] sm:h-auto sm:max-h-[85vh] animate-fade-in-up border border-gray-800">

            {{-- Header Modal --}}
            <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#202025] shrink-0">
                <button @click="mesaSelecionada = null" class="shrink-0 h-12 w-12 rounded-xl bg-gray-800 border border-gray-700 hover:bg-gray-700 hover:border-[#7ed957] flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </button>
                <div class="text-right">
                    <span class="text-xs font-bold text-[#7ed957] uppercase tracking-widest">{{ __('kds.management') }}</span>
                    <h2 class="text-3xl font-black text-white leading-none"><span x-text="__t('table')"></span> <span x-text="mesaSelecionada?.numero"></span></h2>
                    <template x-if="mesaSelecionada?.garcons?.length > 0">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                            👤 <span x-text="mesaSelecionada.garcons.join(' · ')"></span>
                        </p>
                    </template>
                </div>
            </div>

            {{-- LISTA DE ITENS --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#18181b] scrollbar-hide">
                <template x-if="mesaSelecionada">
                    <div class="space-y-3">
                        
                        <div x-show="itensConsolidados.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-600">
                            <p class="text-sm font-bold uppercase tracking-wider">{{ __('kds.no_orders_table') }}</p>
                        </div>

                        <template x-for="item in itensConsolidados" :key="item.chaveUnica">
                            {{-- CARD DO ITEM --}}
                            <div class="flex flex-col group bg-[#202025] rounded-xl border border-gray-800 hover:border-gray-600 transition-all shadow-sm relative overflow-hidden">
                                <div class="p-4 flex justify-between items-start">
                                    <div class="flex gap-4 items-start w-full">
                                        
                                        {{-- Botão Editar Quantidade --}}
                                        <button @click="abrirEdicaoLote(item)" class="shrink-0 w-12 h-12 rounded-xl bg-[#2a2a30] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] flex items-center justify-center transition-all shadow-sm active:scale-95 group-hover:bg-[#33333a]">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2 flex-wrap">
                                                <span class="font-black text-2xl text-white" x-text="item.quantidade + 'x'"></span>
                                                <span class="font-bold text-gray-200 text-lg leading-tight" x-text="item.nome_produto"></span>
                                            </div>
                                            
                                            {{-- Status Chips --}}
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <template x-for="(qtd, status) in item.statusBreakdown" :key="status">
                                                    <span x-show="qtd > 0" 
                                                          class="text-[10px] px-2 py-0.5 rounded uppercase font-black tracking-wide border flex items-center gap-1.5"
                                                          :class="{
                                                              'bg-[#efa324]/10 text-[#efa324] border-[#efa324]/20': status === 'pendente',
                                                              'bg-[#bf7854]/10 text-[#bf7854] border-[#bf7854]/20 animate-pulse': status === 'preparo',
                                                              'bg-[#7ed957]/10 text-[#7ed957] border-[#7ed957]/20': status === 'pronto'
                                                          }">
                                                        <span class="w-1.5 h-1.5 rounded-full" :class="{'bg-[#efa324]': status === 'pendente', 'bg-[#bf7854]': status === 'preparo', 'bg-[#7ed957]': status === 'pronto'}"></span>
                                                        <span x-text="qtd + ' ' + formatStatus(status)"></span>
                                                    </span>
                                                </template>
                                            </div>

                                            {{-- Observações --}}
                                            <div class="flex flex-wrap gap-2 mt-2" x-show="item.observacoesUnicas.size > 0">
                                                <template x-for="obs in Array.from(item.observacoesUnicas)">
                                                    <span class="text-[9px] text-gray-400 italic flex items-center gap-1">
                                                        <span>📝</span> <span x-text="obs"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end pl-2">
                                        <span class="font-mono font-bold text-gray-400 text-lg whitespace-nowrap" x-text="formatMoney(item.preco * item.quantidade)"></span>
                                        <span class="text-[9px] text-gray-600 uppercase font-bold tracking-wide mt-1" x-text="formatMoney(item.preco) + ' un'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Footer Modal --}}
            <div class="p-4 bg-[#202025] border-t border-gray-800 shrink-0 space-y-3">
                <div class="flex justify-between items-end">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('kds.grand_total') }}</span>
                    <span class="text-4xl font-black text-[#7ed957] kds-font-mono tracking-tighter" x-text="formatMoney(mesaSelecionada?.total || 0)"></span>
                </div>
                
                <div class="grid grid-cols-12 gap-2">
                    <button @click="abrirAdicionarItem()" class="col-span-3 h-14 bg-transparent border-2 border-dashed border-gray-600 text-gray-400 rounded-xl font-bold uppercase text-[10px] hover:border-[#7ed957] hover:text-[#7ed957] transition-all flex flex-col items-center justify-center leading-none gap-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span>{{ __('kds.add') }}</span>
                    </button>
                    
                    <button @click="imprimirContaConsolidada()" class="col-span-3 h-14 bg-[#2a2a30] border border-gray-700 text-white rounded-xl font-bold uppercase text-xs hover:bg-gray-700 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        <span>{{ __('kds.print') }}</span>
                    </button>

                    <button @click="abrirConfirmacaoFecho()" class="col-span-6 h-14 bg-[#7ed957] text-black rounded-xl font-black uppercase text-sm shadow-lg shadow-[#7ed957]/20 active:scale-[0.98] hover:bg-[#6ec24a] transition-all flex items-center justify-center gap-2">
                        <span>{{ __('kds.close_bill') }}</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUB-MODAL 1: GERENCIAR ITEM --}}
    <div x-show="editandoItem" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" 
         x-transition.opacity 
         @click.self.stop="editandoItem = null" 
         style="display: none;">
        
        <div class="bg-[#18181b] w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up border border-gray-800" @click.stop>
            <div class="p-6 text-center">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">{{ __('kds.manage_item') }}</h3>
                <h2 class="text-2xl font-black text-white leading-tight mb-2" x-text="editandoItem?.nome_produto"></h2>
                <p class="text-xs text-gray-400 mb-6">{{ __('kds.adjust_qty') }}</p>
                
                <div class="flex items-center justify-center gap-6 mb-8">
                    <button @click="removerUmItemDoLote()" class="w-16 h-16 rounded-2xl bg-[#2a2a30] text-white text-3xl font-black hover:bg-red-500/20 active:scale-95 transition-all">-</button>
                    <div class="text-center">
                        <span class="block text-5xl font-black font-mono tracking-tighter text-[#7ed957]" x-text="editandoItem?.quantidade"></span>
                        <span class="text-[10px] font-bold text-gray-500 uppercase">{{ __('kds.total') }}</span>
                    </div>
                    <button @click="adicionarMaisUmNoLote()" class="w-16 h-16 rounded-2xl bg-[#7ed957] text-black text-3xl font-black hover:bg-[#6ec24a] active:scale-95 transition-all">+</button>
                </div>

                <div class="space-y-3">
                    <button @click="removerTodosDoLote()" class="w-full py-4 text-red-500 bg-red-500/10 border border-red-500/20 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-red-500/20 transition-colors">
                        {{ __('kds.delete_all') }} (<span x-text="editandoItem?.quantidade"></span>)
                    </button>
                    <button @click="editandoItem = null" class="w-full py-3 text-gray-400 text-xs font-bold uppercase hover:text-white">
                        {{ __('kds.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUB-MODAL 2: ADICIONAR PRODUTO (réplica exata do garçom) --}}
    <div x-show="adicionandoItem"
         class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-black/80 backdrop-blur-md"
         x-transition.opacity
         @click.self.stop="fecharAdicionarItem()"
         style="display: none;">

        <div class="bg-[#0c0c0e] w-full max-w-lg rounded-t-[2rem] sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[95vh] sm:h-auto sm:max-h-[85vh] border border-gray-800 relative" @click.stop>

            {{-- TOOLBAR (réplica exata do garçom) --}}
            <div class="bg-[#27272a] shadow-md z-30 shrink-0 pb-3 border-b border-gray-700">
                <div class="px-4 pt-5 pb-3 flex gap-3 items-center">
                    <button @click="fecharAdicionarItem()" class="shrink-0 h-14 w-14 rounded-xl bg-[#18181b] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] flex items-center justify-center active:scale-95 transition-all">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </button>

                    <div class="flex-1 relative">
                        <input type="text" x-model="searchProduto" :placeholder="__t('search_product')"
                               class="w-full pl-4 pr-4 h-14 bg-[#18181b] border border-gray-700 text-white rounded-xl font-bold text-lg placeholder-gray-600 focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all">
                    </div>

                    <button class="shrink-0 flex flex-col items-center justify-center bg-[#18181b] border border-gray-600 text-[#7ed957] w-14 h-14 rounded-xl active:scale-95 transition-transform shadow-lg group" disabled>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-white opacity-60 group-hover:opacity-100" x-text="__t('table').toUpperCase()"></span>
                        <span class="text-2xl font-black leading-none font-mono" x-text="mesaSelecionada?.numero"></span>
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

            {{-- LISTA DE PRODUTOS --}}
            <div class="flex-1 overflow-y-auto px-4 pt-4 scrollbar-hide space-y-3 bg-[#0c0c0e]"
                 :style="'padding-bottom:' + (carrinhoAdd.length > 0 ? '140px' : '16px') + ';transition:padding-bottom .3s ease'">
                <template x-for="prod in produtosFiltrados" :key="prod.id">
                    <button @click="addProdutoAoCarrinho(prod)"
                            class="w-full bg-[#18181b] p-4 rounded-xl shadow-sm border border-gray-800 hover:border-[#7ed957] flex justify-between items-center active:scale-[0.98] transition-all group relative overflow-hidden touch-manipulation">
                        <div class="text-left z-10">
                            <span class="block text-lg font-bold text-white leading-tight" x-text="prod.nome"></span>
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-wider bg-[#0c0c0e] border border-gray-800 px-2 py-0.5 rounded mt-1 inline-block" x-text="prod.categoria"></span>
                        </div>
                        <div class="flex flex-col items-end z-10">
                            <span class="font-mono font-black text-[#7ed957] text-xl" x-text="formatMoney(prod.preco)"></span>
                            <div x-show="getQtyCarrinho(prod.id) > 0" x-transition.scale
                                 class="mt-1 bg-[#bf7854] text-white text-xs font-bold px-2 py-1 rounded-lg shadow-md flex items-center gap-1 border border-[#bf7854]/50">
                                <span x-text="getQtyCarrinho(prod.id)"></span>
                                <span class="text-[8px] uppercase" x-text="__t('in_order')"></span>
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-[#7ed957] opacity-0 group-active:opacity-10 transition-opacity"></div>
                    </button>
                </template>
            </div>

            {{-- CARRINHO BOTTOM SHEET (réplica exata do garçom) --}}
            <div x-show="addCartOpen" @click="addCartOpen = false" x-transition.opacity class="absolute inset-0 bg-black/80 backdrop-blur-sm z-[60]"></div>

            <div class="absolute bottom-0 left-0 w-full bg-[#18181b] z-[70] rounded-t-[2rem] shadow-[0_-10px_60px_-10px_rgba(0,0,0,0.5)] border-t border-gray-800 transition-transform duration-300 ease-out flex flex-col max-h-[90%]"
                 :class="addCartOpen ? 'translate-y-0' : (carrinhoAdd.length > 0 ? 'translate-y-[calc(100%-120px)]' : 'translate-y-full')">

                {{-- CABEÇALHO DO CARRINHO (puxador + info, min 120px = match translate) --}}
                <div @click="addCartOpen = !addCartOpen"
                     class="shrink-0 bg-[#18181b] rounded-t-[2rem] cursor-pointer touch-manipulation hover:bg-[#1f1f23] active:bg-[#1f1f23] transition-colors z-20 relative flex flex-col"
                     :class="addCartOpen && 'border-b border-gray-800'"
                     style="min-height: 120px">

                    <div class="w-full flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 bg-gray-600 rounded-full"></div>
                    </div>

                    <div class="flex-1 px-4 sm:px-6 pb-3 flex justify-between items-center gap-2">
                        <div class="flex flex-col justify-center flex-1 min-w-0">
                            <span class="text-[9px] sm:text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-0.5 sm:mb-1 truncate" x-text="__t('estimated_total')">
                            </span>
                            <span class="text-2xl sm:text-3xl font-black text-[#7ed957] font-mono tracking-tighter leading-none truncate"
                                  x-text="formatMoney(totalCarrinhoAdd)">
                            </span>
                        </div>

                        <div class="flex items-center shrink-0">
                            <div class="bg-[#7ed957] text-black rounded-xl px-4 sm:px-5 py-3 flex items-center gap-2 sm:gap-3 shadow-lg shadow-[#7ed957]/20 active:scale-95 transition-transform"
                                 x-show="!addCartOpen">
                                <span class="font-black text-[10px] sm:text-xs uppercase tracking-widest whitespace-nowrap" x-text="__t('view_order')">
                                </span>
                                <span class="bg-black text-[#7ed957] text-[10px] font-mono font-bold px-2 py-0.5 rounded"
                                      :class="{ 'animate-badge': badgeAnimAdd }"
                                      x-text="totalItensAdd">
                                </span>
                            </div>

                            <div class="bg-gray-800 rounded-full p-3 text-gray-400 border border-gray-700 hover:text-white transition-colors"
                                 x-show="addCartOpen"
                                 style="display: none;">
                                 <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                 </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Itens do Carrinho --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-[#0c0c0e] scrollbar-hide" x-show="addCartOpen">
                    <template x-for="(item, index) in carrinhoAdd" :key="item.uuid">
                        <div class="bg-[#18181b] p-4 rounded-2xl border border-gray-800 shadow-sm animate-fade-in-up">
                            <div class="flex justify-between items-start mb-3">
                                <span class="font-black text-white text-lg leading-tight w-2/3" x-text="item.nome"></span>
                                <span class="font-mono font-bold text-[#7ed957]" x-text="formatMoney(item.preco * item.qtd)"></span>
                            </div>
                            <div class="flex flex-col gap-3">
                                <input type="text" x-model="item.obs" :placeholder="__t('any_notes')" class="w-full pl-3 pr-3 py-2 bg-[#2a2a30] border-none rounded-xl text-sm font-semibold text-white placeholder-gray-500 focus:ring-1 focus:ring-[#7ed957] transition-all">
                                <div class="flex items-center justify-between mt-1">
                                    <button @click="removerDoCarrinho(index)" class="text-red-500 text-xs font-bold uppercase tracking-wider hover:text-red-400 px-2 py-1">{{ __('kds.remove') }}</button>
                                    <div class="flex items-center gap-4 bg-[#0c0c0e] border border-gray-800 rounded-xl p-1.5">
                                        <button @click="decrementarAdd(index)" class="w-10 h-10 bg-[#18181b] rounded-lg text-xl font-black text-gray-400 hover:text-white flex items-center justify-center touch-manipulation">-</button>
                                        <span class="font-mono font-black text-xl min-w-[1.5rem] text-center text-white" x-text="item.qtd"></span>
                                        <button @click="incrementarAdd(index)" class="w-10 h-10 bg-[#7ed957] text-black rounded-lg text-xl font-black active:scale-95 flex items-center justify-center touch-manipulation">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="h-20"></div>
                </div>

                {{-- Botão Enviar --}}
                <div class="p-4 bg-[#18181b] border-t border-gray-800 absolute bottom-0 w-full" x-show="addCartOpen">
                    <button @click="enviarItensAdicionados()" :disabled="carrinhoAdd.length === 0 || enviandoAdd" class="w-full h-16 bg-[#7ed957] text-black rounded-2xl font-black text-xl shadow-xl shadow-[#7ed957]/20 uppercase tracking-widest hover:bg-[#6ec24a] active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center gap-3">
                        <span x-show="enviandoAdd" class="animate-spin h-6 w-6 border-4 border-black border-t-transparent rounded-full"></span>
                        <span x-text="enviandoAdd ? __t('sending') : __t('send_order')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUB-MODAL 3: CONFIRMAR FECHO --}}
    <div x-show="confirmandoFecho" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" 
         x-transition.opacity 
         @click.self.stop="confirmandoFecho = false" 
         style="display: none;">
        
        <div class="bg-[#18181b] w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up border border-[#7ed957]/30" @click.stop>
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-[#7ed957]/10 text-[#7ed957] rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-[#7ed957]/20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-2xl font-black text-white mb-2">{{ __('kds.receive_payment') }}</h3>
                <p class="text-gray-400 font-medium mb-8">{{ __('kds.confirm_receipt') }} <span class="text-white font-bold"><span x-text="__t('table')"></span> <span x-text="mesaSelecionada?.numero"></span></span>.<br><span class="block mt-4 text-4xl font-black text-[#7ed957] kds-font-mono" x-text="formatMoney(mesaSelecionada?.total || 0)"></span></p>
                <div class="flex flex-col gap-3">
                    <button @click="fecharContaConfirmada()" class="h-16 bg-[#7ed957] text-black font-black rounded-2xl shadow-xl shadow-[#7ed957]/20 active:scale-[0.98] uppercase tracking-widest text-sm flex items-center justify-center gap-3 hover:bg-[#6ec24a]">
                        <span>{{ __('kds.confirm_payment') }}</span>
                    </button>
                    <button @click="confirmandoFecho = false" class="h-14 bg-[#2a2a30] text-gray-400 font-bold rounded-2xl hover:bg-[#33333a] hover:text-white uppercase tracking-widest text-xs">{{ __('kds.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection