import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Enregistrement du Service Worker (PWA)
if ('serviceWorker' in navigator && window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Service worker registration skipped or failed silently
        });
    });
}
