/* resources/js/modules/cozinha.js */
import { io } from "socket.io-client";
import axios from 'axios';

export default function kdsSystem() {
    return {
        // --- ESTADO (State) ---
        pedidos: [],
        online: false,
        socket: null,
        clock: '--:--',
        now: Date.now(), // Usado para reatividade dos timers
        filtro: 'todos',
        
        // --- RECALL (Soft Delete / Desfazer) ---
        pedidosFinalizando: [], // IDs visualmente escondidos enquanto o timer corre
        undoData: null,         // Dados do pedido na barra de desfazer {id, mesa}
        undoTimer: null,        // Referência do setTimeout
        
        // --- AUDIO & ALERTAS (ORDER BUMP) ---
        audioAlert: null,
        pedidosAlterados: [],   // IDs que devem piscar (Alerta Visual de alteração)

        // --- INICIALIZAÇÃO ---
        init() {
            this.setupAudio();
            this.setupSocket();
            this.startClock();
            this.fetchActiveOrders();
        },

        setupAudio() {
            // Cria o objeto de áudio apenas uma vez
            this.audioAlert = new Audio('/sounds/ding.mp3'); 
            this.audioAlert.volume = 1.0; 
        },

        // --- 1. CONFIGURAÇÃO DO SOCKET ---
        setupSocket() {
            const SOCKET_URL = window.AppConfig?.socketUrl || "http://localhost:3000";

            this.socket = io(SOCKET_URL, {
                reconnection: true,
                reconnectionAttempts: 10,
                transports: ['websocket']
            });

            this.socket.on('connect', () => this.online = true);
            this.socket.on('disconnect', () => this.online = false);

            // [EVENTO CRÍTICO] Recebe novos pedidos ou ATUALIZAÇÕES completas
            this.socket.on('cozinha_novo_pedido', (pedido) => {
                this.addOrUpdateLocal(pedido);
            });

            // Atualização de status simples
            this.socket.on('cozinha_atualizar_status', (dados) => {
                if (this.undoData && this.undoData.id == dados.id) return;

                const index = this.pedidos.findIndex(p => p.id == dados.id);
                if (index !== -1) {
                    this.pedidos[index].status = dados.status;
                    // Se foi marcado como pronto remotamente, remove da tela
                    if (dados.status === 'pronto' || dados.status === 'cancelado') {
                        this.pedidos.splice(index, 1);
                    }
                }
            });
            
            // Evento de limpeza/remoção
            this.socket.on('pedido_pronto', (dados) => {
                 this.pedidos = this.pedidos.filter(p => p.id != (dados.id || dados.id_pedido));
            });
        },

        // --- 2. GERENCIAMENTO DE DADOS ---
        async fetchActiveOrders() {
            try {
                const res = await axios.get('/api/pedidos-ativos');
                this.pedidos = res.data.filter(p => !this.pedidosFinalizando.includes(p.id));
            } catch (e) {
                console.error("Erro ao buscar pedidos:", e);
            }
        },

        // [CORE] Lógica Central de Entrada de Dados
        addOrUpdateLocal(pedido) {
            // [SEGURANÇA FRONTEND] 
            // Se o backend enviar um pedido 'pronto' ou 'cancelado' por engano (ex: edição financeira),
            // nós rejeitamos e removemos da tela se ele existir. Isso evita "Zumbis".
            if (pedido.status !== 'pendente' && pedido.status !== 'preparo') {
                const existingIndex = this.pedidos.findIndex(p => p.id === pedido.id);
                if (existingIndex !== -1) {
                    this.pedidos.splice(existingIndex, 1);
                }
                return; 
            }

            const index = this.pedidos.findIndex(p => p.id === pedido.id);
            
            if (index !== -1) {
                // CASO 1: ATUALIZAÇÃO (O pedido já existe na tela)
                this.pedidos[index] = pedido; 
                
                // Só pisca a tela se for um UPDATE real (ignora create tardio)
                if (pedido.event_type === 'update') {
                    this.triggerUpdateFlash(pedido.id);
                }
                
                this.playNotification(); 
            } else {
                // CASO 2: NOVO PEDIDO
                this.pedidos.push(pedido);
                // Em criação, apenas toca o som, sem flash de alteração
                this.playNotification();
            }
        },

        // --- 3. ALERTA VISUAL (ORDER BUMP) ---
        triggerUpdateFlash(id) {
            if (!this.pedidosAlterados.includes(id)) {
                this.pedidosAlterados.push(id);
                
                // Remove o destaque automaticamente após 15 segundos
                setTimeout(() => {
                    this.pedidosAlterados = this.pedidosAlterados.filter(pid => pid !== id);
                }, 15000);
            }
        },

        isUpdated(id) {
            return this.pedidosAlterados.includes(id);
        },

        // --- 4. AÇÕES & FLUXO DE RECALL (UNDO) ---
        
        async avancarStatus(pedido) {
            const novoStatus = pedido.status === 'pendente' ? 'preparo' : 'pronto';

            if (novoStatus === 'pronto') {
                this.iniciarFinalizacao(pedido);
                return; 
            }

            const oldStatus = pedido.status;
            pedido.status = novoStatus; 

            try {
                await axios.post(`/api/atualizar-status/${pedido.id}`, { status: novoStatus });
                this.socket.emit('atualizar_status_pedido', { 
                    id: pedido.id, 
                    status: novoStatus, 
                    mesa: pedido.mesa 
                });
            } catch (e) {
                pedido.status = oldStatus;
                alert("Erro de conexão!");
            }
        },

        iniciarFinalizacao(pedido) {
            if (this.undoData) this.confirmarFinalizacao(this.undoData.id);

            this.pedidosFinalizando.push(pedido.id);
            this.undoData = { id: pedido.id, mesa: pedido.mesa };

            if (this.undoTimer) clearTimeout(this.undoTimer);
            this.undoTimer = setTimeout(() => {
                this.confirmarFinalizacao(pedido.id);
            }, 3000); 
        },

        desfazerFinalizacao() {
            if (!this.undoData) return;
            const idParaRestaurar = this.undoData.id;
            
            clearTimeout(this.undoTimer);
            
            this.pedidosFinalizando = this.pedidosFinalizando.filter(id => id !== idParaRestaurar);
            
            this.undoData = null;
            this.undoTimer = null;
        },

        async confirmarFinalizacao(id) {
            if (this.undoData && this.undoData.id === id) {
                this.undoData = null;
                this.undoTimer = null;
            }
            try {
                await axios.post(`/api/atualizar-status/${id}`, { status: 'pronto' });
                
                this.pedidos = this.pedidos.filter(p => p.id !== id);
                this.pedidosFinalizando = this.pedidosFinalizando.filter(pid => pid !== id);
                
                this.socket.emit('atualizar_status_pedido', { id: id, status: 'pronto' });
            } catch (e) {
                this.pedidosFinalizando = this.pedidosFinalizando.filter(pid => pid !== id);
                console.error("Erro ao finalizar:", e);
            }
        },

        // --- 5. LÓGICA DE TEMPO & UTILS ---
        
        startClock() {
            setInterval(() => {
                this.now = Date.now();
                this.clock = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }, 1000);
        },

        formatTimer(dateStr) {
            if (!dateStr) return '--:--';
            const start = new Date(dateStr).getTime();
            const totalSeconds = Math.floor((this.now - start) / 1000);
            
            if (totalSeconds < 0) return '00:00';
            
            const hours = Math.floor(totalSeconds / 3600);
            const mins = Math.floor((totalSeconds % 3600) / 60);
            const secs = totalSeconds % 60;
            
            if (hours > 0) return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}h`;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        getSlaClasses(dateStr) {
            const totalSeconds = Math.floor((this.now - new Date(dateStr).getTime()) / 1000);
            let classes = "timer text-xl font-bold kds-font-mono transition-colors duration-300 ";
            
            if (totalSeconds > 1800) return classes + 'text-red-500 animate-pulse'; 
            else if (totalSeconds > 1200) return classes + 'text-red-500';          
            else if (totalSeconds > 600) return classes + 'text-yellow-400';        
            else return classes + 'text-gray-300';
        },
        
        isLateBorder(dateStr) {
            return ((this.now - new Date(dateStr).getTime()) / 1000) > 1200; 
        },

        playNotification() {
            if (this.audioAlert) {
                this.audioAlert.currentTime = 0;
                this.audioAlert.play().catch(e => console.log("Som bloqueado pelo navegador"));
            }
        },

        // --- 6. HIERARQUIA VISUAL (NOVOS vs ANTIGOS) ---
        
        // Verifica se o item é REALMENTE novo e merece destaque (Laranja/Amarelo)
        isItemNew(item, pedido) {
            // DETECTA TEXTOS DE AÇÃO (Lógica Robusta)
            if (item.observacao) {
                const obs = item.observacao.toUpperCase();
                if (
                    obs.includes('ACRESCENTAR MAIS') || 
                    obs.includes('ACRÉSCIMO') || 
                    obs.includes('REFORÇO') || 
                    obs.includes('ADICIONADO')
                ) {
                    return true; // Pinta de Laranja/Amarelo
                }
            }

            // Lógica Temporal (Backup de segurança)
            if (!item.created_at || !pedido.created_at) return false;
            const itemTime = new Date(item.created_at).getTime();
            const orderTime = new Date(pedido.created_at).getTime();
            return (itemTime - orderTime) > 60000; 
        },

        // Helper para saber se o pedido tem ALGUM item novo
        hasNewItems(pedido) {
            if (!pedido || !pedido.itens) return false;
            return pedido.itens.some(i => this.isItemNew(i, pedido));
        },

        // Verifica se o item é "recente" (últimos 3 min) para o destaque de "Novo Pedido"
        isRecentItem(itemDate) {
            if (!itemDate) return false;
            const diff = (this.now - new Date(itemDate).getTime()) / 1000;
            return diff < 180; // 3 minutos
        },

        get pedidosFiltrados() {
            if (this.filtro === 'todos') return this.pedidos;
            
            return this.pedidos.map(p => {
                const itensDaEstacao = p.itens.filter(i => i.categoria === this.filtro);
                if (itensDaEstacao.length === 0) return null;
                return { ...p, itens: itensDaEstacao };
            }).filter(p => p !== null);
        }
    }
}