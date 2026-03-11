/* resources/js/modules/cozinha.js */
import axios from 'axios';
import notifier from './notifications';

// Mapeamento estação → categorias (espelha o backend)
const ESTACAO_CATEGORIAS = {
    'cozinha':       ['porções', 'caldos', 'lanches', 'acompanhamentos'],
    'churrasqueira': ['espetinhos'],
    'bebidas':       ['bebidas', 'sucos'],
};

const ESTACAO_LABELS = {
    'cozinha':       'Cozinha',
    'churrasqueira': 'Churrasqueira',
    'bebidas':       'Bebidas',
    'todos':         null,
};

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

        // --- RECALL (Undo) por rodada ---
        pedidosRodadasFinalizando: [], // ex: ['12-1', '12-2']
        rodadasIniciadas: [],          // ex: ['12-2'] — rodadas 2+ já reconhecidas pela cozinha
        undoData: null,               // { pedidoId, rodada, estacao, mesa }
        undoTimer: null,
        undoCountdown: 3,
        undoCountdownInterval: null,

        // --- ALERTAS VISUAIS ---
        pedidosAlterados: [],

        // --- INICIALIZAÇÃO ---
        init() {
            this.setupSocket();
            this.startClock();
            this.fetchActiveOrders();
            this.requestWakeLock();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.requestWakeLock();
                    this.reconnect();
                }
            });

            window.addEventListener('pageshow', (e) => {
                if (e.persisted) this.reconnect();
            });
        },

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
            } catch (err) { /* erro esperado se bateria crítica */ }
        },

        // --- SOCKET ---
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

            // Novo pedido ou atualização de itens — reutiliza o mesmo evento
            this.socket.on('cozinha_novo_pedido', (pedido) => {
                this.addOrUpdateLocal(pedido);
            });

            this.socket.on('cozinha_atualizar_status', (dados) => {
                if (this.undoData && this.undoData.pedidoId == dados.id) return;
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
                const finalizando = this.pedidosRodadasFinalizando.map(k => parseInt(k.split('-')[0]));
                this.pedidos = res.data.filter(p => !finalizando.includes(p.id));
            } catch (e) { /* fetch error */ }
        },

        addOrUpdateLocal(pedido) {
            if (pedido.status !== 'pendente' && pedido.status !== 'preparo') {
                const existingIndex = this.pedidos.findIndex(p => p.id === pedido.id);
                if (existingIndex !== -1) this.pedidos.splice(existingIndex, 1);
                return;
            }

            const index = this.pedidos.findIndex(p => p.id === pedido.id);

            if (index !== -1) {
                this.pedidos[index] = pedido;
                if (pedido.event_type === 'update') {
                    this.triggerUpdateFlash(pedido.id);
                    notifier.playUpdate();
                    notifier.vibrateTap();
                    notifier.showPush(__t('notif_order_changed'), `${__t('table')} ${pedido.mesa}`, { tag: 'update-' + pedido.id });
                }
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

        // --- RODADAS (agrupamento por rodada, concluída = todos os itens da rodada prontos) ---
        getRodadas(pedido) {
            const rodadasMap = {};
            (pedido.itens || []).forEach(item => {
                const r = item.rodada || 1;
                if (!rodadasMap[r]) rodadasMap[r] = [];
                rodadasMap[r].push(item);
            });
            return Object.keys(rodadasMap)
                .map(n => parseInt(n))
                .sort((a, b) => a - b)
                .map(num => ({
                    numero: num,
                    itens:    rodadasMap[num],
                    // Rodada concluída quando TODOS os seus itens (visíveis no filtro atual) estão prontos
                    concluida: rodadasMap[num].every(i => i.status === 'pronto'),
                }));
        },

        rodadaEstaFinalizando(pedidoId, rodadaNum) {
            return this.pedidosRodadasFinalizando.includes(`${pedidoId}-${rodadaNum}`);
        },

        // Rodada 1 é iniciada automaticamente quando o pedido inicia.
        // Rodadas 2+ precisam de reconhecimento explícito pela cozinha.
        rodadaEstaIniciada(pedidoId, rodadaNum) {
            if (rodadaNum === 1) return true;
            return this.rodadasIniciadas.includes(`${pedidoId}-${rodadaNum}`);
        },

        iniciarRodada(pedidoId, rodadaNum) {
            const key = `${pedidoId}-${rodadaNum}`;
            if (!this.rodadasIniciadas.includes(key)) {
                this.rodadasIniciadas.push(key);
            }
        },

        // --- ESTAÇÃO: label e verificações ---
        getLabelEstacao() {
            return ESTACAO_LABELS[this.filtro] || null;
        },

        itemPertenceAFiltro(item) {
            if (this.filtro === 'todos') return true;
            const cats = ESTACAO_CATEGORIAS[this.filtro] || [];
            return cats.includes((item.categoria || '').toLowerCase());
        },

        // Rótulo do botão de finalização (usa estação quando filtro ativo)
        getLabelBotaoFinalizar(rodada, totalRodadas) {
            const label = this.getLabelEstacao();
            const multiRodada = totalRodadas > 1;

            if (label) {
                return multiRodada
                    ? `✔ FINALIZAR ${label.toUpperCase()} · R${rodada}`
                    : `✔ FINALIZAR ${label.toUpperCase()}`;
            }
            return multiRodada
                ? `✔ FINALIZAR RODADA ${rodada}`
                : __t('finish_order');
        },

        // Verifica se todos os itens da estação atual na rodada já estão prontos
        todosItensDaEstacaoProntos(rodada) {
            const itens = this.filtro !== 'todos'
                ? rodada.itens.filter(i => this.itemPertenceAFiltro(i))
                : rodada.itens;
            return itens.length > 0 && itens.every(i => i.status === 'pronto');
        },

        // --- AÇÕES ---

        // Toggle item individual: pendente/preparo ↔ pronto (toque no badge)
        itemsToggling: new Set(), // previne double-tap durante request em curso

        async toggleItemPronto(pedidoId, itemId) {
            if (this.itemsToggling.has(itemId)) return;
            const pedido = this.pedidos.find(p => p.id === pedidoId);
            if (!pedido) return;
            const item = pedido.itens.find(i => i.id === itemId);
            if (!item) return;

            this.itemsToggling.add(itemId);
            const anterior = item.status;
            item.status = item.status === 'pronto' ? 'pendente' : 'pronto';

            try {
                const res = await axios.post(`/api/item/${itemId}/toggle-pronto`);
                if (res.data.todas_concluidas) {
                    this.pedidos = this.pedidos.filter(p => p.id !== pedidoId);
                }
            } catch (e) {
                item.status = anterior;
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_connection'), 'error');
            } finally {
                this.itemsToggling.delete(itemId);
            }
        },

        // Iniciar preparo do pedido (pendente → preparo)
        async iniciarPreparo(pedido) {
            const oldStatus = pedido.status;
            pedido.status = 'preparo';
            try {
                await axios.post(`/api/atualizar-status/${pedido.id}`, { status: 'preparo' });
                this.socket.emit('atualizar_status_pedido', {
                    id: pedido.id, status: 'preparo', mesa: pedido.mesa
                });
            } catch (e) {
                pedido.status = oldStatus;
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_connection'), 'error');
            }
        },

        // Iniciar finalização de uma rodada (com undo de 3s)
        iniciarFinalizacaoRodada(pedido, rodadaNum) {
            if (this.undoData) {
                this.confirmarFinalizacaoRodada(
                    this.undoData.pedidoId,
                    this.undoData.rodada,
                    this.undoData.estacao
                );
            }

            const key = `${pedido.id}-${rodadaNum}`;
            this.pedidosRodadasFinalizando.push(key);
            this.undoCountdown = 3;
            this.undoData = {
                pedidoId: pedido.id,
                rodada:   rodadaNum,
                estacao:  this.filtro,
                mesa:     pedido.mesa,
            };

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
                this.confirmarFinalizacaoRodada(pedido.id, rodadaNum, this.filtro);
            }, 3000);
        },

        desfazerFinalizacao() {
            if (!this.undoData) return;
            const { pedidoId, rodada } = this.undoData;
            const key = `${pedidoId}-${rodada}`;

            clearTimeout(this.undoTimer);
            if (this.undoCountdownInterval) clearInterval(this.undoCountdownInterval);

            this.pedidosRodadasFinalizando = this.pedidosRodadasFinalizando.filter(k => k !== key);
            this.undoData = null;
            this.undoTimer = null;
            this.undoCountdownInterval = null;
        },

        async confirmarFinalizacaoRodada(pedidoId, rodadaNum, estacao) {
            if (
                this.undoData &&
                this.undoData.pedidoId === pedidoId &&
                this.undoData.rodada === rodadaNum
            ) {
                this.undoData = null;
                this.undoTimer = null;
                if (this.undoCountdownInterval) clearInterval(this.undoCountdownInterval);
                this.undoCountdownInterval = null;
            }

            const key = `${pedidoId}-${rodadaNum}`;

            try {
                const res = await axios.post(`/api/finalizar-rodada/${pedidoId}/${rodadaNum}`, {
                    estacao: estacao || 'todos',
                });

                if (res.data.todas_concluidas) {
                    this.pedidos = this.pedidos.filter(p => p.id !== pedidoId);
                }
                // Se não totalmente concluído, o broadcast 'cozinha_novo_pedido' já atualiza os estados dos itens
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_connection'), 'error');
            } finally {
                this.pedidosRodadasFinalizando = this.pedidosRodadasFinalizando.filter(k => k !== key);
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
            const mins  = Math.floor((totalSeconds % 3600) / 60);
            const secs  = totalSeconds % 60;

            if (hours > 0) return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}h`;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        getSlaClasses(dateStr) {
            const totalSeconds = Math.floor((this.now - new Date(dateStr).getTime()) / 1000);
            const slaAmarelo = window.AppConfig?.slaAmareloSec || 600;
            const slaVermelho = window.AppConfig?.slaVermelhoSec || 1200;
            let classes = "timer text-xl font-bold kds-font-mono transition-colors duration-300 ";

            if (totalSeconds > slaVermelho * 1.5) return classes + 'text-red-500 animate-pulse';
            else if (totalSeconds > slaVermelho)  return classes + 'text-red-500';
            else if (totalSeconds > slaAmarelo)   return classes + 'text-yellow-400';
            else                                  return classes + 'text-gray-300';
        },

        isLateBorder(dateStr) {
            const slaVermelho = window.AppConfig?.slaVermelhoSec || 1200;
            return ((this.now - new Date(dateStr).getTime()) / 1000) > slaVermelho;
        },

        playNotification() {
            notifier.playNewOrder();
            notifier.vibrateAlert();
        },

        isRecentItem(itemDate) {
            if (!itemDate) return false;
            return ((this.now - new Date(itemDate).getTime()) / 1000) < 180;
        },

        // --- HEALTH STATUS ---
        get healthStatus() {
            if (this.online && this.serverOk) return 'online';
            if (this.serverOk) return 'partial';
            return 'offline';
        },

        get pedidosFiltrados() {
            if (this.filtro === 'todos') return this.pedidos;

            const cats = ESTACAO_CATEGORIAS[this.filtro] || [];

            return this.pedidos.map(p => {
                const itensDaEstacao = p.itens.filter(i =>
                    cats.includes((i.categoria || '').toLowerCase())
                );
                if (itensDaEstacao.length === 0) return null;

                // Esconde o card da estação quando todos os seus itens já estão prontos
                if (itensDaEstacao.every(i => i.status === 'pronto')) return null;

                return { ...p, itens: itensDaEstacao };
            }).filter(p => p !== null);
        },
    }
}
