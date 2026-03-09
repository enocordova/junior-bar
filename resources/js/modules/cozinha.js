/* resources/js/modules/cozinha.js */
import axios from 'axios';
import notifier from './notifications';

export default function kdsSystem() {
    return {
        // --- ESTADO ---
        pedidos: [],
        online: false,
        serverOk: true,
        socket: null,
        clock: '--:--',
        now: Date.now(),
        filtro: 'todos',
        wakeLock: null,

        // --- RECALL (Undo) ---
        pedidosFinalizando: [],
        undoData: null,
        undoTimer: null,
        undoCountdown: 3,
        undoCountdownInterval: null,

        // --- ALERTAS VISUAIS ---
        audioAlert: null,
        pedidosAlterados: [],

        // --- INICIALIZAÇÃO ---
        init() {
            this.setupSocket();
            this.startClock();
            this.fetchActiveOrders();
            this.requestWakeLock();

            // Reconexão ao voltar do background (iOS PWA suspende o WebSocket)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.requestWakeLock();
                    this.reconnect();
                }
            });

            // iOS PWA: pageshow é mais fiável que visibilitychange em alguns casos
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) this.reconnect();
            });
        },

        // Força reconexão do socket + re-fetch dos pedidos
        reconnect() {
            if (this.socket) {
                this.socket.disconnect();
                this.socket.connect();
            }
            if (window.healthCheck) window.healthCheck.ping();
            this.fetchActiveOrders();
        },

        async requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    this.wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (err) {
                // Erro esperado se bateria estiver crítica
            }
        },

        // --- SOCKET (usa instância partilhada) ---
        setupSocket() {
            this.socket = window.socket;
            if (!this.socket) return;

            this.online = this.socket.connected;
            this.socket.on('connect', () => {
                this.online = true;
                this.fetchActiveOrders();
            });
            this.socket.on('disconnect', () => this.online = false);
            this.socket.on('connect_error', () => { this.online = false; });

            this.socket.on('cozinha_novo_pedido', (pedido) => {
                this.addOrUpdateLocal(pedido);
            });

            this.socket.on('cozinha_atualizar_status', (dados) => {
                if (this.undoData && this.undoData.id == dados.id) return;

                const index = this.pedidos.findIndex(p => p.id == dados.id);
                if (index !== -1) {
                    this.pedidos[index].status = dados.status;
                    if (dados.status === 'pronto' || dados.status === 'cancelado') {
                        this.pedidos.splice(index, 1);
                    }
                }
            });

            this.socket.on('pedido_pronto', (dados) => {
                this.pedidos = this.pedidos.filter(p => p.id != dados.id);
            });
        },

        // --- DADOS ---
        async fetchActiveOrders() {
            try {
                const res = await axios.get('/api/pedidos-ativos');
                this.pedidos = res.data.filter(p => !this.pedidosFinalizando.includes(p.id));
            } catch (e) { /* fetch error */ }
        },

        addOrUpdateLocal(pedido) {
            if (pedido.status !== 'pendente' && pedido.status !== 'preparo') {
                const existingIndex = this.pedidos.findIndex(p => p.id === pedido.id);
                if (existingIndex !== -1) {
                    this.pedidos.splice(existingIndex, 1);
                }
                return;
            }

            const index = this.pedidos.findIndex(p => p.id === pedido.id);

            if (index !== -1) {
                this.pedidos[index] = pedido;
                if (pedido.event_type === 'update') {
                    this.triggerUpdateFlash(pedido.id);
                }
                notifier.playUpdate();
                notifier.vibrateTap();
                notifier.showPush(__t('notif_order_changed'), `${__t('table')} ${pedido.mesa}`, { tag: 'update-' + pedido.id });
            } else {
                this.pedidos.push(pedido);
                notifier.playNewOrder();
                notifier.vibrateAlert();
                notifier.showPush(__t('notif_new_order'), `${__t('table')} ${pedido.mesa}`, { tag: 'novo-' + pedido.id });
            }
        },

        // --- ALERTA VISUAL ---
        triggerUpdateFlash(id) {
            if (!this.pedidosAlterados.includes(id)) {
                this.pedidosAlterados.push(id);
                setTimeout(() => {
                    this.pedidosAlterados = this.pedidosAlterados.filter(pid => pid !== id);
                }, 15000);
            }
        },

        isUpdated(id) {
            return this.pedidosAlterados.includes(id);
        },

        // --- AÇÕES & RECALL ---
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
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_connection'), 'error');
            }
        },

        iniciarFinalizacao(pedido) {
            if (this.undoData) this.confirmarFinalizacao(this.undoData.id);

            this.pedidosFinalizando.push(pedido.id);
            this.undoCountdown = 3;
            this.undoData = { id: pedido.id, mesa: pedido.mesa };

            if (this.undoTimer) clearTimeout(this.undoTimer);
            if (this.undoCountdownInterval) clearInterval(this.undoCountdownInterval);

            this.undoCountdownInterval = setInterval(() => {
                this.undoCountdown--;
                if (this.undoCountdown <= 0) {
                    clearInterval(this.undoCountdownInterval);
                    this.undoCountdownInterval = null;
                }
            }, 1000);

            this.undoTimer = setTimeout(() => {
                this.confirmarFinalizacao(pedido.id);
            }, 3000);
        },

        desfazerFinalizacao() {
            if (!this.undoData) return;
            const idParaRestaurar = this.undoData.id;

            clearTimeout(this.undoTimer);
            if (this.undoCountdownInterval) clearInterval(this.undoCountdownInterval);

            this.pedidosFinalizando = this.pedidosFinalizando.filter(id => id !== idParaRestaurar);

            this.undoData = null;
            this.undoTimer = null;
            this.undoCountdownInterval = null;
        },

        async confirmarFinalizacao(id) {
            if (this.undoData && this.undoData.id === id) {
                this.undoData = null;
                this.undoTimer = null;
                if (this.undoCountdownInterval) clearInterval(this.undoCountdownInterval);
                this.undoCountdownInterval = null;
            }
            try {
                await axios.post(`/api/atualizar-status/${id}`, { status: 'pronto' });

                this.pedidos = this.pedidos.filter(p => p.id !== id);
                this.pedidosFinalizando = this.pedidosFinalizando.filter(pid => pid !== id);

                this.socket.emit('atualizar_status_pedido', { id: id, status: 'pronto' });
            } catch (e) {
                this.pedidosFinalizando = this.pedidosFinalizando.filter(pid => pid !== id);
            }
        },

        // --- TEMPO & UTILS ---
        startClock() {
            const update = () => {
                this.now = Date.now();
                const serverTimezone = window.AppConfig?.timezone || 'Europe/Lisbon';
                this.clock = new Date().toLocaleTimeString(window.AppConfig?.dateLocale || 'pt-PT', {
                    timeZone: serverTimezone,
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
                // Sincronizar estado do health check global
                if (window.healthCheck) {
                    this.serverOk = window.healthCheck.laravelOk;
                }
            };
            update();
            setInterval(update, 1000);
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
            const slaAmarelo = window.AppConfig?.slaAmareloSec || 600;
            const slaVermelho = window.AppConfig?.slaVermelhoSec || 1200;
            let classes = "timer text-xl font-bold kds-font-mono transition-colors duration-300 ";

            if (totalSeconds > slaVermelho * 1.5) return classes + 'text-red-500 animate-pulse';
            else if (totalSeconds > slaVermelho) return classes + 'text-red-500';
            else if (totalSeconds > slaAmarelo) return classes + 'text-yellow-400';
            else return classes + 'text-gray-300';
        },

        isLateBorder(dateStr) {
            const slaVermelho = window.AppConfig?.slaVermelhoSec || 1200;
            return ((this.now - new Date(dateStr).getTime()) / 1000) > slaVermelho;
        },

        playNotification() {
            notifier.playNewOrder();
            notifier.vibrateAlert();
        },

        // --- HIERARQUIA VISUAL ---
        isItemNew(item, pedido) {
            if (item.observacao) {
                const obs = item.observacao.toUpperCase();
                if (
                    obs.includes('ACRESCENTAR MAIS') ||
                    obs.includes('ACRÉSCIMO') ||
                    obs.includes('REFORÇO') ||
                    obs.includes('ADICIONADO')
                ) {
                    return true;
                }
            }
            if (!item.created_at || !pedido.created_at) return false;
            const itemTime = new Date(item.created_at).getTime();
            const orderTime = new Date(pedido.created_at).getTime();
            return (itemTime - orderTime) > 60000;
        },

        hasNewItems(pedido) {
            if (!pedido || !pedido.itens) return false;
            return pedido.itens.some(i => this.isItemNew(i, pedido));
        },

        isRecentItem(itemDate) {
            if (!itemDate) return false;
            const diff = (this.now - new Date(itemDate).getTime()) / 1000;
            return diff < 180;
        },

        // --- HEALTH STATUS (combinado: socket + laravel + node) ---
        get healthStatus() {
            if (this.online && this.serverOk) return 'online';
            if (this.serverOk) return 'partial';
            return 'offline';
        },

        get pedidosFiltrados() {
            if (this.filtro === 'todos') return this.pedidos;

            const categoriasPorFiltro = {
                'cozinha':       ['porções', 'caldos', 'lanches', 'acompanhamentos'],
                'churrasqueira': ['espetinhos'],
                'bebidas':       ['bebidas', 'sucos'],
            };

            const cats = categoriasPorFiltro[this.filtro] || [];

            return this.pedidos.map(p => {
                const itensDaEstacao = p.itens.filter(i => cats.includes((i.categoria || '').toLowerCase()));
                if (itensDaEstacao.length === 0) return null;
                return { ...p, itens: itensDaEstacao };
            }).filter(p => p !== null);
        },
    }
}
