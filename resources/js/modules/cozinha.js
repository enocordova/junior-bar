import { io } from "socket.io-client";

class KDSManager {
    constructor() {
        // 1. CONFIGURAÇÃO DINÂMICA
        // Pega o IP definido no layout (kds.blade.php) ou usa fallback se falhar
        const SOCKET_URL = window.AppConfig?.socketUrl || "http://localhost:3000";

        // 2. INICIALIZAÇÃO DO SOCKET
        this.socket = io(SOCKET_URL, {
            reconnection: true,
            reconnectionAttempts: 10,
            transports: ['websocket'] // Força websocket para menor latência
        });
        
        // 3. CACHE DE ELEMENTOS DOM
        this.grid = document.getElementById('kds-grid');
        this.template = document.getElementById('ticket-template');
        this.connectionInd = document.getElementById('connection-indicator');
        
        // 4. ESTADO LOCAL (Map de Pedidos)
        // Usamos um Map para rastrear os pedidos em memória e evitar ler o DOM repetidamente
        this.orders = new Map();
        
        this.init();
    }

    init() {
        this.setupSocket();
        this.startGlobalClock();
        this.fetchActiveOrders();
    }

    setupSocket() {
        this.socket.on('connect', () => this.setOnline(true));
        this.socket.on('disconnect', () => this.setOnline(false));
        
        // Eventos de Negócio
        this.socket.on('cozinha_novo_pedido', (pedido) => this.addOrUpdateTicket(pedido, true));
        this.socket.on('cozinha_atualizar_status', (dados) => this.updateTicketStatus(dados));
        this.socket.on('pedido_pronto', (dados) => this.removeTicket(dados.id || dados.id_pedido));
    }

    setOnline(isOnline) {
        if (isOnline) {
            this.connectionInd.textContent = "ONLINE";
            this.connectionInd.className = "px-3 py-1 rounded bg-green-500/10 text-green-500 text-xs font-bold border border-green-500/20";
        } else {
            this.connectionInd.textContent = "OFFLINE";
            this.connectionInd.className = "px-3 py-1 rounded bg-red-500/10 text-red-500 text-xs font-bold border border-red-500/20";
        }
    }

    async fetchActiveOrders() {
        try {
            const res = await fetch('/api/pedidos-ativos');
            const data = await res.json();
            this.grid.innerHTML = ''; // Limpa grid (safe reload)
            this.orders.clear();
            data.forEach(p => this.addOrUpdateTicket(p));
        } catch (e) {
            console.error("Erro ao buscar pedidos:", e);
        }
    }

    addOrUpdateTicket(pedido, isNew = false) {
        // Normaliza ID
        const id = pedido.id || pedido.id_pedido;
        
        if (this.orders.has(id)) return; // Já existe

        const clone = this.template.content.cloneNode(true);
        const cardContainer = clone.querySelector('.ticket-card');
        cardContainer.id = `ticket-${id}`;
        
        // Popula Dados Básicos
        clone.querySelector('.mesa-num').textContent = pedido.mesa;
        clone.querySelector('.ticket-id').textContent = `#${id}`;
        
        // Renderiza Itens com foco em legibilidade
        const list = clone.querySelector('.item-list');
        pedido.itens.forEach(item => {
            const li = document.createElement('li');
            li.className = "flex justify-between items-start border-b border-gray-800 pb-2 last:border-0";
            
            // Lógica para Observações (Destaque amarelo)
            const obsHtml = item.observacao 
                ? `<div class="text-yellow-500 text-xs italic mt-0.5 font-bold">⚠️ ${item.observacao}</div>` 
                : '';

            li.innerHTML = `
                <div class="flex-1 pr-2">
                    <span class="text-lg font-semibold text-gray-100 leading-tight block">${item.nome_produto || item.nome}</span>
                    ${obsHtml}
                </div>
                <span class="bg-gray-800 text-white font-mono text-xl px-3 py-1 rounded font-bold">
                    ${item.quantidade || item.qtd}
                </span>
            `;
            list.appendChild(li);
        });

        this.grid.appendChild(clone);
        
        // Salva referencia e inicia timer individual
        const ticketElement = document.getElementById(`ticket-${id}`);
        
        this.orders.set(id, {
            element: ticketElement,
            startTime: new Date(pedido.created_at || new Date()).getTime(),
            status: pedido.status
        });

        this.applyStatusVisuals(id, pedido.status, pedido.mesa);

        if (isNew) {
            // Toca som (opcional) e flash visual
            ticketElement.classList.add('ring-2', 'ring-yellow-400');
            setTimeout(() => ticketElement.classList.remove('ring-2', 'ring-yellow-400'), 2000);
        }
    }

    updateTicketStatus(dados) {
        const id = dados.id;
        if (!this.orders.has(id)) return;

        const order = this.orders.get(id);
        order.status = dados.status;
        this.applyStatusVisuals(id, dados.status, dados.mesa);
    }

    applyStatusVisuals(id, status, mesa) {
        const order = this.orders.get(id);
        const el = order.element;
        const btn = el.querySelector('.action-btn');
        const badge = el.querySelector('.status-badge');

        // Reset classes
        el.style.borderColor = '';
        btn.onclick = null;

        if (status === 'pendente') {
            el.style.borderColor = 'var(--status-new-border)';
            badge.textContent = "AGUARDANDO";
            badge.className = "status-badge text-[10px] uppercase font-bold tracking-wider mt-1 text-gray-400";
            
            btn.textContent = "INICIAR PREPARO";
            btn.className = "action-btn w-full py-4 bg-gray-700 hover:bg-gray-600 text-white font-bold transition-colors";
            btn.onclick = () => this.changeStatus(id, 'preparo', mesa);
        } 
        else if (status === 'preparo') {
            el.style.borderColor = 'var(--status-cooking-border)';
            badge.textContent = "EM PREPARO >>";
            badge.className = "status-badge text-[10px] uppercase font-bold tracking-wider mt-1 text-blue-400 animate-pulse";
            
            btn.textContent = "✔ FINALIZAR";
            btn.className = "action-btn w-full py-4 bg-green-600 hover:bg-green-500 text-white font-bold shadow-lg transition-colors";
            btn.onclick = () => this.changeStatus(id, 'pronto', mesa);
        }
    }

    async changeStatus(id, newStatus, mesa) {
        // Optimistic UI Update (Atualiza visualmente antes da API responder)
        this.updateTicketStatus({ id, status: newStatus, mesa });

        try {
            await fetch(`/api/atualizar-status/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            });
            
            this.socket.emit('atualizar_status_pedido', { id, status: newStatus, mesa });
        } catch (e) {
            console.error("Falha ao atualizar status", e);
            alert("Erro de conexão. Tente novamente.");
            // Revert UI if needed (not implemented for simplicity)
        }
    }

    removeTicket(id) {
        if (!this.orders.has(id)) return;
        
        const el = this.orders.get(id).element;
        el.style.transform = 'scale(0.9) translateY(20px)';
        el.style.opacity = '0';
        
        setTimeout(() => {
            el.remove();
            this.orders.delete(id);
        }, 300);
    }

    startGlobalClock() {
        // Atualiza o relógio do topo apenas uma vez por minuto (menos processamento)
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }, 1000);

        // Loop otimizado para os Tickets (requestAnimationFrame é mais suave que setInterval)
        const updateTimers = () => {
            const now = Date.now(); // Timestamp atual em ms

            this.orders.forEach((data, id) => {
                // data.startTime agora deve vir do backend como Timestamp (inteiro)
                const diffMs = now - data.startTime; 
                const totalSeconds = Math.floor(diffMs / 1000);

                // Proteção contra tempos negativos (relógios dessincronizados)
                if (totalSeconds < 0) return;

                const timerEl = data.element.querySelector('.timer');
                
                // Lógica de Formatação Inteligente
                let timeString;
                const hours = Math.floor(totalSeconds / 3600);
                const mins = Math.floor((totalSeconds % 3600) / 60);
                const secs = totalSeconds % 60;

                if (hours > 0) {
                    // Formato HH:MM se passar de 1 hora (ex: 01:15)
                    timeString = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}h`;
                } else {
                    // Formato MM:SS padrão (ex: 45:30)
                    timeString = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }

                // Atualiza o DOM apenas se o texto mudou (Performance)
                if (timerEl.textContent !== timeString) {
                    timerEl.textContent = timeString;
                }

                // Gestão de Cores por Níveis de Atraso (SLA)
                // Nível 1: Atenção (> 10 min) - Amarelo
                // Nível 2: Crítico (> 20 min) - Vermelho Sólido
                // Nível 3: Caos (> 30 min) - Vermelho Pulsante (Sem mexer na opacidade do texto)
                
                timerEl.className = "timer text-xl font-bold kds-font-mono transition-colors duration-300"; // Reset base

                if (totalSeconds > 1800) { // > 30 min
                    timerEl.classList.add('text-red-500', 'animate-pulse-color'); // Nova animação customizada
                    data.element.style.borderColor = 'var(--status-late-border)';
                } else if (totalSeconds > 1200) { // > 20 min
                    timerEl.classList.add('text-red-500');
                    data.element.style.borderColor = 'var(--status-late-border)';
                } else if (totalSeconds > 600) { // > 10 min
                    timerEl.classList.add('text-yellow-400');
                } else {
                    timerEl.classList.add('text-gray-300');
                }
            });

            requestAnimationFrame(updateTimers); // Loop sincronizado com o refresh rate do monitor (60fps)
        };

        requestAnimationFrame(updateTimers);
    }
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.kds = new KDSManager();
});