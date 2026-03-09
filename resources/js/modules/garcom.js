/* resources/js/modules/garcom.js */

export default function garcomPro() {
    return {
        // --- ESTADO ---
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
        serverOk: true,
        clock: '--:--',
        listaMesas: [],
        mesasAlertadas: [],
        showWifi: false,
        confirmModal: { open: false, title: '', message: '', action: null },
        filaOffline: [],
        filaProcessando: false,

        // --- GETTERS ---
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

        // --- INICIALIZAÇÃO ---
        init() {
            this.startClock();
            this.setupSocket();
            this.carregarCardapio();
            this.fetchStatusMesas();
            this.carregarFilaOffline();
            setInterval(() => this.fetchStatusMesas(), 10000);
            this.gerarQRCode(
                window.AppConfig?.wifiSsid,
                window.AppConfig?.wifiPass
            );

            // Reconexão ao voltar do background (iOS PWA)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') this.reconnect();
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
            this.fetchStatusMesas();
            this.processarFilaOffline();
        },

        // --- HEALTH STATUS ---
        get healthStatus() {
            if (this.online && this.serverOk) return 'online';
            if (this.serverOk) return 'partial';
            return 'offline';
        },

        // --- RELÓGIO (com timezone do AppConfig) ---
        startClock() {
            const serverTimezone = window.AppConfig?.timezone || 'Europe/Lisbon';
            const update = () => {
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

        // --- SOCKET (usa instância partilhada) ---
        setupSocket() {
            this.socket = window.socket;
            if (!this.socket) return;

            this.online = this.socket.connected;
            this.socket.on('connect', () => { this.online = true; this.fetchStatusMesas(); });
            this.socket.on('disconnect', () => this.online = false);
            this.socket.on('connect_error', () => { this.online = false; });

            this.socket.on('pedido_pronto', (dados) => {
                this.fetchStatusMesas();
                if (dados?.mesa) this.flashMesa(dados.mesa);
                if (window.KDSNotifier) {
                    window.KDSNotifier.playOrderReady();
                    window.KDSNotifier.vibrateAlert();
                    window.KDSNotifier.showToast(`${__t('table')} ${dados?.mesa || '?'} — ${__t('notif_order_ready')}`, 'success');
                    window.KDSNotifier.showPush(__t('notif_order_ready'), `${__t('table')} ${dados?.mesa || '?'} — ${__t('notif_pick_kitchen')}`, { tag: 'pronto-' + Date.now() });
                }
            });

            this.socket.on('cozinha_novo_pedido', () => this.fetchStatusMesas());

            this.socket.on('cozinha_atualizar_status', () => {
                this.fetchStatusMesas();
                if (this.painelMesaOpen && window.KDSNotifier) {
                    window.KDSNotifier.vibrate(50);
                    window.KDSNotifier.playPing();
                }
            });

            this.socket.on('pedido_pago', (dados) => {
                this.fetchStatusMesas();
                if (dados?.mesa && this.mesaSelecionada == dados.mesa) {
                    this.painelMesaOpen = false;
                    this.mesaSelecionada = null;
                    if (window.KDSNotifier) window.KDSNotifier.showToast(`${__t('table')} ${dados.mesa} — ${__t('notif_bill_closed')}`, 'info');
                }
            });
        },

        // --- API ---
        async fetchStatusMesas() {
            try {
                const resConfig = await window.axios.get('/api/mesas-configuradas');
                let mesasMapeadas = resConfig.data.map(m => ({
                    numero: m.numero,
                    ocupada: false,
                    label: m.label,
                    pedidos: [],
                    total: 0
                }));

                const resStatus = await window.axios.get('/api/gerente/resumo-mesas');
                if (resStatus.data) {
                    resStatus.data.forEach(m => {
                        if (m.pedidos && m.pedidos.length > 0) {
                            const mesa = mesasMapeadas.find(t => t.numero == m.mesa);
                            if (mesa) {
                                mesa.ocupada = true;
                                mesa.pedidos = m.pedidos;
                                mesa.total = m.total;
                                if (this.mesaSelecionada == m.mesa && this.painelMesaOpen) {
                                    this.mesaDadosAtuais = mesa;
                                }
                            }
                        }
                    });
                }
                this.listaMesas = mesasMapeadas;
            } catch (e) { /* sync error */ }
        },

        async carregarCardapio() {
            try {
                const res = await window.axios.get('/api/produtos');
                this.produtos = res.data;
                this.categoriasUnicas = [...new Set(this.produtos.map(p => p.categoria))].sort();
            } catch (e) { /* load error */ }
        },

        // --- INTERFACE ---
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
            if (window.KDSNotifier) window.KDSNotifier.vibrate(10);
        },

        fecharMonitor() {
            this.painelMesaOpen = false;
            this.mesaSelecionada = null;
        },

        trocarMesa() {
            const acao = () => {
                this.cartOpen = false;
                this.painelMesaOpen = false;
                this.carrinho = [];
                this.mesaSelecionada = null;
                this.fetchStatusMesas();
            };
            this.carrinho.length > 0 ? this.askConfirm(__t('confirm_leave_order'), acao) : acao();
        },

        voltar() {
            const acao = () => {
                this.cartOpen = false;
                this.carrinho = [];
                const mesa = this.listaMesas.find(m => m.numero == this.mesaSelecionada);
                if (mesa && mesa.ocupada) {
                    this.mesaDadosAtuais = mesa;
                    this.painelMesaOpen = true;
                } else {
                    this.mesaSelecionada = null;
                    this.painelMesaOpen = false;
                }
            };
            this.carrinho.length > 0 ? this.askConfirm(__t('confirm_cancel_order'), acao) : acao();
        },

        addProduto(produto) {
            if (window.KDSNotifier) window.KDSNotifier.vibrate(5);
            this.badgeAnim = true;
            setTimeout(() => this.badgeAnim = false, 300);
            const existing = this.carrinho.find(i => i.id_produto === produto.id && !i.obs);
            if (existing) { existing.qtd++; } else {
                this.carrinho.push({ uuid: Date.now() + Math.random(), id_produto: produto.id, nome: produto.nome, preco: produto.preco, categoria: produto.categoria, qtd: 1, obs: '' });
            }
        },

        incrementarItem(index) { this.carrinho[index].qtd++; if (window.KDSNotifier) window.KDSNotifier.vibrate(5); },
        decrementarItem(index) {
            if (this.carrinho[index].qtd > 1) { this.carrinho[index].qtd--; if (window.KDSNotifier) window.KDSNotifier.vibrate(5); }
            else { this.askConfirm(__t('confirm_remove_item'), () => this.removerItem(index)); }
        },
        removerItem(index) { this.carrinho.splice(index, 1); if (this.carrinho.length === 0) this.cartOpen = false; },
        getQtyCart(pid) { return this.carrinho.filter(i => i.id_produto === pid).reduce((acc, i) => acc + i.qtd, 0); },

        askConfirm(msg, callback) {
            if (window.KDSNotifier) window.KDSNotifier.vibrate(10);
            this.confirmModal.message = msg;
            this.confirmModal.title = __t('attention');
            this.confirmModal.action = callback;
            this.confirmModal.open = true;
        },

        // --- ENVIO DE PEDIDO ---
        async enviarPedido() {
            this.loading = true;
            const payload = {
                mesa: this.mesaSelecionada,
                itens: this.carrinho.map(i => ({ id_produto: i.id_produto, qtd: i.qtd, obs: i.obs }))
            };

            try {
                const res = await window.axios.post('/api/criar-pedido', payload);

                if (res.data.status === 'sucesso') {
                    this.cartOpen = false;
                    this.showSuccessToast();
                    this.carrinho = [];
                    await this.fetchStatusMesas();
                    const mesa = this.listaMesas.find(m => m.numero == this.mesaSelecionada);
                    if (mesa) this.selecionarMesa(mesa);
                }
            } catch (e) {
                // Sem rede — guardar na fila offline
                this.guardarNaFilaOffline(payload);
                this.cartOpen = false;
                this.carrinho = [];
            } finally { this.loading = false; }
        },

        // --- FILA OFFLINE ---
        guardarNaFilaOffline(payload) {
            const item = { ...payload, timestamp: Date.now() };
            this.filaOffline.push(item);
            this.salvarFilaOffline();
            if (window.KDSNotifier) {
                window.KDSNotifier.showToast(`${__t('table')} ${payload.mesa} — ${__t('notif_saved_queue')} (${this.filaOffline.length})`, 'warning', 3000);
                window.KDSNotifier.vibrate(20);
            }
        },

        salvarFilaOffline() {
            try { localStorage.setItem('kds_fila_offline', JSON.stringify(this.filaOffline)); } catch {}
        },

        carregarFilaOffline() {
            try {
                const data = localStorage.getItem('kds_fila_offline');
                if (data) {
                    this.filaOffline = JSON.parse(data);
                    // Descartar pedidos com mais de 4 horas
                    const limite = Date.now() - (4 * 60 * 60 * 1000);
                    this.filaOffline = this.filaOffline.filter(p => p.timestamp > limite);
                    this.salvarFilaOffline();
                    if (this.filaOffline.length > 0) this.processarFilaOffline();
                }
            } catch { this.filaOffline = []; }
        },

        async processarFilaOffline() {
            if (this.filaProcessando || this.filaOffline.length === 0) return;
            this.filaProcessando = true;

            const pendentes = [...this.filaOffline];
            const enviados = [];

            for (const pedido of pendentes) {
                try {
                    const { timestamp, ...payload } = pedido;
                    await window.axios.post('/api/criar-pedido', payload);
                    enviados.push(pedido);
                } catch {
                    break; // Parar se ainda sem rede
                }
            }

            if (enviados.length > 0) {
                this.filaOffline = this.filaOffline.filter(p => !enviados.includes(p));
                this.salvarFilaOffline();
                this.fetchStatusMesas();
                if (window.KDSNotifier) {
                    window.KDSNotifier.showToast(`${enviados.length} ${__t('notif_queue_sent')}`, 'success', 3000);
                    window.KDSNotifier.playNewOrder();
                }
            }

            this.filaProcessando = false;
        },

        descartarFilaOffline() {
            this.filaOffline = [];
            this.salvarFilaOffline();
            if (window.KDSNotifier) window.KDSNotifier.showToast(__t('notif_queue_cleared'), 'info');
        },

        // --- UTILS ---
        hasReadyOrder(mesa) {
            return mesa.pedidos && mesa.pedidos.some(p => p.status === 'pronto');
        },

        flashMesa(numMesa) {
            numMesa = parseInt(numMesa);
            if (!this.mesasAlertadas.includes(numMesa)) {
                this.mesasAlertadas.push(numMesa);
                setTimeout(() => {
                    this.mesasAlertadas = this.mesasAlertadas.filter(m => m !== numMesa);
                }, 10000);
            }
        },

        gerarQRCode(ssid, pass) {
            if (typeof QRCode === 'undefined') {
                const el = document.getElementById("qrcode");
                if (el) el.innerHTML = '<span class="text-xs text-red-500 font-bold">Sem Internet</span>';
                return;
            }
            const el = document.getElementById("qrcode");
            if (ssid && ssid !== __t('not_configured') && el) {
                el.innerHTML = '';
                const wifiString = `WIFI:S:${ssid};T:WPA;P:${pass};;`;
                new QRCode(el, { text: wifiString, width: 180, height: 180 });
            }
        },

        showSuccessToast() {
            const toast = document.createElement('div');
            toast.className = 'fixed inset-0 z-[200] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm';
            toast.innerHTML = `<div class="bg-[#7ed957] rounded-full p-4 text-black shadow-lg shadow-[#7ed957]/30"><svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></div><span class="font-black text-2xl uppercase tracking-widest text-[#7ed957] mt-3">${__t('sent')}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 1500);
        }
    }
}
