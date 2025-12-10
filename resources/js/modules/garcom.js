import { io } from "socket.io-client";

// 1. CONFIGURAÇÃO DINÂMICA (Pega o IP definido no layout kds.blade.php)
const SOCKET_URL = window.AppConfig?.socketUrl || "http://localhost:3000";

const socket = io(SOCKET_URL, {
    transports: ['websocket'], // Força transporte mais rápido
    reconnection: true,
    reconnectionAttempts: 5
});

// 2. ESTADO LOCAL DA APLICAÇÃO
let state = { 
    mesa: null, 
    itens: [] 
};

// Cache de Elementos DOM (Melhora performance no telemóvel)
const ui = {
    menuContainer: document.getElementById('menu-container'),
    mesaInfo: document.getElementById('mesa-info'),
    mesaDisplay: document.getElementById('selected-mesa-display'),
    btnEnviar: document.getElementById('btn-enviar'),
    counter: document.getElementById('contador-itens'),
    mesaBtns: document.querySelectorAll('.mesa-btn')
};

// 3. MONITORAMENTO DE CONEXÃO (UX)
socket.on('connect', () => {
    console.log("🟢 Conectado ao Servidor de Pedidos");
    // Habilita o botão se houver itens
    if(state.itens.length > 0) ui.btnEnviar.classList.remove('opacity-50', 'cursor-not-allowed');
});

socket.on('disconnect', () => {
    console.log("🔴 Sem conexão");
    // Desabilita visualmente para evitar frustração
    ui.btnEnviar.classList.add('opacity-50', 'cursor-not-allowed');
});

// 4. FUNÇÕES GLOBAIS (Acessíveis pelo HTML)

window.selectMesa = (numero, btn) => {
    state.mesa = numero;
    
    // Reset visual de todos os botões de mesa
    ui.mesaBtns.forEach(b => b.className = "mesa-btn h-12 rounded-lg font-bold text-lg border border-gray-200 text-gray-600 transition-all active:scale-95");
    
    // Destaque para a mesa selecionada
    btn.className = "mesa-btn h-12 rounded-lg font-bold text-lg bg-black text-white shadow-lg transform scale-110 transition-all";
    
    // Libera o Menu
    ui.menuContainer.classList.remove('opacity-50', 'pointer-events-none');
    ui.menuContainer.classList.add('fade-in-up'); // Animação CSS
    
    ui.mesaInfo.classList.remove('hidden');
    ui.mesaDisplay.innerText = numero;
    
    // Scroll suave até o menu (UX para telas pequenas)
    setTimeout(() => {
        ui.menuContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
};

window.addItem = (nome) => {
    // Adiciona ao estado
    state.itens.push({ nome, qtd: 1 });
    
    // Feedback Tátil (se suportado)
    if(navigator.vibrate) navigator.vibrate(15);
    
    // Atualiza botão flutuante
    updateFab();
    
    // Feedback Visual rápido no botão clicado (opcional, mas agradável)
    // Mostra um pequeno toast temporário ou animação no contador
};

function updateFab() {
    ui.counter.innerText = state.itens.length;
    
    // Lógica de Exibição do Botão Flutuante
    if (state.itens.length > 0) {
        ui.btnEnviar.classList.remove('translate-y-24'); // Aparece
    } else {
        ui.btnEnviar.classList.add('translate-y-24'); // Esconde
    }
}

// 5. ENVIO DE PEDIDO (Lógica Crítica Refatorada)
window.enviarPedido = async () => {
    // Validações
    if (!state.mesa || state.itens.length === 0) return;
    
    if (!socket.connected) {
        alert("⚠️ Sem conexão com a Cozinha!\nVerifique se o servidor está rodando ou seu Wi-Fi.");
        return;
    }

    // Trava UI
    const originalText = ui.btnEnviar.innerText;
    ui.btnEnviar.innerText = "ENVIANDO...";
    ui.btnEnviar.disabled = true;
    
    try {
        // Envia para o Laravel (Banco de Dados)
        const res = await fetch('/api/criar-pedido', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ mesa: state.mesa, itens: state.itens })
        });
        
        const data = await res.json();
        
        if(data.status === 'sucesso') {
            // SUCESSO: Emite Socket (Instantâneo)
            socket.emit('novo_pedido', data.pedido);
            
            // Feedback
            if(navigator.vibrate) navigator.vibrate([50, 50, 50]);
            showToast(`Pedido Mesa ${state.mesa} Enviado!`);
            
            // Reseta a Aplicação (Sem Reload)
            resetState();
        } else {
            throw new Error(data.msg || 'Erro desconhecido');
        }

    } catch(e) {
        console.error(e);
        alert("❌ Falha ao enviar: " + e.message);
        ui.btnEnviar.innerText = "TENTAR NOVAMENTE";
    } finally {
        ui.btnEnviar.disabled = false;
        if(ui.btnEnviar.innerText !== "TENTAR NOVAMENTE") {
            ui.btnEnviar.innerText = originalText;
        }
    }
};

// 6. FUNÇÕES UTILITÁRIAS

function resetState() {
    state.mesa = null;
    state.itens = [];
    
    // Reset UI Elementos
    ui.mesaBtns.forEach(b => b.className = "mesa-btn h-12 rounded-lg font-bold text-lg border border-gray-200 text-gray-600 transition-all");
    
    ui.menuContainer.classList.add('opacity-50', 'pointer-events-none');
    ui.menuContainer.classList.remove('fade-in-up');
    
    ui.mesaInfo.classList.add('hidden');
    
    ui.btnEnviar.classList.add('translate-y-24');
    ui.counter.innerText = "0";
    
    // Volta ao topo
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showToast(msg) {
    const toast = document.createElement('div');
    // Estilo Tailwind para o Toast
    toast.className = "fixed top-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center gap-3 animate-bounce";
    toast.innerHTML = `
        <div class="bg-green-500 rounded-full p-1"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>
        <span class="font-bold tracking-wide">${msg}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Remove após 3 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// 7. NOTIFICAÇÕES (Opcional: Garçom saber que está pronto)
socket.on('pedido_pronto', (dados) => {
    // Toca som ou vibra mais forte
    if(navigator.vibrate) navigator.vibrate([200, 100, 200]);
    
    const alertBox = document.createElement('div');
    alertBox.className = "fixed bottom-24 left-4 right-4 bg-green-500 text-white p-4 rounded-xl shadow-lg z-50 flex justify-between items-center animate-pulse";
    alertBox.innerHTML = `
        <div>
            <p class="text-xs font-bold opacity-80 uppercase">Cozinha Informa:</p>
            <p class="text-lg font-black">Mesa ${dados.mesa} PRONTO!</p>
        </div>
        <button onclick="this.parentElement.remove()" class="bg-white/20 p-2 rounded-full font-bold">OK</button>
    `;
    document.body.appendChild(alertBox);
    
    // Auto remove em 10s
    setTimeout(() => alertBox.remove(), 10000);
});