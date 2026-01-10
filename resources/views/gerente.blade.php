@extends('layouts.kds')
@section('title', 'Gerente')
@section('body-class', 'bg-[#0c0c0e] font-sans overflow-hidden')

@section('content')
<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .no-select { user-select: none; -webkit-user-select: none; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
    
    /* Input Autocomplete Fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active{
        -webkit-box-shadow: 0 0 0 30px #18181b inset !important;
        -webkit-text-fill-color: white !important;
    }
</style>

<div x-data="gerenteApp()" x-init="init()" class="h-[calc(100vh-90px)] flex flex-col overflow-hidden no-select relative">
    
    {{-- HEADER --}}
    <div class="h-16 bg-[#4d4a52] border-b border-gray-700 px-6 flex justify-between items-center shrink-0 z-20 shadow-md">
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-black tracking-tight text-[#7ed957]">GERENTE</h1>
            <div class="hidden md:flex gap-3 border-l border-gray-600 pl-6">
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#7ed957]"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">Livres: <span x-text="contarLivres()" class="text-white"></span></span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1 bg-[#18181b] rounded-md border border-gray-700">
                    <div class="w-2 h-2 rounded-full bg-[#bf7854] animate-pulse"></div>
                    <span class="text-xs font-bold text-gray-400 uppercase">Ocupadas: <span x-text="contarOcupadas()" class="text-white"></span></span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-xl font-black font-mono leading-none text-white tracking-widest" x-text="clock"></div>
                <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5" 
                     :class="online ? 'text-[#7ed957]' : 'text-[#ef4444]'" 
                     x-text="online ? '● ONLINE' : '○ OFFLINE'"></div>
            </div>
        </div>
    </div>

    {{-- LOADING --}}
    <div x-show="!ready" class="flex-1 flex flex-col items-center justify-center text-gray-500 bg-[#0c0c0e]">
        <div class="w-12 h-12 border-4 border-gray-800 border-t-[#7ed957] rounded-full animate-spin mb-4"></div>
        <span class="text-xs font-bold uppercase tracking-widest animate-pulse">Sincronizando...</span>
    </div>

    {{-- GRID DE MESAS --}}
    <div x-show="ready" class="flex-1 overflow-y-auto p-4 scrollbar-hide bg-[#0c0c0e]" style="scrollbar-width: none; display: none;">
        <template x-if="mesas.length === 0">
            <div class="flex flex-col items-center justify-center h-full text-gray-500 opacity-50">
                <span class="text-sm font-bold uppercase tracking-widest">Nenhuma mesa configurada</span>
            </div>
        </template>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3 pb-20">
            <template x-for="mesa in mesas" :key="mesa.numero">
                <div @click="abrirMesa(mesa)" 
                     class="relative rounded-xl p-3 flex flex-col justify-between transition-all duration-200 h-32 border-2 bg-[#18181b]" 
                     :class="getMesaClasses(mesa)">
                    
                    <div class="flex justify-between items-start">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-60 text-gray-400">Mesa</span>
                            <template x-if="mesa.label">
                                <span class="text-[9px] font-bold text-[#efa324] uppercase tracking-wider" x-text="mesa.label"></span>
                            </template>
                        </div>
                        <span class="text-xl font-black font-mono leading-none text-white" x-text="mesa.numero"></span>
                    </div>
                    
                    <template x-if="mesa.pedidos.length > 0">
                        <div class="flex flex-col items-end">
                            <span class="text-lg font-black font-mono tracking-tight text-[#7ed957]" x-text="'€ ' + mesa.total.toFixed(2)"></span>
                            <div class="mt-1 flex items-center gap-1.5 bg-[#bf7854]/20 text-[#bf7854] border border-[#bf7854]/30 px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                                <span x-text="mesa.pedidos.reduce((acc, p) => acc + p.itens.length, 0)"></span>
                                <span>items</span>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="mesa.pedidos.length === 0">
                        <div class="absolute inset-0 flex items-center justify-center opacity-20">
                            <div class="text-center">
                                <span class="text-sm font-bold uppercase tracking-widest border border-gray-600 text-gray-500 px-2 py-1 rounded">Livre</span>
                                <div class="text-[9px] font-mono mt-1 text-gray-600" x-text="mesa.capacidade + ' Lug.'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- =======================================================================
         MODAL DETALHES UNIFICADO (COM RESPIRO E STATUS)
         ======================================================================= --}}
    <div x-show="mesaSelecionada" 
         class="fixed inset-0 z-[55] flex items-end sm:items-center justify-center bg-black/90 backdrop-blur-sm p-0 sm:p-4" 
         x-transition.opacity 
         style="display: none;">
        
        <div @click.away="mesaSelecionada = null" class="bg-[#18181b] w-full max-w-2xl sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[95vh] sm:h-auto sm:max-h-[85vh] animate-fade-in-up border border-gray-800">
            
            {{-- Header Modal --}}
            <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#202025] shrink-0">
                <div>
                    <span class="text-xs font-bold text-[#7ed957] uppercase tracking-widest">Gerenciamento</span>
                    <h2 class="text-3xl font-black text-white leading-none">Mesa <span x-text="mesaSelecionada?.numero"></span></h2>
                </div>
                <button @click="mesaSelecionada = null" class="w-10 h-10 rounded-full bg-gray-800 hover:bg-gray-700 flex items-center justify-center font-bold text-gray-400 hover:text-white transition-colors">✕</button>
            </div>

            {{-- LISTA DE ITENS AGRUPADOS --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#18181b] scrollbar-hide">
                <template x-if="mesaSelecionada">
                    <div class="space-y-3"> {{-- AUMENTADO GAP PARA RESPIRO --}}
                        
                        <div x-show="itensConsolidados.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-600">
                            <p class="text-sm font-bold uppercase tracking-wider">Mesa sem pedidos ativos</p>
                        </div>

                        <template x-for="item in itensConsolidados" :key="item.chaveUnica">
                            {{-- CARD DO ITEM (Visual Refinado) --}}
                            <div class="flex flex-col group bg-[#202025] rounded-xl border border-gray-800 hover:border-gray-600 transition-all shadow-sm relative overflow-hidden">
                                
                                <div class="p-4 flex justify-between items-start"> {{-- AUMENTADO PADDING --}}
                                    <div class="flex gap-4 items-start w-full">
                                        
                                        {{-- Botão Editar --}}
                                        <button @click="abrirEdicaoLote(item)" class="shrink-0 w-12 h-12 rounded-xl bg-[#2a2a30] border border-gray-700 text-gray-400 hover:text-white hover:border-[#7ed957] flex items-center justify-center transition-all shadow-sm active:scale-95 group-hover:bg-[#33333a]">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2 flex-wrap">
                                                <span class="font-black text-2xl text-white" x-text="item.quantidade + 'x'"></span>
                                                <span class="font-bold text-gray-200 text-lg leading-tight" x-text="item.nome_produto"></span>
                                            </div>
                                            
                                            {{-- BARRA DE STATUS INSTANTÂNEO (UX KEY FEATURE) --}}
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <template x-for="(qtd, status) in item.statusBreakdown" :key="status">
                                                    <span x-show="qtd > 0" 
                                                          class="text-[10px] px-2 py-0.5 rounded uppercase font-black tracking-wide border flex items-center gap-1.5"
                                                          :class="{
                                                              'bg-[#efa324]/10 text-[#efa324] border-[#efa324]/20': status === 'pendente',
                                                              'bg-[#bf7854]/10 text-[#bf7854] border-[#bf7854]/20 animate-pulse': status === 'preparo',
                                                              'bg-[#7ed957]/10 text-[#7ed957] border-[#7ed957]/20': status === 'pronto'
                                                          }">
                                                        {{-- Bolinha de status --}}
                                                        <span class="w-1.5 h-1.5 rounded-full" 
                                                              :class="{
                                                                'bg-[#efa324]': status === 'pendente',
                                                                'bg-[#bf7854]': status === 'preparo',
                                                                'bg-[#7ed957]': status === 'pronto'
                                                              }"></span>
                                                        <span x-text="qtd + ' ' + formatStatus(status)"></span>
                                                    </span>
                                                </template>
                                            </div>

                                            {{-- Tags de Observação --}}
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
                                        <span class="font-mono font-bold text-gray-400 text-lg whitespace-nowrap" x-text="'€ ' + (item.preco * item.quantidade).toFixed(2)"></span>
                                        <span class="text-[9px] text-gray-600 uppercase font-bold tracking-wide mt-1" x-text="'€ ' + Number(item.preco).toFixed(2) + ' un'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Footer Detalhes --}}
            <div class="p-4 bg-[#202025] border-t border-gray-800 shrink-0 space-y-3">
                <div class="flex justify-between items-end">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Geral</span>
                    <span class="text-4xl font-black text-[#7ed957] kds-font-mono tracking-tighter" x-text="'€ ' + (mesaSelecionada?.total || 0).toFixed(2)"></span>
                </div>
                
                <div class="grid grid-cols-12 gap-2">
                    <button @click="abrirAdicionarItem()" class="col-span-3 h-14 bg-transparent border-2 border-dashed border-gray-600 text-gray-400 rounded-xl font-bold uppercase text-[10px] hover:border-[#7ed957] hover:text-[#7ed957] transition-all flex flex-col items-center justify-center leading-none gap-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span>Add Produto</span>
                    </button>
                    
                    <button @click="imprimirContaConsolidada()" class="col-span-3 h-14 bg-[#2a2a30] border border-gray-700 text-white rounded-xl font-bold uppercase text-xs hover:bg-gray-700 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        <span>Imprimir</span>
                    </button>

                    <button @click="abrirConfirmacaoFecho()" class="col-span-6 h-14 bg-[#7ed957] text-black rounded-xl font-black uppercase text-sm shadow-lg shadow-[#7ed957]/20 active:scale-[0.98] hover:bg-[#6ec24a] transition-all flex items-center justify-center gap-2">
                        <span>Fechar Conta</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUB-MODAL 1: EDIÇÃO DE LOTE --}}
    <div x-show="editandoItem" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" 
         x-transition.opacity 
         @click.self.stop="editandoItem = null" 
         style="display: none;">
        
        <div class="bg-[#18181b] w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up border border-gray-800" @click.stop>
            <div class="p-6 text-center">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Gerenciar Item</h3>
                <h2 class="text-2xl font-black text-white leading-tight mb-2" x-text="editandoItem?.nome_produto"></h2>
                <p class="text-xs text-gray-400 mb-6">Esta ação afeta o último pedido deste item.</p>
                
                <div class="flex items-center justify-center gap-6 mb-8">
                    <button @click="removerUmItemDoLote()" class="w-16 h-16 rounded-2xl bg-[#2a2a30] text-white text-3xl font-black hover:bg-red-500/20 active:scale-95 transition-all">-</button>
                    <div class="text-center">
                        <span class="block text-5xl font-black font-mono tracking-tighter text-[#7ed957]" x-text="editandoItem?.quantidade"></span>
                        <span class="text-[10px] font-bold text-gray-500 uppercase">TOTAL</span>
                    </div>
                    <button @click="adicionarMaisUmNoLote()" class="w-16 h-16 rounded-2xl bg-[#7ed957] text-black text-3xl font-black hover:bg-[#6ec24a] active:scale-95 transition-all">+</button>
                </div>

                <div class="space-y-3">
                    <button @click="removerTodosDoLote()" class="w-full py-4 text-red-500 bg-red-500/10 border border-red-500/20 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-red-500/20 transition-colors">
                        Excluir Todos (<span x-text="editandoItem?.quantidade"></span>)
                    </button>
                    <button @click="editandoItem = null" class="w-full py-3 text-gray-400 text-xs font-bold uppercase hover:text-white">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SUB-MODAL 2: ADICIONAR NOVO PRODUTO --}}
    <div x-show="adicionandoItem" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" 
         x-transition.opacity 
         @click.self.stop="fecharAdicionarItem()" 
         style="display: none;">
        
        <div class="bg-[#18181b] w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up flex flex-col max-h-[80vh] border border-gray-800" @click.stop>
            <div class="p-6 border-b border-gray-800">
                <h2 class="text-xl font-black text-white mb-4">Adicionar à Mesa <span x-text="mesaSelecionada?.numero"></span></h2>
                <input type="text" x-model="searchProduto" placeholder="Buscar produto..." 
                       class="w-full h-12 px-4 bg-[#2a2a30] text-white rounded-xl font-bold border border-gray-700 focus:border-[#7ed957] focus:ring-1 focus:ring-[#7ed957] transition-all placeholder-gray-500">
            </div>

            <div class="flex-1 overflow-y-auto p-2 bg-[#0c0c0e] space-y-2">
                <template x-for="prod in produtosFiltrados" :key="prod.id">
                    <div @click="adicionarProdutoNaMesa(prod)" class="bg-[#18181b] p-4 rounded-xl border border-gray-800 flex justify-between items-center cursor-pointer hover:border-[#7ed957] transition-all active:scale-[0.98]">
                        <div>
                            <p class="font-bold text-white" x-text="prod.nome"></p>
                            <p class="text-[10px] font-bold text-gray-500 uppercase" x-text="prod.categoria"></p>
                        </div>
                        <span class="font-mono font-black text-[#7ed957]" x-text="'€ ' + Number(prod.preco).toFixed(2)"></span>
                    </div>
                </template>
            </div>
            <button @click="fecharAdicionarItem()" class="p-4 bg-[#18181b] border-t border-gray-800 font-bold text-gray-500 uppercase text-xs hover:text-white">Fechar</button>
        </div>
    </div>

    {{-- SUB-MODAL 3: CONFIRMAR PAGAMENTO --}}
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
                <h3 class="text-2xl font-black text-white mb-2">Receber Pagamento?</h3>
                <p class="text-gray-400 font-medium mb-8">Confirmar recebimento da <span class="text-white font-bold">Mesa <span x-text="mesaSelecionada?.numero"></span></span>.<br><span class="block mt-4 text-4xl font-black text-[#7ed957] kds-font-mono" x-text="'€ ' + (mesaSelecionada?.total || 0).toFixed(2)"></span></p>
                <div class="flex flex-col gap-3">
                    <button @click="fecharContaConfirmada()" class="h-16 bg-[#7ed957] text-black font-black rounded-2xl shadow-xl shadow-[#7ed957]/20 active:scale-[0.98] uppercase tracking-widest text-sm flex items-center justify-center gap-3 hover:bg-[#6ec24a]">
                        <span>Confirmar Pagamento</span>
                    </button>
                    <button @click="confirmandoFecho = false" class="h-14 bg-[#2a2a30] text-gray-400 font-bold rounded-2xl hover:bg-[#33333a] hover:text-white uppercase tracking-widest text-xs">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function gerenteApp() {
        return {
            mesas: [],
            mesaSelecionada: null,
            confirmandoFecho: false,
            editandoItem: null,
            adicionandoItem: false,
            listaProdutos: [],
            searchProduto: '',
            socket: null,
            clock: '',
            online: false,
            ready: false,

            async init() {
                setInterval(() => { this.clock = new Date().toLocaleTimeString('pt-PT', {hour: '2-digit', minute:'2-digit'}); }, 1000);
                await this.waitForAxios();
                this.setupSocket();
                this.fetchDados();
                this.carregarProdutos();
            },

            // --- COMPUTED: ITENS CONSOLIDADOS COM BREAKDOWN DE STATUS ---
            get itensConsolidados() {
                if (!this.mesaSelecionada || !this.mesaSelecionada.pedidos) return [];

                let mapa = {};
                
                this.mesaSelecionada.pedidos.forEach(pedido => {
                    pedido.itens.forEach(item => {
                        let chave = item.nome_produto + '_' + item.preco;
                        
                        if (!mapa[chave]) {
                            mapa[chave] = {
                                chaveUnica: chave,
                                nome_produto: item.nome_produto,
                                preco: parseFloat(item.preco),
                                quantidade: 0,
                                observacoesUnicas: new Set(),
                                ids_reais: [],
                                produto_id_real: null,
                                // Novo: Contador de status
                                statusBreakdown: { pendente: 0, preparo: 0, pronto: 0 } 
                            };
                        }

                        mapa[chave].quantidade += item.quantidade;
                        mapa[chave].ids_reais.push(item.id);
                        
                        // Somar status (Assumindo que o item herda status do pedido)
                        // AVISO: Se o backend já manda o item.status, melhor. Se não, usamos pedido.status.
                        // No modelo atual, parece que o status é do PEDIDO.
                        let statusAtual = pedido.status || 'pendente';
                        if(mapa[chave].statusBreakdown[statusAtual] !== undefined) {
                            mapa[chave].statusBreakdown[statusAtual] += item.quantidade;
                        }

                        let obsLimpa = item.observacao;
                        if (obsLimpa && !obsLimpa.includes('Acrescentar +')) {
                            mapa[chave].observacoesUnicas.add(obsLimpa);
                        }
                    });
                });

                return Object.values(mapa).sort((a,b) => a.nome_produto.localeCompare(b.nome_produto));
            },

            formatStatus(status) {
                const map = { 'pendente': 'Pendente', 'preparo': 'Preparo', 'pronto': 'Pronto' };
                return map[status] || status;
            },

            async carregarProdutos() {
                try {
                    const res = await window.axios.get('/api/produtos');
                    this.listaProdutos = res.data;
                } catch(e) { console.error("Erro prod", e); }
            },

            abrirEdicaoLote(itemConsolidado) {
                const prodOriginal = this.listaProdutos.find(p => p.nome === itemConsolidado.nome_produto);
                this.editandoItem = {
                    ...itemConsolidado,
                    produto_id_real: prodOriginal ? prodOriginal.id : null
                };
            },

            async adicionarMaisUmNoLote() {
                if (!this.editandoItem || !this.editandoItem.produto_id_real) { alert("Produto não identificado."); return; }
                try {
                    await window.axios.post('/api/gerente/adicionar-item-mesa', { mesa: this.mesaSelecionada.numero, produto_id: this.editandoItem.produto_id_real });
                    this.editandoItem.quantidade++;
                    this.fetchDados();
                } catch(e) { alert("Erro ao adicionar."); }
            },

            async removerUmItemDoLote() {
                if (!this.editandoItem || this.editandoItem.ids_reais.length === 0) return;
                const idParaRemover = this.editandoItem.ids_reais[this.editandoItem.ids_reais.length - 1];
                try {
                    await window.axios.delete(`/api/gerente/remover-item/${idParaRemover}`);
                    this.editandoItem.quantidade--;
                    this.editandoItem.ids_reais.pop();
                    if (this.editandoItem.quantidade <= 0) this.editandoItem = null;
                    this.fetchDados();
                } catch(e) { alert("Erro ao remover."); }
            },

            async removerTodosDoLote() {
                if (!this.editandoItem) return;
                if (!confirm(`Remover todos?`)) return;
                try {
                    const promises = this.editandoItem.ids_reais.map(id => window.axios.delete(`/api/gerente/remover-item/${id}`));
                    await Promise.all(promises);
                    this.editandoItem = null;
                    this.fetchDados();
                } catch(e) { alert("Erro ao remover lote."); }
            },

            abrirAdicionarItem() { this.searchProduto = ''; this.adicionandoItem = true; },
            fecharAdicionarItem() { this.adicionandoItem = false; },

            async adicionarProdutoNaMesa(produto) {
                try {
                    await window.axios.post('/api/gerente/adicionar-item-mesa', {
                        mesa: this.mesaSelecionada.numero,
                        produto_id: produto.id
                    });
                    this.adicionandoItem = false; 
                    this.fetchDados(); 
                } catch(e) { alert("Erro ao adicionar item."); }
            },

            async fetchDados() {
                if (typeof window.axios === 'undefined') return; 
                try {
                    const resConfig = await window.axios.get('/api/mesas-configuradas');
                    const mesasDb = resConfig.data;
                    const resStatus = await window.axios.get('/api/gerente/resumo-mesas');
                    const dadosBackend = resStatus.data;
                    
                    this.mesas = mesasDb.map(mesaConfig => {
                        const dadosMesa = dadosBackend.find(d => d.mesa == mesaConfig.numero);
                        return {
                            numero: mesaConfig.numero,
                            label: mesaConfig.label,
                            capacidade: mesaConfig.capacidade,
                            pedidos: dadosMesa ? dadosMesa.pedidos : [],
                            total: dadosMesa ? parseFloat(dadosMesa.total) : 0
                        };
                    });

                    if (this.mesaSelecionada) {
                        const atualizada = this.mesas.find(m => m.numero === this.mesaSelecionada.numero);
                        if (atualizada) {
                            this.mesaSelecionada = atualizada;
                            if (this.editandoItem) {
                                const novoConsolidado = this.itensConsolidados.find(i => i.chaveUnica === this.editandoItem.chaveUnica);
                                if (novoConsolidado) {
                                    novoConsolidado.produto_id_real = this.editandoItem.produto_id_real;
                                    this.editandoItem = novoConsolidado;
                                } else {
                                    this.editandoItem = null;
                                }
                            }
                        } else {
                            this.mesaSelecionada = null; 
                        }
                    }
                } catch (e) { console.error("Erro sync:", e); }
            },

            getMesaClasses(mesa) {
                if (mesa.pedidos.length > 0) {
                    return 'border-[#bf7854] shadow-[0_0_15px_rgba(191,120,84,0.15)] cursor-pointer active:scale-[0.97] z-10';
                } else {
                    return 'border-gray-800 text-gray-600 cursor-default hover:border-gray-700';
                }
            },
            
            contarLivres() { return this.mesas.filter(m => m.pedidos.length === 0).length; },
            contarOcupadas() { return this.mesas.filter(m => m.pedidos.length > 0).length; },
            
            async waitForAxios() {
                if (typeof window.axios !== 'undefined') { this.ready = true; return; }
                await new Promise(resolve => setTimeout(resolve, 100));
                return this.waitForAxios();
            },
            
            setupSocket() {
                if (window.AppConfig && window.AppConfig.socketUrl && typeof io !== 'undefined') {
                    this.socket = io(window.AppConfig.socketUrl);
                    this.socket.on('connect', () => { this.online = true; this.fetchDados(); });
                    this.socket.on('disconnect', () => this.online = false);
                    ['cozinha_novo_pedido', 'cozinha_atualizar_status', 'pedido_pronto', 'pedido_pago'].forEach(ev => {
                        this.socket.on(ev, () => this.fetchDados());
                    });
                }
            },
            
            get produtosFiltrados() {
                if (this.searchProduto === '') return this.listaProdutos;
                return this.listaProdutos.filter(p => p.nome.toLowerCase().includes(this.searchProduto.toLowerCase()));
            },
            
            abrirConfirmacaoFecho() { if (this.mesaSelecionada) this.confirmandoFecho = true; },
            
            async fecharContaConfirmada() {
                try {
                    await window.axios.post(`/api/gerente/fechar-mesa/${this.mesaSelecionada.numero}`);
                    this.confirmandoFecho = false;
                    this.mesaSelecionada = null; 
                    this.fetchDados();
                } catch(e) { alert('Erro ao fechar.'); this.confirmandoFecho = false; }
            },
            
            abrirMesa(mesa) { 
                if (mesa.pedidos.length > 0) this.mesaSelecionada = mesa; 
            },

            imprimirContaConsolidada() {
                if(!this.mesaSelecionada) return;
                const m = this.mesaSelecionada;
                const itens = this.itensConsolidados; 
                
                const w = window.open('', '', 'width=350,height=600');
                w.document.write(`
                    <html>
                    <head>
                        <title>MESA ${m.numero}</title>
                        <style>
                            body { font-family: 'Courier New', monospace; padding: 20px; font-size: 12px; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
                            .total { font-weight: bold; font-size: 1.4em; border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; }
                            .qty { font-weight: bold; margin-right: 5px; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>JUNIOR BAR</h2>
                            <h3>MESA ${m.numero}</h3>
                            <p>${new Date().toLocaleString('pt-PT')}</p>
                        </div>
                        <hr style="border-top: 1px dashed #000;">
                        ${itens.map(i => `
                            <div class="row">
                                <span><span class="qty">${i.quantidade}x</span> ${i.nome_produto}</span>
                                <span>${(i.preco * i.quantidade).toFixed(2)}</span>
                            </div>
                        `).join('')}
                        <div class="row total">
                            <span>TOTAL</span>
                            <span>€ ${m.total.toFixed(2)}</span>
                        </div>
                        <br><div style="text-align:center;">Obrigado!</div>
                    </body>
                    </html>
                `);
                w.document.close();
                setTimeout(() => { w.print(); w.close(); }, 500);
            }
        }
    }
</script>
@endsection