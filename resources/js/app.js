/* resources/js/app.js */
import './bootstrap';
import Alpine from 'alpinejs';
import { io } from 'socket.io-client';

// Módulos
import kdsSystem from './modules/cozinha';
import garcomPro from './modules/garcom';
import gerenteApp from './modules/gerente';
import notifier from './modules/notifications';
import './modules/i18n';

// ═══════════════════════════════════════
// SOCKET.IO — Instância única partilhada
// ═══════════════════════════════════════
const SOCKET_URL = window.AppConfig?.socketUrl || '/';

const socket = io(SOCKET_URL, {
    path: '/socket.io/',
    transports: ['websocket', 'polling'],
    reconnection: true,
    reconnectionAttempts: Infinity,
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
    timeout: 20000,
});

// Reconexão automática ao voltar do background (mobile/standalone PWA)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        if (!socket.connected) {
            socket.disconnect();
            socket.connect();
        }
    }
});

// ═══════════════════════════════════════
// HEALTH CHECK — Verifica Laravel + Node
// ═══════════════════════════════════════
const healthCheck = {
    laravelOk: true,

    async ping() {
        try {
            const r = await fetch('/api/health', { method: 'GET', cache: 'no-store', signal: AbortSignal.timeout(5000) });
            this.laravelOk = r.ok;
        } catch { this.laravelOk = false; }
    },

    start() {
        this.ping();
        setInterval(() => this.ping(), 30000);

        // Ping imediato ao voltar do background
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') this.ping();
        });
    }
};

healthCheck.start();

// ═══════════════════════════════════════
// EXPOSIÇÃO GLOBAL
// ═══════════════════════════════════════
window.io = io;
window.socket = socket;
window.KDSNotifier = notifier;
window.healthCheck = healthCheck;

// ═══════════════════════════════════════
// ALPINE.JS — Registar todos os módulos
// ═══════════════════════════════════════
window.Alpine = Alpine;
Alpine.data('kdsSystem', kdsSystem);
Alpine.data('garcomPro', garcomPro);
Alpine.data('gerenteApp', gerenteApp);
Alpine.start();
