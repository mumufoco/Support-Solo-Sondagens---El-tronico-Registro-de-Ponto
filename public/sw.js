/**
 * Service Worker for Push Notifications
 * Sistema de Ponto Eletrônico
 */

const CACHE_NAME = 'ponto-eletronico-v1';
const urlsToCache = [
    '/',
    '/assets/css/app.css',
    '/assets/js/chat.js',
    '/assets/img/icon-192.png',
    '/assets/img/badge-72.png'
];

// Install event - cache resources
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching app shell');
                return cache.addAll(urlsToCache);
            })
            .catch((error) => {
                console.error('[SW] Cache error:', error);
            })
    );

    // Force activation
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );

    // Take control immediately
    return self.clients.claim();
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');

    let data = {
        title: 'Nova Notificação',
        body: 'Você tem uma nova mensagem.',
        icon: '/assets/img/icon-192.png',
        badge: '/assets/img/badge-72.png',
        data: {}
    };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/assets/img/icon-192.png',
        badge: data.badge || '/assets/img/badge-72.png',
        vibrate: [200, 100, 200],
        tag: data.data?.type || 'general',
        requireInteraction: false,
        data: data.data || {},
        actions: []
    };

    // Add action buttons based on notification type
    if (data.data?.type === 'chat_message') {
        options.actions = [
            {
                action: 'open',
                title: 'Ver Mensagem',
                icon: '/assets/img/action-view.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: '/assets/img/action-close.png'
            }
        ];
    }

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window open
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open new window if none found
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('[SW] Notification closed:', event.notification.tag);
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clone response for cache
                const responseToCache = response.clone();

                caches.open(CACHE_NAME)
                    .then((cache) => {
                        cache.put(event.request, responseToCache);
                    });

                return response;
            })
            .catch(() => {
                // Fallback to cache
                return caches.match(event.request)
                    .then((response) => {
                        return response || new Response('Offline', {
                            status: 503,
                            statusText: 'Service Unavailable',
                            headers: new Headers({
                                'Content-Type': 'text/plain'
                            })
                        });
                    });
            })
    );
});

// Message event - handle messages from clients
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Background sync event (future feature)
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-messages') {
        event.waitUntil(syncMessages());
    }
});

/**
 * Sync queued messages (offline support)
 */
async function syncMessages() {
    try {
        // Get queued messages from IndexedDB
        const db = await openDatabase();
        const messages = await getQueuedMessages(db);

        // Send each message
        for (const message of messages) {
            await fetch('/chat/upload', {
                method: 'POST',
                body: JSON.stringify(message),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            // Remove from queue on success
            await removeQueuedMessage(db, message.id);
        }

        console.log('[SW] Messages synced successfully');
    } catch (error) {
        console.error('[SW] Sync error:', error);
        throw error; // Retry sync
    }
}

/**
 * Open IndexedDB database
 */
function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ChatDB', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('messageQueue')) {
                db.createObjectStore('messageQueue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

/**
 * Get queued messages
 */
function getQueuedMessages(db) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['messageQueue'], 'readonly');
        const objectStore = transaction.objectStore('messageQueue');
        const request = objectStore.getAll();

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
    });
}

/**
 * Remove queued message
 */
function removeQueuedMessage(db, messageId) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['messageQueue'], 'readwrite');
        const objectStore = transaction.objectStore('messageQueue');
        const request = objectStore.delete(messageId);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}
