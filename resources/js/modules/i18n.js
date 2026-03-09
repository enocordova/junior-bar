/* resources/js/modules/i18n.js */

/**
 * Lightweight i18n helpers for KDS frontend.
 * Reads translations from window.KDS_LANG (injected by Blade layout)
 * and currency config from window.AppConfig.
 */

/**
 * Get translated string by key.
 * Falls back to the key itself if not found.
 */
export function __t(key) {
    return window.KDS_LANG?.[key] ?? key;
}

/**
 * Format a numeric value as currency using AppConfig settings.
 * @param {number|string} value
 * @returns {string} e.g. "€ 10,50" or "10.50 Kz"
 */
export function formatMoney(value) {
    const cfg = window.AppConfig || {};
    const sym = cfg.currencySymbol || '€';
    const before = cfg.currencyBefore !== undefined ? cfg.currencyBefore : true;
    const decSep = cfg.decimalSep || ',';

    const num = Number(value).toFixed(2);
    const formatted = decSep === ',' ? num.replace('.', ',') : num;

    return before ? `${sym} ${formatted}` : `${formatted} ${sym}`;
}

// Expose globally for Alpine.js x-text expressions
window.__t = __t;
window.formatMoney = formatMoney;
