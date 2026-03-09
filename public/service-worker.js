/* public/service-worker.js */

// 1. Importar a biblioteca Workbox (CDN do Google)
importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.4.1/workbox-sw.js');

if (workbox) {

    // --- CONFIGURAÇÕES GERAIS ---
    // Força o novo Service Worker a assumir o controle imediatamente após a instalação
    workbox.core.skipWaiting();
    workbox.core.clientsClaim();

    // --- ESTRATÉGIAS DE CACHE ---

    // 1. CSS, JS e Imagens do Sistema (Stale-While-Revalidate)
    // EXPLICAÇÃO: Serve o arquivo do cache (rápido) e verifica em segundo plano se tem novo.
    // Se tiver novo, ele atualiza o cache para o próximo acesso.
    workbox.routing.registerRoute(
        ({request}) => request.destination === 'style' ||
                       request.destination === 'script' ||
                       request.destination === 'image',
        new workbox.strategies.StaleWhileRevalidate({
            cacheName: 'kds-assets-cache',
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 100, // Guarda no máximo 100 arquivos
                    maxAgeSeconds: 30 * 24 * 60 * 60, // Expira em 30 dias
                }),
            ],
        })
    );

    // 2. Fontes do Google (Cache First)
    // EXPLICAÇÃO: Fontes nunca mudam. Baixa uma vez e guarda para sempre.
    workbox.routing.registerRoute(
        ({url}) => url.origin === 'https://fonts.googleapis.com' ||
                   url.origin === 'https://fonts.gstatic.com',
        new workbox.strategies.CacheFirst({
            cacheName: 'google-fonts',
            plugins: [
                new workbox.cacheableResponse.CacheableResponsePlugin({
                    statuses: [0, 200],
                }),
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 30,
                    maxAgeSeconds: 365 * 24 * 60 * 60, // 1 ano
                }),
            ],
        })
    );

    // 3. API do Laravel / Backend (Network First)
    // EXPLICAÇÃO: Dados de pedidos PRECISAM ser frescos.
    // Tenta a rede primeiro. Se cair a net, tenta pegar o último cache (opcional, mas seguro).
    workbox.routing.registerRoute(
        ({url}) => url.pathname.startsWith('/api/'),
        new workbox.strategies.NetworkFirst({
            cacheName: 'kds-api-cache',
            networkTimeoutSeconds: 3, // Se demorar 3s, desiste e tenta cache
            plugins: [
                new workbox.expiration.ExpirationPlugin({
                    maxEntries: 50,
                    maxAgeSeconds: 5 * 60, // Cache dura só 5 minutos para evitar dados velhos
                }),
            ],
        })
    );

}