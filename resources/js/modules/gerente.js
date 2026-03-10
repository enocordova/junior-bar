/* resources/js/modules/gerente.js */

export default function gerenteApp() {
    return {
        // --- ESTADO ---
        mesas: [],
        mesaSelecionada: null,
        confirmandoFecho: false,
        editandoItem: null,
        adicionandoItem: false,
        addCartOpen: false,
        badgeAnimAdd: false,
        carrinhoAdd: [],
        enviandoAdd: false,
        listaProdutos: [],
        searchProduto: '',
        filtroCategoria: '',
        socket: null,
        clock: '',
        online: false,
        serverOk: true,
        ready: false,
        mesasAlteradas: [],

        // --- INICIALIZAÇÃO ---
        init() {
            this.startClock();
            this.setupSocket();
            this.fetchDados();
            this.carregarProdutos();
            this.ready = true;

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
            this.fetchDados();
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
            this.socket.on('connect', () => { this.online = true; this.fetchDados(); });
            this.socket.on('disconnect', () => this.online = false);
            this.socket.on('connect_error', () => { this.online = false; });

            this.socket.on('cozinha_novo_pedido', (dados) => {
                this.fetchDados();
                if (dados?.mesa) this.flashMesa(dados.mesa);
                if (window.KDSNotifier) {
                    window.KDSNotifier.playNewOrder();
                    window.KDSNotifier.showToast(`${__t('notif_new_order')} — ${__t('table')} ${dados?.mesa || '?'}`, 'info');
                    window.KDSNotifier.showPush(__t('notif_new_order'), `${__t('table')} ${dados?.mesa || '?'}`, { tag: 'novo-pedido' });
                }
            });

            this.socket.on('cozinha_atualizar_status', (dados) => {
                this.fetchDados();
                if (dados?.mesa) this.flashMesa(dados.mesa);
                if (window.KDSNotifier) window.KDSNotifier.playPing();
            });

            this.socket.on('pedido_pronto', (dados) => {
                this.fetchDados();
                if (dados?.mesa) this.flashMesa(dados.mesa);
                if (window.KDSNotifier) {
                    window.KDSNotifier.playOrderReady();
                    window.KDSNotifier.showToast(`${__t('table')} ${dados?.mesa || '?'} — ${__t('notif_order_ready')}`, 'success');
                }
            });

            this.socket.on('pedido_pago', (dados) => {
                this.fetchDados();
                if (window.KDSNotifier) {
                    window.KDSNotifier.playPayment();
                    window.KDSNotifier.showToast(`${__t('table')} ${dados?.mesa || '?'} — ${__t('notif_bill_closed_u')}`, 'success', 4000);
                }
            });
        },

        // --- LÓGICA DE AGRUPAMENTO ---
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
                            statusBreakdown: { pendente: 0, preparo: 0, pronto: 0 }
                        };
                    }
                    mapa[chave].quantidade += item.quantidade;
                    mapa[chave].ids_reais.push(item.id);
                    let statusAtual = pedido.status || 'pendente';
                    if (mapa[chave].statusBreakdown[statusAtual] !== undefined) {
                        mapa[chave].statusBreakdown[statusAtual] += item.quantidade;
                    }
                    let obsLimpa = item.observacao;
                    if (obsLimpa && !obsLimpa.includes('Acrescentar +')) {
                        mapa[chave].observacoesUnicas.add(obsLimpa);
                    }
                });
            });

            return Object.values(mapa).sort((a, b) => a.nome_produto.localeCompare(b.nome_produto));
        },

        get categoriasUnicas() {
            return [...new Set(this.listaProdutos.map(p => p.categoria))].sort();
        },

        get produtosFiltrados() {
            let lista = this.listaProdutos;
            if (this.filtroCategoria !== '') lista = lista.filter(p => p.categoria === this.filtroCategoria);
            if (this.searchProduto !== '') lista = lista.filter(p => p.nome.toLowerCase().includes(this.searchProduto.toLowerCase()));
            return lista;
        },

        formatStatus(status) {
            const map = { 'pendente': __t('status_pending'), 'preparo': __t('status_preparing'), 'pronto': __t('status_ready') };
            return map[status] || status;
        },

        // --- API ---
        async carregarProdutos() {
            try {
                const res = await window.axios.get('/api/produtos');
                this.listaProdutos = res.data;
            } catch (e) { /* load error */ }
        },

        async fetchDados() {
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
                        pedidos:   dadosMesa ? dadosMesa.pedidos : [],
                        total:     dadosMesa ? parseFloat(dadosMesa.total) : 0,
                        garcons:   dadosMesa ? dadosMesa.garcons : [],
                    };
                });

                if (this.mesaSelecionada) {
                    const atualizada = this.mesas.find(m => m.numero === this.mesaSelecionada.numero);
                    if (atualizada) {
                        // Merge in-place to avoid full DOM re-render flash on mobile
                        this.mesaSelecionada.pedidos = atualizada.pedidos;
                        this.mesaSelecionada.total   = atualizada.total;
                        this.mesaSelecionada.garcons = atualizada.garcons;

                        if (this.editandoItem) {
                            const novoConsolidado = this.itensConsolidados.find(i => i.chaveUnica === this.editandoItem.chaveUnica);
                            if (novoConsolidado) {
                                this.editandoItem.quantidade = novoConsolidado.quantidade;
                                this.editandoItem.ids_reais = novoConsolidado.ids_reais;
                                this.editandoItem.observacoesUnicas = novoConsolidado.observacoesUnicas;
                                this.editandoItem.statusBreakdown = novoConsolidado.statusBreakdown;
                            } else {
                                this.editandoItem = null;
                            }
                        }
                    } else {
                        this.mesaSelecionada = null;
                        this.editandoItem = null;
                    }
                }
            } catch (e) { /* sync error */ }
        },

        // --- EDIÇÃO DE ITENS ---
        abrirEdicaoLote(itemConsolidado) {
            const prodOriginal = this.listaProdutos.find(p => p.nome === itemConsolidado.nome_produto);
            this.editandoItem = {
                ...itemConsolidado,
                produto_id_real: prodOriginal ? prodOriginal.id : null
            };
        },

        async adicionarMaisUmNoLote() {
            if (!this.editandoItem || !this.editandoItem.produto_id_real) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('notif_product_na'), 'error');
                return;
            }
            try {
                await window.axios.post('/api/gerente/adicionar-item-mesa', { mesa: this.mesaSelecionada.numero, produto_id: this.editandoItem.produto_id_real });
                this.editandoItem.quantidade++;
                this.fetchDados();
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_add'), 'error');
            }
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
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_remove'), 'error');
            }
        },

        async removerTodosDoLote() {
            if (!this.editandoItem) return;
            try {
                const promises = this.editandoItem.ids_reais.map(id => window.axios.delete(`/api/gerente/remover-item/${id}`));
                await Promise.all(promises);
                this.editandoItem = null;
                this.fetchDados();
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_remove_batch'), 'error');
            }
        },

        // --- ADICIONAR PRODUTO (CARRINHO) ---
        abrirAdicionarItem() {
            this.searchProduto = '';
            this.filtroCategoria = '';
            this.carrinhoAdd = [];
            this.addCartOpen = false;
            this.adicionandoItem = true;
        },

        fecharAdicionarItem() {
            this.adicionandoItem = false;
            this.carrinhoAdd = [];
            this.addCartOpen = false;
        },

        addProdutoAoCarrinho(produto) {
            if (window.KDSNotifier) window.KDSNotifier.vibrate(5);
            this.badgeAnimAdd = true;
            setTimeout(() => this.badgeAnimAdd = false, 300);
            const existing = this.carrinhoAdd.find(i => i.id_produto === produto.id && !i.obs);
            if (existing) {
                existing.qtd++;
            } else {
                this.carrinhoAdd.push({
                    uuid: Date.now() + Math.random(),
                    id_produto: produto.id,
                    nome: produto.nome,
                    preco: produto.preco,
                    categoria: produto.categoria,
                    qtd: 1,
                    obs: ''
                });
            }
        },

        getQtyCarrinho(prodId) {
            return this.carrinhoAdd.filter(i => i.id_produto === prodId).reduce((acc, i) => acc + i.qtd, 0);
        },

        get totalItensAdd() {
            return this.carrinhoAdd.reduce((acc, i) => acc + i.qtd, 0);
        },

        get totalCarrinhoAdd() {
            return this.carrinhoAdd.reduce((acc, item) => acc + (parseFloat(item.preco) * item.qtd), 0).toFixed(2);
        },

        incrementarAdd(index) { this.carrinhoAdd[index].qtd++; if (window.KDSNotifier) window.KDSNotifier.vibrate(5); },
        decrementarAdd(index) {
            if (this.carrinhoAdd[index].qtd > 1) { this.carrinhoAdd[index].qtd--; if (window.KDSNotifier) window.KDSNotifier.vibrate(5); }
            else { this.carrinhoAdd.splice(index, 1); if (this.carrinhoAdd.length === 0) this.addCartOpen = false; }
        },
        removerDoCarrinho(index) {
            this.carrinhoAdd.splice(index, 1);
            if (this.carrinhoAdd.length === 0) this.addCartOpen = false;
        },

        async enviarItensAdicionados() {
            if (this.carrinhoAdd.length === 0 || !this.mesaSelecionada) return;
            this.enviandoAdd = true;
            try {
                for (const item of this.carrinhoAdd) {
                    await window.axios.post('/api/gerente/adicionar-item-mesa', {
                        mesa: this.mesaSelecionada.numero,
                        produto_id: item.id_produto,
                        quantidade: item.qtd,
                        observacao: item.obs || null
                    });
                }
                this.carrinhoAdd = [];
                this.adicionandoItem = false;
                this.addCartOpen = false;
                this.showSuccessToast();
                this.fetchDados();
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_send_items'), 'error');
            } finally { this.enviandoAdd = false; }
        },

        showSuccessToast() {
            const toast = document.createElement('div');
            toast.className = 'fixed inset-0 z-[200] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm';
            toast.innerHTML = `<div class="bg-[#7ed957] rounded-full p-4 text-black shadow-lg shadow-[#7ed957]/30"><svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></div><span class="font-black text-2xl uppercase tracking-widest text-[#7ed957] mt-3">${__t('sent')}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 1500);
        },

        // --- MESAS ---
        getMesaClasses(mesa) {
            if (this.mesasAlteradas.includes(mesa.numero)) {
                return 'border-[#7ed957] shadow-[0_0_20px_rgba(126,217,87,0.3)] ring-2 ring-[#7ed957]/30 cursor-pointer active:scale-[0.97] z-10';
            }
            if (mesa.pedidos.length > 0) {
                if (mesa.pedidos.some(p => p.status === 'pronto')) {
                    return 'border-[#7ed957] shadow-[0_0_15px_rgba(126,217,87,0.15)] cursor-pointer active:scale-[0.97] z-10';
                }
                return 'border-[#bf7854] shadow-[0_0_15px_rgba(191,120,84,0.15)] cursor-pointer active:scale-[0.97] z-10';
            }
            return 'border-gray-800 text-gray-600 cursor-default hover:border-gray-700';
        },

        flashMesa(numMesa) {
            numMesa = parseInt(numMesa);
            if (!this.mesasAlteradas.includes(numMesa)) {
                this.mesasAlteradas.push(numMesa);
                setTimeout(() => {
                    this.mesasAlteradas = this.mesasAlteradas.filter(m => m !== numMesa);
                }, 5000);
            }
        },

        contarLivres() { return this.mesas.filter(m => m.pedidos.length === 0).length; },
        contarOcupadas() { return this.mesas.filter(m => m.pedidos.length > 0).length; },

        abrirMesa(mesa) {
            if (mesa.pedidos.length > 0) this.mesaSelecionada = mesa;
        },

        // --- FECHO DE CONTA ---
        abrirConfirmacaoFecho() { if (this.mesaSelecionada) this.confirmandoFecho = true; },

        async fecharContaConfirmada() {
            try {
                await window.axios.post(`/api/gerente/fechar-mesa/${this.mesaSelecionada.numero}`);
                this.confirmandoFecho = false;
                this.mesaSelecionada = null;
                this.fetchDados();
            } catch (e) {
                if (window.KDSNotifier) window.KDSNotifier.showToast(__t('err_close_bill'), 'error');
                this.confirmandoFecho = false;
            }
        },

        // --- IMPRESSÃO ---
        imprimirContaConsolidada() {
            if (!this.mesaSelecionada) return;
            const m = this.mesaSelecionada;
            const itens = this.itensConsolidados;
            const nomeBar = window.AppConfig?.nomeBar || 'Junior BAR';

            const dateLocale = window.AppConfig?.dateLocale || 'pt-PT';
            const w = window.open('', '', 'width=310,height=600');
            w.document.write(`
                <html>
                <head>
                    <title>${__t('table').toUpperCase()} ${m.numero}</title>
                    <style>
                        @page { size: 80mm auto; margin: 2mm 3mm; }
                        * { color: #000 !important; background: transparent !important; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                        body { font-family: 'Courier New', monospace; padding: 4px 6px; margin: 0; width: 100%; font-size: 11pt; font-weight: 700; }
                        .header { text-align: center; margin-bottom: 8px; }
                        .header h2 { font-size: 13pt; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 2px; }
                        .header h3 { font-size: 12pt; font-weight: 700; margin: 0 0 2px; }
                        .header p  { font-size: 10pt; font-weight: 700; margin: 0; }
                        .row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11pt; font-weight: 700; gap: 4px; }
                        .row span:first-child { flex: 1; overflow: hidden; }
                        .row span:last-child { white-space: nowrap; }
                        .total { font-weight: 900; font-size: 13pt; border-top: 2px solid #000; margin-top: 8px; padding-top: 6px; }
                        .qty { font-weight: 900; }
                        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>${nomeBar}</h2>
                        <h3>${__t('table').toUpperCase()} ${m.numero}</h3>
                        <p>${new Date().toLocaleString(dateLocale)}</p>
                    </div>
                    <hr>
                    ${itens.map(i => `
                        <div class="row">
                            <span><span class="qty">${i.quantidade}x</span> ${i.nome_produto}</span>
                            <span>${formatMoney(i.preco * i.quantidade)}</span>
                        </div>
                    `).join('')}
                    <div class="row total">
                        <span>${__t('total')}</span>
                        <span>${formatMoney(m.total)}</span>
                    </div>
                    <br><div style="text-align:center;">${__t('thank_you')}</div>
                </body>
                </html>
            `);
            w.document.close();
            setTimeout(() => { w.print(); w.close(); }, 500);
        }
    }
}
