@extends('layouts.kds')
@section('title', 'Garçom')
@section('body-class', 'bg-[#F4F4F5] kds-font-ui pb-32')

@section('content')
<div x-data="garcomApp()" class="max-w-md mx-auto pt-6 px-4">
    
    <div class="bg-white p-5 rounded-2xl shadow-sm mb-6 sticky top-24 z-40 border border-gray-100/50 backdrop-blur-md bg-white/90">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Selecionar Mesa</h3>
            
            <div x-show="mesa" x-transition class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-xs font-bold text-black kds-font-mono" x-text="'MESA ' + (mesa < 10 ? '0'+mesa : mesa)"></span>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-3">
            <template x-for="i in 10">
                <button type="button" 
                    @click="selectMesa(i)" 
                    class="h-12 rounded-xl font-bold text-lg border-2 transition-all duration-200 kds-font-mono focus:outline-none relative overflow-hidden"
                    :class="mesa === i ? 'bg-black border-black text-white shadow-lg scale-105' : 'border-gray-100 text-gray-500 hover:border-gray-300 hover:text-black bg-white'">
                    <span x-text="i" class="relative z-10"></span>
                </button>
            </template>
        </div>
    </div>

    <div x-show="loadingMenu" class="space-y-3 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
        <template x-for="i in 4">
            <div class="w-full h-20 bg-gray-200 rounded-2xl"></div>
        </template>
    </div>

    <div x-show="!loadingMenu" 
         class="space-y-3 transition-all duration-500"
         :class="mesa ? 'opacity-100 translate-y-0' : 'opacity-40 translate-y-4 pointer-events-none blur-[1px] grayscale'">
        
        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] pl-1 mb-2">Cardápio</h3>
        
        <div x-show="produtos.length === 0" class="text-center py-8 text-gray-400 text-sm">
            Nenhum produto encontrado.
        </div>

        <template x-for="produto in produtos" :key="produto.id">
            <button @click="addItem(produto)" 
                class="w-full flex justify-between items-center p-4 rounded-2xl bg-white border border-gray-100 shadow-sm active:scale-[0.97] transition-all hover:border-black/20 group relative overflow-hidden">
                
                <div class="text-left z-10">
                    <span class="block font-bold text-gray-900 text-lg leading-tight group-hover:text-black" x-text="produto.nome"></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1 block" x-text="produto.categoria"></span>
                </div>
                
                <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-400 border border-gray-100 group-hover:bg-black group-hover:text-white group-hover:border-black flex items-center justify-center transition-all z-10 shadow-sm">
                    +
                </div>
            </button>
        </template>
    </div>

    <div class="fixed bottom-6 left-4 right-4 z-50 max-w-md mx-auto">
        <button @click="enviarPedido()" 
                :disabled="!mesa || itens.length === 0 || loading"
                class="w-full h-16 rounded-2xl font-bold text-lg shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] flex items-center justify-between px-6 transition-all duration-300 disabled:opacity-0 disabled:translate-y-10"
                :class="(mesa && itens.length > 0) ? 'translate-y-0 bg-black text-white' : 'translate-y-20 bg-gray-200'">
            
            <span class="tracking-wide text-sm" x-text="loading ? 'ENVIANDO...' : 'CONFIRMAR PEDIDO'"></span>
            
            <div class="flex items-center gap-3">
                <div class="flex flex-col items-end leading-none">
                    <span class="text-[10px] text-gray-400 font-medium uppercase">Itens</span>
                    <span class="kds-font-mono text-xl" x-text="itens.length < 10 ? '0'+itens.length : itens.length"></span>
                </div>
                <div class="bg-white/20 p-2 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </button>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function garcomApp() {
        return {
            mesa: null,
            itens: [],
            loading: false,
            loadingMenu: true,
            produtos: [],
            socket: null,

            async init() {
                // 1. Configura Socket (Verificação de segurança)
                if (window.AppConfig && window.AppConfig.socketUrl && typeof io !== 'undefined') {
                    this.socket = io(window.AppConfig.socketUrl);
                } else {
                    console.warn("Socket.io não carregado ou config ausente.");
                }

                // 2. Busca Produtos
                await this.fetchCardapio();
            },

            async fetchCardapio() {
                try {
                    // Verifica se o Axios já foi carregado pelo bootstrap.js
                    // Se não, aguarda 100ms e tenta de novo (fix para tela branca)
                    if (typeof window.axios === 'undefined') {
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                    
                    if (typeof window.axios !== 'undefined') {
                        const res = await window.axios.get('/api/produtos');
                        this.produtos = res.data;
                    } else {
                        console.error("Biblioteca Axios não encontrada.");
                        alert("Erro de sistema: Recarregue a página (F5).");
                    }
                } catch (error) {
                    console.error("Erro ao carregar cardápio:", error);
                    // Não mostra alert para não bloquear a tela, apenas loga
                } finally {
                    this.loadingMenu = false;
                }
            },

            selectMesa(num) {
                this.mesa = num;
                if(navigator.vibrate) navigator.vibrate(10);
            },

            addItem(produto) {
                if (!this.mesa) {
                    alert('Selecione uma mesa primeiro');
                    return;
                }
                
                this.itens.push({ 
                    id_produto: produto.id,
                    nome: produto.nome, 
                    qtd: 1, 
                    obs: '' 
                });
                
                if(navigator.vibrate) navigator.vibrate(15);
            },

            async enviarPedido() {
                if (!this.mesa || this.itens.length === 0) return;
                this.loading = true;

                try {
                    const res = await window.axios.post('/api/criar-pedido', {
                        mesa: this.mesa,
                        itens: this.itens
                    });

                    if (res.data.status === 'sucesso') {
                        this.mesa = null;
                        this.itens = [];
                        
                        // Feedback Sucesso
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center gap-3 animate-bounce font-bold';
                        toast.innerText = '✔ Pedido Enviado!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro ao enviar pedido.');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endpush