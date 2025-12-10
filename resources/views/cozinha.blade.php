@extends('layouts.kds')
@section('title', 'KDS Monitor')
@section('body-class', 'bg-[var(--kds-bg)] text-[var(--text-primary)] kds-font-ui overflow-hidden')

@section('content')
<div x-data="kdsSystem()" x-init="initSystem()" class="h-screen flex flex-col">

    <div class="h-16 bg-[var(--kds-bg)] border-b border-[var(--kds-border)] flex items-center justify-between px-6 z-50 shrink-0">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold tracking-tight text-white">COZINHA <span class="text-yellow-500 text-xs align-top">PRO</span></h1>
            <div class="text-2xl font-bold text-gray-500 kds-font-mono" x-text="clock">--:--</div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="px-3 py-1 rounded text-xs font-bold border transition-colors duration-300"
                 :class="online ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'">
                <span x-text="online ? 'ONLINE' : 'OFFLINE'"></span>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 scrollbar-hide">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 auto-rows-max">
            
            <template x-for="pedido in pedidos" :key="pedido.id">
                <div class="relative bg-[var(--kds-card)] border-t-4 rounded-b-lg shadow-lg flex flex-col min-h-[300px] transition-all duration-300 animate-fade-in-up"
                     :class="{
                        'border-[var(--status-new-border)]': pedido.status === 'pendente',
                        'border-[var(--status-cooking-border)]': pedido.status === 'preparo',
                        'animate-pulse border-red-500': isLate(pedido.created_at)
                     }">
                    
                    <div class="p-3 bg-white/5 flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-black text-white">Mesa <span x-text="pedido.mesa"></span></h2>
                            <span class="text-xs text-gray-500 font-mono" x-text="'#' + pedido.id"></span>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold kds-font-mono" 
                                 :class="isLate(pedido.created_at) ? 'text-red-500' : 'text-gray-300'"
                                 x-text="formatTime(pedido.created_at)">
                            </div>
                            
                            <div class="text-[10px] uppercase font-bold tracking-wider mt-1"
                                 :class="pedido.status === 'preparo' ? 'text-blue-400 animate-pulse' : 'text-gray-400'"
                                 x-text="pedido.status === 'pendente' ? 'AGUARDANDO' : 'EM PREPARO'">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 flex-grow overflow-y-auto">
                        <ul class="space-y-4">
                            <template x-for="item in pedido.itens" :key="item.id">
                                <li class="flex justify-between items-start border-b border-gray-800 pb-2 last:border-0">
                                    <div class="flex-1 pr-2">
                                        <span class="text-lg font-semibold text-gray-100 leading-tight block" x-text="item.nome_produto"></span>
                                        <template x-if="item.observacao">
                                            <div class="text-yellow-500 text-xs italic mt-0.5 font-bold" x-text="'⚠️ ' + item.observacao"></div>
                                        </template>
                                    </div>
                                    <span class="bg-gray-800 text-white font-mono text-xl px-3 py-1 rounded font-bold" x-text="item.quantidade"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="p-2 mt-auto bg-black/20">
                        <button @click="avancarStatus(pedido)" 
                                class="w-full py-4 rounded font-bold uppercase tracking-widest text-sm transition-all hover:brightness-110 active:scale-[0.98]"
                                :class="{
                                    'bg-gray-700 hover:bg-gray-600 text-white': pedido.status === 'pendente',
                                    'bg-green-600 hover:bg-green-500 text-white shadow-lg': pedido.status === 'preparo'
                                }">
                            <span x-text="pedido.status === 'pendente' ? 'INICIAR PREPARO' : '✔ FINALIZAR'"></span>
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>

<script>
    function kdsSystem() {
        return {
            pedidos: [],
            online: false,
            socket: null,
            clock: '--:--',
            now: Date.now(),

            initSystem() {
                // Conecta ao Socket usando a config global
                if (window.AppConfig && window.AppConfig.socketUrl) {
                    this.socket = io(window.AppConfig.socketUrl);
                    
                    this.socket.on('connect', () => this.online = true);
                    this.socket.on('disconnect', () => this.online = false);

                    // --- Eventos Socket ---
                    
                    // Novo Pedido (Vem do Servidor/Laravel)
                    this.socket.on('cozinha_novo_pedido', (pedido) => {
                        this.pedidos.push(pedido);
                        // Opcional: Som de notificação
                        // new Audio('/sounds/ding.mp3').play().catch(e => console.log(e));
                    });

                    // Atualização de Status (Outra tela mexeu)
                    this.socket.on('cozinha_atualizar_status', (dados) => {
                        const pedido = this.pedidos.find(p => p.id == dados.id);
                        if (pedido) pedido.status = dados.status;
                    });

                    // Pedido Pronto (Remove da tela)
                    this.socket.on('pedido_pronto', (dados) => {
                        this.pedidos = this.pedidos.filter(p => p.id != dados.id);
                    });
                }

                // Carrega estado inicial
                this.fetchPedidos();

                // Relógio Global (Atualiza a cada segundo)
                setInterval(() => {
                    this.now = Date.now();
                    this.clock = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }, 1000);
            },

            async fetchPedidos() {
                try {
                    const res = await axios.get('/api/pedidos-ativos');
                    this.pedidos = res.data;
                } catch (e) {
                    console.error("Erro API:", e);
                }
            },

            async avancarStatus(pedido) {
                const novoStatus = pedido.status === 'pendente' ? 'preparo' : 'pronto';
                const oldStatus = pedido.status;

                // UI Optimistic Update (Atualiza visualmente antes da resposta)
                if(novoStatus === 'pronto') {
                    this.pedidos = this.pedidos.filter(p => p.id !== pedido.id);
                } else {
                    pedido.status = novoStatus;
                }

                try {
                    // Chama Laravel
                    await axios.post(`/api/atualizar-status/${pedido.id}`, { status: novoStatus });
                    
                    // Aviso Socket (redundância caso o server-side broadcast falhe, mas o principal é o PHP avisar)
                    // Nota: Se implementou o Server-Side Broadcast no PedidoController, esta linha abaixo é opcional,
                    // mas mal não faz (duplo aviso é filtrado pelo ID na maioria dos casos).
                    if(this.socket) {
                         this.socket.emit('atualizar_status_pedido', { 
                            id: pedido.id, 
                            status: novoStatus,
                            mesa: pedido.mesa 
                        });
                    }

                } catch (e) {
                    alert('Erro de conexão! Ação desfeita.');
                    // Rollback se der erro
                    if(novoStatus !== 'pronto') pedido.status = oldStatus;
                    else this.fetchPedidos();
                }
            },

            // --- NOVA FUNÇÃO DE HORA ---
            formatTime(dateStr) {
                if (!dateStr) return '--:--';
                const date = new Date(dateStr);
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            },

            // Lógica de Atraso (Mantida para pintar de vermelho)
            isLate(dateStr) {
                const start = new Date(dateStr).getTime();
                // Retorna TRUE se passou de 20 minutos
                return ((this.now - start) / 1000 / 60) > 20; 
            }
        }
    }
</script>
@endsection