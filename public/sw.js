const CACHE_NAME = 'lajanansanm-v1';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/images/logo-icon-sm.png',
    '/images/apple-touch-icon.png',
    '/images/logo-icon.png',
    '/favicon.ico'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Static assets (images, fonts, static public assets)
    if (url.origin === self.location.origin && (
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname === '/manifest.webmanifest' ||
        url.pathname === '/favicon.ico'
    )) {
        event.respondWith(
            caches.match(request).then((cached) => {
                return cached || fetch(request).then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for web pages
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Gestion des notifications Push
self.addEventListener('push', (event) => {
    let payload = {
        title: 'LajanAnsanm',
        body: 'Ou gen yon nouvo notifikasyon',
        icon: '/images/apple-touch-icon.png',
        badge: '/images/logo-icon-sm.png',
        url: '/notifications'
    };

    if (event.data) {
        try {
            payload = Object.assign(payload, event.data.json());
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon || '/images/apple-touch-icon.png',
        badge: payload.badge || '/images/logo-icon-sm.png',
        vibrate: [100, 50, 100],
        data: {
            url: payload.url || '/notifications'
        }
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

// Clic sur une notification push
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

