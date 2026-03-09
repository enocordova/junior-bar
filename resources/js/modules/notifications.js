/* resources/js/modules/notifications.js */

/**
 * KDS Professional Notification System
 * Sound (Web Audio API) | Vibration | Push Notifications | Visual Toasts
 */
class KDSNotifier {
    constructor() {
        this.audioCtx = null;
        this.pushPermission = typeof Notification !== 'undefined' && Notification.permission === 'granted';
        this.soundEnabled = true;
        this.initialized = false;
        this._toastContainer = null;
        this._activeToasts = new Set();
    }

    init() {
        if (this.initialized) return;
        this.initialized = true;
        this._getAudioContext();
        setTimeout(() => this._requestPushPermission(), 2000);
    }

    _getAudioContext() {
        if (!this.audioCtx) {
            this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
        return this.audioCtx;
    }

    // ── SOUND (Web Audio API - sem ficheiros externos) ────────

    _tone(freq, dur, type = 'sine', vol = 0.3, delay = 0) {
        try {
            const ctx = this._getAudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = type;
            osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);

            gain.gain.setValueAtTime(0, ctx.currentTime + delay);
            gain.gain.linearRampToValueAtTime(vol, ctx.currentTime + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + dur);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + dur);
        } catch (e) { /* browser blocked audio */ }
    }

    /** 🔥 Novo pedido - chime ascendente (Cozinha) */
    playNewOrder() {
        if (!this.soundEnabled) return;
        this._tone(880, 0.15, 'sine', 0.4, 0);
        this._tone(1175, 0.3, 'sine', 0.35, 0.15);
    }

    /** ✅ Pedido pronto - acorde de sucesso (Garçom) */
    playOrderReady() {
        if (!this.soundEnabled) return;
        this._tone(523, 0.15, 'sine', 0.3, 0);
        this._tone(659, 0.15, 'sine', 0.3, 0.12);
        this._tone(784, 0.3, 'sine', 0.25, 0.24);
    }

    /** ⚠️ Pedido alterado - alerta rápido (Cozinha) */
    playUpdate() {
        if (!this.soundEnabled) return;
        this._tone(1000, 0.1, 'square', 0.15, 0);
        this._tone(1200, 0.15, 'square', 0.12, 0.12);
    }

    /** 💰 Pagamento recebido - caixa registradora (Gerente) */
    playPayment() {
        if (!this.soundEnabled) return;
        this._tone(1047, 0.08, 'sine', 0.3, 0);
        this._tone(1319, 0.08, 'sine', 0.3, 0.08);
        this._tone(1568, 0.08, 'sine', 0.3, 0.16);
        this._tone(2093, 0.25, 'sine', 0.25, 0.24);
    }

    /** 🔔 Ping subtil */
    playPing() {
        if (!this.soundEnabled) return;
        this._tone(800, 0.15, 'sine', 0.2, 0);
    }

    // ── VIBRAÇÃO ──────────────────────────────────────────────

    vibrate(pattern = 50) {
        if (navigator.vibrate) navigator.vibrate(pattern);
    }

    vibrateTap()    { this.vibrate(30); }
    vibrateAlert()  { this.vibrate([100, 50, 100]); }
    vibrateUrgent() { this.vibrate([200, 100, 200, 100, 200]); }

    // ── PUSH NOTIFICATIONS (Browser API) ──────────────────────

    async _requestPushPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted') {
            this.pushPermission = true;
            return;
        }
        if (Notification.permission !== 'denied') {
            const result = await Notification.requestPermission();
            this.pushPermission = result === 'granted';
        }
    }

    showPush(title, body, options = {}) {
        if (!this.pushPermission || document.visibilityState === 'visible') return;
        try {
            const n = new Notification(title, {
                body,
                icon: '/logo.png',
                badge: '/logo.png',
                tag: options.tag || 'kds',
                renotify: true,
                silent: true,
                ...options
            });
            setTimeout(() => n.close(), 5000);
            n.onclick = () => { window.focus(); n.close(); };
        } catch (e) { /* silent fail */ }
    }

    // ── VISUAL TOASTS ─────────────────────────────────────────

    _getToastContainer() {
        if (this._toastContainer && document.body.contains(this._toastContainer)) {
            return this._toastContainer;
        }

        // Responsive CSS: mobile = top center, desktop = top right
        if (!document.getElementById('kds-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'kds-toast-styles';
            style.textContent = `
                #kds-toast-container {
                    position: fixed;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    pointer-events: none;
                    top: calc(env(safe-area-inset-top, 0px) + 16px);
                    left: 16px;
                    right: 16px;
                    align-items: stretch;
                }
                @media (min-width: 640px) {
                    #kds-toast-container {
                        top: 80px;
                        left: auto;
                        right: 16px;
                        max-width: 380px;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        const c = document.createElement('div');
        c.id = 'kds-toast-container';
        document.body.appendChild(c);
        this._toastContainer = c;
        return c;
    }

    _isMobile() {
        return window.innerWidth < 640;
    }

    showToast(message, type = 'info', duration = 3000) {
        // Deduplicação: ignora se já existe toast com a mesma mensagem
        if (this._activeToasts.has(message)) return;
        this._activeToasts.add(message);

        const icons  = { success: '✓', warning: '⚠', error: '✕', info: 'ℹ' };
        const colors = {
            success: { bg: '#7ed957', text: '#000' },
            warning: { bg: '#efa324', text: '#000' },
            error:   { bg: '#ef4444', text: '#fff' },
            info:    { bg: '#3b82f6', text: '#fff' }
        };

        const cl = colors[type] || colors.info;
        const container = this._getToastContainer();
        const toast = document.createElement('div');
        const mobile = this._isMobile();
        const hideTransform = mobile ? 'translateY(-120%)' : 'translateX(calc(100% + 32px))';

        Object.assign(toast.style, {
            display: 'flex',
            alignItems: 'center',
            gap: '16px',
            padding: '16px 24px',
            borderRadius: '16px',
            boxShadow: '0 20px 40px -8px rgba(0,0,0,0.6)',
            border: `1px solid ${cl.bg}44`,
            background: '#18181b',
            color: '#fff',
            transform: hideTransform,
            transition: 'transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.3s ease',
            pointerEvents: 'auto',
            fontFamily: 'Inter, system-ui, sans-serif'
        });

        toast.innerHTML = `
            <div style="width:40px;height:40px;background:${cl.bg};color:${cl.text};border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;flex-shrink:0">${icons[type] || 'ℹ'}</div>
            <span style="font-weight:700;font-size:15px;line-height:1.4">${message}</span>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.transform = 'translate(0, 0)';
        });

        setTimeout(() => {
            toast.style.transform = hideTransform;
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
                this._activeToasts.delete(message);
            }, 300);
        }, duration);
    }
}

const notifier = new KDSNotifier();

// Auto-init no primeiro toque do utilizador (exigido pelo browser para áudio)
const _initOnce = () => {
    notifier.init();
    ['click', 'touchstart', 'keydown'].forEach(ev =>
        document.removeEventListener(ev, _initOnce)
    );
};
['click', 'touchstart', 'keydown'].forEach(ev => {
    document.addEventListener(ev, _initOnce, { passive: true });
});

export default notifier;
