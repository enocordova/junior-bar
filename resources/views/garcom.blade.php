@extends('layouts.kds')
@section('title', 'Garçom')
@section('body-class', 'bg-[#0c0c0e] font-sans overflow-hidden')

@section('content')
<style>
    /* Utilitários de Scroll e Animação */
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    
    @keyframes badge-pop { 
        0% { transform: scale(1); } 
        50% { transform: scale(1.6); } 
        100% { transform: scale(1); } 
    }
    .animate-badge { 
        animation: badge-pop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        background-color: #7ed957 !important; 
        color: black !important; 
    }
</style>

{{-- CONTAINER PRINCIPAL --}}
<div x-data="garcomPro()" x-init="init()" class="h-[calc(100dvh-70px)] flex flex-col overflow-hidden relative">

    {{-- =======================================================================
         HEADER UNIFICADO (Topo)
         ======================================================================= --}}
    <div class="h-16 bg-[#4d4a52] border-b border-gray-700 flex items-center justify-between px-6 z-20 shrink-0 shadow-md">
        
        {{-- LADO ESQUERDO: Título + Contadores --}}
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-black tracking-tight text-[#7ed957]">
                GARÇOM
            </h1>
            
            {{-- Contadores --}}
            <div class="hidden md:flex gap-3 border-l border-gray-600 pl-6">
                {{-- Livres --}}
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#7ed957]"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">
                        Livres: <span x-text="mesasLivres" class="text-white"></span>
                    </span>
                </div>
                {{-- Ocupadas --}}
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#bf7854] animate-pulse"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">
                        Ocupadas: <span x-text="mesasOcupadas" class="text-white"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- LADO DIREITO: Relógio + Status --}}
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock">--:--</div>
                <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5 transition-colors duration-300" 
                     :class="online ? 'text-[#7ed957]' : 'text-[#ef4444]'" 
                     x-text="online ? '● ONLINE' : '○ OFFLINE'"></div>
            </div>
        </div>
    </div>

    {{-- =======================================================================
         FASE 1: GRADE DE MESAS (Dinâmica)
         ======================================================================= --}}
    <div x-show="!mesaSelecionada" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         class="absolute inset-0 top-16 z-10 bg-[#0c0c0e] flex flex-col"> 
        
        <div class="flex-1 overflow-y-auto p-4 scrollbar-hide pb-20">
            {{-- Aviso se não houver mesas --}}
            <template x-if="listaMesas.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <span class="text-xs uppercase font-bold tracking-widest">Nenhuma mesa encontrada</span>
                </div>
            </template>

            <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
                <template x-for="mesa in listaMesas" :key="mesa.numero">
                    <button @click="selecionarMesa(mesa)"
                            class="aspect-square rounded-2xl flex flex-col items-center justify-center border transition-all duration-100 touch-manipulation relative overflow-hidden group shadow-lg"
                            :class="mesa.ocupada 
                                ? 'bg-[#18181b] border-[#bf7854] text-white shadow-[0_0_10px_rgba(191,120,84,0.2)]' 
                                : 'bg-[#18181b] border-gray-800 text-gray-400 hover:border-[#7ed957] hover:text-white'">
                        
                        <span class="text-2xl font-black font-mono z-10" x-text="mesa.numero"></span>
                        
                        <template x-if="mesa.label">
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mt-1 z-10" x-text="mesa.label"></span>
                        </template>
                        
                        <template x-if="mesa.ocupada">
                            <div class="flex flex-col items-center mt-1 z-10">
                                <span class="text-[8px] font-bold text-[#bf7854] uppercase tracking-wider bg-[#bf7854]/10 px-1.5 py-0.5 rounded">Ocupada</span>
                            </div>
                        </template>

                        <div class="absolute inset-0 bg-[#7ed957] opacity-0 group-active:opacity-20 transition-opacity"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- =======================================================================
         FASE EXTRA: MONITOR DA MESA (Acompanhamento)
         ======================================================================= --}}
    <div x-show="painelMesaOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-10"
         class="absolute inset-0 top-16 z-50 bg-[#0c0c0e] flex flex-col animate-fade-in-up">

        {{-- Header do Monitor --}}
        <div class="h-16 bg-[#18181b] border-b border-gray-800 flex items-center justify-between px-6 shrink-0">
            <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status da Mesa</span>
                <h2 class="text-3xl font-black text-white leading-none font-mono" x-text="'MESA ' + mesaSelecionada"></h2>
            </div>
            
            {{-- Botão Voltar do Monitor --}}
            <button @click="fecharMonitor()" 
                    class="shrink-0 h-14 w-14 rounded-xl bg-[#18181b] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] hover:bg-[#1f1f23] hover:shadow-[0_0_15px_rgba(126,217,87,0.1)] active:scale-95 transition-all duration-200 flex items-center justify-center group"
                        title="Voltar">
                    
                    {{-- Ícone de Seta (Muda de cor no hover do pai) --}}
                    <svg class="w-6 h-6 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
        </div>

        {{-- Lista de Pedidos Ativos --}}
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
                                 <span x-text="pedido.status === 'pendente' ? 'ENVIADO À COZINHA' : (pedido.status === 'preparo' ? 'EM PREPARO' : 'PRONTO P/ SERVIR')"></span>
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
                    <p class="text-xs uppercase font-bold">Nenhum pedido ativo encontrado.</p>
                </div>
            </template>
        </div>

        {{-- Rodapé do Monitor --}}
        <div class="p-4 bg-[#18181b] border-t border-gray-800 shrink-0 shadow-[0_-5px_15px_rgba(0,0,0,0.3)]">
            <div class="flex justify-between items-end mb-4 px-1">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total da Conta</span>
                <span class="text-3xl font-black text-[#7ed957] font-mono" x-text="'€ ' + (mesaDadosAtuais?.total || 0).toFixed(2)"></span>
            </div>
            
            <button @click="abrirCardapio(mesaSelecionada)" 
                    class="w-full py-4 bg-[#7ed957] text-black font-black rounded-xl uppercase tracking-widest text-sm shadow-lg shadow-[#7ed957]/20 flex items-center justify-center gap-2 hover:bg-[#6ec24a] active:scale-[0.98] transition-all">
                <span>+ Adicionar Novos Itens</span>
            </button>
        </div>
    </div>


    {{-- =======================================================================
         FASE 2: LISTA DE PRODUTOS (CARDÁPIO / ADICIONAR NOVO ITEM)
         ======================================================================= --}}
    <div x-show="mesaSelecionada && !painelMesaOpen" class="flex-1 flex flex-col h-full bg-[#0c0c0e]">
        
        {{-- Sub-Header --}}
        <div class="bg-[#4d4a52] shadow-md z-20 shrink-0 pb-3 border-b border-gray-700">
            <div class="px-4 py-3 flex gap-3 items-center">
                
                {{-- Botão da Mesa (Indicador / Sair para Grade) --}}
                <button @click="trocarMesa()" class="shrink-0 flex flex-col items-center justify-center bg-[#18181b] border border-gray-600 text-[#7ed957] w-14 h-14 rounded-xl active:scale-95 transition-transform shadow-lg group">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-white opacity-60 group-hover:opacity-100">MESA</span>
                    <span class="text-2xl font-black leading-none font-mono" x-text="mesaSelecionada"></span>
                </button>

                {{-- Campo de Busca --}}
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" x-model="search" placeholder="Buscar produto..." 
                           class="w-full pl-11 pr-4 h-14 bg-[#18181b] border border-gray-700 text-white rounded-xl font-bold text-lg placeholder-gray-600 focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all shadow-inner">
                </div>

                {{-- BOTÃO VOLTAR INTELIGENTE --}}
                <button @click="voltar()" 
                        class="shrink-0 h-14 w-14 rounded-xl bg-[#18181b] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] hover:bg-[#1f1f23] hover:shadow-[0_0_15px_rgba(126,217,87,0.1)] active:scale-95 transition-all duration-200 flex items-center justify-center group"
                        title="Voltar">
                    
                    {{-- Ícone de Seta (Muda de cor no hover do pai) --}}
                    <svg class="w-6 h-6 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
            </div>

            {{-- Categorias --}}
            <div class="flex overflow-x-auto gap-2 px-4 scrollbar-hide snap-x">
                <button @click="filtroCategoria = ''" 
                        class="px-5 py-2 rounded-full font-bold text-xs whitespace-nowrap transition-all border snap-start uppercase tracking-wider"
                        :class="filtroCategoria === '' ? 'bg-[#7ed957] text-black border-[#7ed957]' : 'bg-[#18181b] text-gray-400 border-gray-700 hover:text-white'">
                    TODOS
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

        {{-- Grid de Produtos --}}
        <div class="flex-1 overflow-y-auto p-4 pb-48 sm:pb-52 scrollbar-hide space-y-3 bg-[#0c0c0e]" id="product-list">
            <template x-for="produto in produtosFiltrados" :key="produto.id">
                <button @click="addProduto(produto)" 
                        class="w-full bg-[#18181b] p-4 rounded-xl shadow-sm border border-gray-800 hover:border-[#7ed957] flex justify-between items-center active:scale-[0.98] transition-all group relative overflow-hidden touch-manipulation">
                    
                    <div class="text-left z-10">
                        <span class="block text-lg font-bold text-white leading-tight" x-text="produto.nome"></span>
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-wider bg-[#0c0c0e] border border-gray-800 px-2 py-0.5 rounded mt-1 inline-block" x-text="produto.categoria"></span>
                    </div>

                    <div class="flex flex-col items-end z-10">
                        <span class="font-mono font-black text-[#7ed957] text-xl" x-text="'€ ' + Number(produto.preco).toFixed(2)"></span>
                        <div x-show="getQtyCart(produto.id) > 0" x-transition.scale
                             class="mt-1 bg-[#bf7854] text-white text-xs font-bold px-2 py-1 rounded-lg shadow-md flex items-center gap-1 border border-[#bf7854]/50">
                            <span x-text="getQtyCart(produto.id)"></span>
                            <span class="text-[8px] uppercase">no pedido</span>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-[#7ed957] opacity-0 group-active:opacity-10 transition-opacity"></div>
                </button>
            </template>
        </div>
    </div>

    {{-- =======================================================================
         CARRINHO / BOTTOM SHEET
         ======================================================================= --}}
    <div x-show="cartOpen" @click="cartOpen = false" x-transition.opacity class="fixed inset-0 bg-black/80 backdrop-blur-sm z-30"></div>

    {{-- [MELHORIA CRÍTICA] Adicionado '&& mesaSelecionada' na condição de visibilidade --}}
    {{-- Isso garante que a barra desapareça INSTANTANEAMENTE ao voltar para a grade de mesas --}}
    <div class="fixed bottom-0 left-0 w-full bg-[#18181b] z-40 rounded-t-[2rem] shadow-[0_-10px_60px_-10px_rgba(0,0,0,0.5)] border-t border-gray-800 transition-transform duration-300 ease-[cubic-bezier(0.2,0.8,0.2,1)] flex flex-col max-h-[90vh]"
         :class="cartOpen ? 'translate-y-0' : (carrinho.length > 0 && mesaSelecionada ? 'translate-y-[calc(100%-90px)]' : 'translate-y-full')">
        
        <div @click="cartOpen = !cartOpen" class="w-full flex justify-center pt-3 pb-2 cursor-pointer touch-manipulation">
            <div class="w-12 h-1.5 bg-gray-700 rounded-full"></div>
        </div>

        <div @click="cartOpen = !cartOpen" class="px-6 pb-6 flex justify-between items-center cursor-pointer border-b border-gray-800 bg-[#18181b]">
            <div>
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-0.5">Total Estimado</span>
                <span class="text-4xl font-black text-[#7ed957] font-mono tracking-tighter" x-text="'€ ' + totalCarrinho"></span>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-[#7ed957] text-black rounded-2xl px-5 py-3 flex items-center gap-3 shadow-lg shadow-[#7ed957]/20 transition-transform active:scale-95" x-show="!cartOpen">
                    <span class="font-bold text-sm uppercase tracking-wider">Ver Item(s)</span>
                    <span class="bg-black text-[#7ed957] text-xs font-mono font-bold px-2 py-0.5 rounded transition-all duration-200" :class="{ 'animate-badge': badgeAnim }" x-text="totalItens"></span>
                </div>
                <div class="bg-gray-800 rounded-full p-2 text-gray-400" x-show="cartOpen">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-[#0c0c0e] scrollbar-hide" x-show="cartOpen">
            <template x-for="(item, index) in carrinho" :key="item.uuid">
                <div class="bg-[#18181b] p-4 rounded-2xl border border-gray-800 shadow-sm animate-fade-in-up">
                    <div class="flex justify-between items-start mb-3">
                        <span class="font-black text-white text-lg leading-tight w-2/3" x-text="item.nome"></span>
                        <span class="font-mono font-bold text-[#7ed957]" x-text="'€ ' + (item.preco * item.qtd).toFixed(2)"></span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <input type="text" x-model="item.obs" placeholder="Alguma observação? (ex: Sem cebola)" class="w-full pl-3 pr-3 py-2 bg-[#2a2a30] border-none rounded-xl text-sm font-semibold text-white placeholder-gray-500 focus:ring-1 focus:ring-[#7ed957] transition-all">
                        <div class="flex items-center justify-between mt-1">
                            <button @click="removerItem(index)" class="text-red-500 text-xs font-bold uppercase tracking-wider hover:text-red-400 px-2 py-1">Remover</button>
                            <div class="flex items-center gap-4 bg-[#0c0c0e] border border-gray-800 rounded-xl p-1.5">
                                <button @click="decrementarItem(index)" class="w-10 h-10 bg-[#18181b] rounded-lg text-xl font-black text-gray-400 hover:text-white active:bg-gray-700 flex items-center justify-center touch-manipulation">-</button>
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
            <button @click="enviarPedido()" :disabled="carrinho.length === 0 || loading" class="w-full h-16 bg-[#7ed957] text-black rounded-2xl font-black text-xl shadow-xl shadow-[#7ed957]/20 uppercase tracking-widest hover:bg-[#6ec24a] active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                <span x-show="loading" class="animate-spin h-6 w-6 border-4 border-black border-t-transparent rounded-full"></span>
                <span x-text="loading ? 'ENVIANDO...' : 'ENVIAR PEDIDO'"></span>
            </button>
        </div>
    </div>

    {{-- MODAL GENÉRICO DE CONFIRMAÇÃO (CUSTOM UX) --}}
    <div x-show="confirmModal.open" 
         style="display: none;"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="confirmModal.open = false" 
             class="bg-[#18181b] w-full max-w-sm rounded-3xl border border-gray-800 shadow-2xl overflow-hidden animate-fade-in-up transform transition-all">
            
            <div class="p-6 text-center">
                {{-- Ícone de Alerta --}}
                <div class="mx-auto mb-4 w-16 h-16 bg-[#efa324]/10 rounded-full flex items-center justify-center border border-[#efa324]/20">
                    <svg class="w-8 h-8 text-[#efa324]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h3 class="text-xl font-black text-white mb-2" x-text="confirmModal.title">Tem Certeza?</h3>
                <p class="text-gray-400 text-sm font-medium leading-relaxed mb-6" x-text="confirmModal.message"></p>

                <div class="flex gap-3">
                    <button @click="confirmModal.open = false" 
                            class="flex-1 h-12 rounded-xl bg-[#2a2a30] text-gray-400 font-bold text-xs uppercase tracking-widest hover:bg-[#33333a] hover:text-white transition-colors">
                        Cancelar
                    </button>
                    <button @click="confirmModal.action(); confirmModal.open = false" 
                            class="flex-1 h-12 rounded-xl bg-[#7ed957] text-black font-black text-xs uppercase tracking-widest hover:bg-[#6ec24a] active:scale-95 transition-transform shadow-lg shadow-[#7ed957]/10">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function garcomPro() {
        return {
            mesaSelecionada: null,
            painelMesaOpen: false, 
            mesaDadosAtuais: null, 
            search: '',
            filtroCategoria: '',
            cartOpen: false,
            loading: false,
            produtos: [],
            carrinho: [],
            categoriasUnicas: [], 
            socket: null,
            badgeAnim: false,
            online: false,
            clock: '--:--',
            listaMesas: [], 

            // NOVO ESTADO DO MODAL
            confirmModal: {
                open: false,
                title: 'Atenção',
                message: '',
                action: null
            },

            get mesasLivres() { return this.listaMesas.filter(m => !m.ocupada).length; },
            get mesasOcupadas() { return this.listaMesas.filter(m => m.ocupada).length; },
            get totalItens() { return this.carrinho.reduce((acc, item) => acc + item.qtd, 0); },
            get totalCarrinho() { return this.carrinho.reduce((acc, item) => acc + (parseFloat(item.preco) * item.qtd), 0).toFixed(2); },
            get produtosFiltrados() {
                let lista = this.produtos;
                if (this.filtroCategoria !== '') lista = lista.filter(p => p.categoria === this.filtroCategoria);
                if (this.search !== '') lista = lista.filter(p => p.nome.toLowerCase().includes(this.search.toLowerCase()));
                return lista;
            },

            async init() {
                this.startClock();
                this.setupSocket();
                await this.carregarCardapio();
                this.fetchStatusMesas(); 
                setInterval(() => this.fetchStatusMesas(), 10000);
            },

            startClock() {
                setInterval(() => {
                    this.clock = new Date().toLocaleTimeString('pt-PT', {hour: '2-digit', minute:'2-digit'});
                }, 1000);
            },

            // NOVA FUNÇÃO HELPER (Substitui confirm nativo)
            askConfirm(mensagem, callbackConfirmacao, titulo = 'Tem certeza?') {
                this.vibrate(10);
                this.confirmModal.message = mensagem;
                this.confirmModal.title = titulo;
                this.confirmModal.action = callbackConfirmacao;
                this.confirmModal.open = true;
            },

            setupSocket() {
                if (window.AppConfig && window.AppConfig.socketUrl && typeof io !== 'undefined') {
                    this.socket = io(window.AppConfig.socketUrl);
                    this.socket.on('connect', () => this.online = true);
                    this.socket.on('disconnect', () => this.online = false);
                    this.socket.on('pedido_pronto', () => this.fetchStatusMesas());
                    this.socket.on('cozinha_novo_pedido', () => this.fetchStatusMesas());
                    this.socket.on('pedido_pago', () => this.fetchStatusMesas());
                    this.socket.on('cozinha_atualizar_status', () => {
                        if(this.painelMesaOpen) this.vibrate(50); 
                        this.fetchStatusMesas();
                    });
                }
            },

            async fetchStatusMesas() {
                try {
                    let responseConfig;
                    if (typeof window.axios !== 'undefined') {
                        responseConfig = await window.axios.get('/api/mesas-configuradas');
                    } else {
                        const raw = await fetch('/api/mesas-configuradas');
                        responseConfig = { data: await raw.json() };
                    }
                    
                    let mesasMapeadas = responseConfig.data.map(m => ({
                        numero: m.numero,
                        ocupada: false, 
                        capacidade: m.capacidade,
                        label: m.label,
                        pedidos: [], 
                        total: 0
                    }));

                    let responseStatus;
                    if (typeof window.axios !== 'undefined') {
                        responseStatus = await window.axios.get('/api/gerente/resumo-mesas');
                    } else {
                        const raw = await fetch('/api/gerente/resumo-mesas');
                        responseStatus = { data: await raw.json() };
                    }

                    if (responseStatus.data) {
                        responseStatus.data.forEach(m => {
                            if(m.pedidos && m.pedidos.length > 0) {
                                const mesaEncontrada = mesasMapeadas.find(t => t.numero == m.mesa);
                                if(mesaEncontrada) {
                                    mesaEncontrada.ocupada = true;
                                    mesaEncontrada.pedidos = m.pedidos;
                                    mesaEncontrada.total = m.total;
                                    
                                    if(this.mesaSelecionada == m.mesa && this.painelMesaOpen) {
                                        this.mesaDadosAtuais = mesaEncontrada;
                                    }
                                }
                            }
                        });
                    }

                    this.listaMesas = mesasMapeadas;

                } catch(e) { console.error("Erro sync mesas:", e); }
            },

            async carregarCardapio() {
                try {
                    let data = [];
                    if (typeof window.axios !== 'undefined') {
                        const res = await window.axios.get('/api/produtos');
                        data = res.data;
                    } else {
                        const res = await fetch('/api/produtos');
                        data = await res.json();
                    }
                    this.produtos = data;
                    this.categoriasUnicas = [...new Set(this.produtos.map(p => p.categoria))].sort();
                } catch (e) { console.error("Erro cardápio", e); }
            },

            selecionarMesa(mesaObj) {
                if (mesaObj.ocupada) {
                    this.mesaSelecionada = mesaObj.numero;
                    this.mesaDadosAtuais = mesaObj;
                    this.painelMesaOpen = true; 
                } else {
                    this.abrirCardapio(mesaObj.numero);
                }
            },

            abrirCardapio(numeroMesa) {
                this.mesaSelecionada = numeroMesa;
                this.painelMesaOpen = false; 
                this.carrinho = []; 
                this.vibrate(10);
            },

            fecharMonitor() {
                this.painelMesaOpen = false;
                this.mesaSelecionada = null;
                this.mesaDadosAtuais = null;
            },

            // [MELHORIA CRÍTICA] Lógica de troca de mesa blindada
            trocarMesa() {
                const acaoTroca = () => {
                    this.cartOpen = false;
                    this.painelMesaOpen = false;
                    // Usa splice para garantir reatividade imediata no Alpine
                    this.carrinho.splice(0); 
                    
                    this.mesaSelecionada = null;
                    this.search = '';
                    this.filtroCategoria = '';
                    this.fetchStatusMesas(); 
                };

                if(this.carrinho.length > 0) {
                    this.askConfirm("Sair limpará o pedido atual. Continuar?", acaoTroca, "Descartar Pedido?");
                } else {
                    acaoTroca();
                }
            },

            // [MELHORIA CRÍTICA] Lógica de voltar blindada
            voltar() {
                const acaoVoltar = () => {
                    this.cartOpen = false;
                    // Usa splice para garantir reatividade imediata no Alpine
                    this.carrinho.splice(0);

                    this.search = '';
                    this.filtroCategoria = '';

                    const mesaAtual = this.listaMesas.find(m => m.numero == this.mesaSelecionada);
                    if (mesaAtual && mesaAtual.ocupada) {
                        this.mesaDadosAtuais = mesaAtual;
                        this.painelMesaOpen = true; 
                    } else {
                        this.mesaSelecionada = null;
                        this.painelMesaOpen = false;
                    }
                };

                if (this.carrinho.length > 0) {
                    this.askConfirm("Descartar itens selecionados e voltar?", acaoVoltar, "Cancelar Itens?");
                } else {
                    acaoVoltar();
                }
            },

            addProduto(produto) {
                this.vibrate(5);
                this.badgeAnim = true;
                setTimeout(() => this.badgeAnim = false, 300); 
                const existing = this.carrinho.find(i => i.id_produto === produto.id && !i.obs);
                if (existing) { existing.qtd++; } else {
                    this.carrinho.push({ uuid: Date.now() + Math.random(), id_produto: produto.id, nome: produto.nome, preco: produto.preco, categoria: produto.categoria, qtd: 1, obs: '' });
                }
            },

            incrementarItem(index) { this.carrinho[index].qtd++; this.vibrate(5); },
            
            decrementarItem(index) { 
                if (this.carrinho[index].qtd > 1) { 
                    this.carrinho[index].qtd--; 
                    this.vibrate(5); 
                } else { 
                    this.askConfirm("Remover este item do pedido?", () => this.removerItem(index), "Remover Item");
                } 
            },
            
            removerItem(index) { this.carrinho.splice(index, 1); if (this.carrinho.length === 0) this.cartOpen = false; },
            getQtyCart(pid) { return this.carrinho.filter(i => i.id_produto === pid).reduce((acc, i) => acc + i.qtd, 0); },

            async enviarPedido() {
                this.loading = true;
                try {
                    const payload = { mesa: this.mesaSelecionada, itens: this.carrinho.map(i => ({ id_produto: i.id_produto, qtd: i.qtd, obs: i.obs })) };
                    let res;
                    if(typeof window.axios !== 'undefined') { res = await window.axios.post('/api/criar-pedido', payload); } 
                    else { const raw = await fetch('/api/criar-pedido', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(payload) }); res = { data: await raw.json() }; }

                    if (res.data.status === 'sucesso') {
                        this.cartOpen = false;
                        this.showSuccessToast();
                        this.carrinho = [];
                        
                        await this.fetchStatusMesas();
                        
                        const mesaAtualizada = this.listaMesas.find(m => m.numero == this.mesaSelecionada);
                        if(mesaAtualizada) {
                            this.selecionarMesa(mesaAtualizada);
                        }
                    }
                } catch (e) { alert("Erro ao enviar: " + e.message); } finally { this.loading = false; }
            },

            vibrate(ms) { if(navigator.vibrate) navigator.vibrate(ms); },
            showSuccessToast() {
                const toast = document.createElement('div');
                toast.className = 'fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-black/90 text-white px-8 py-6 rounded-3xl shadow-2xl z-50 flex flex-col items-center gap-4 animate-fade-in-up border border-[#7ed957]';
                toast.innerHTML = `<div class="bg-[#7ed957] rounded-full p-3 text-black"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></div><span class="font-black text-xl uppercase tracking-widest text-[#7ed957]">Enviado!</span>`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 1500);
            }
        }
    }
</script>
@endpush