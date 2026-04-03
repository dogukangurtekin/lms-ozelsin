self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};

    event.waitUntil(self.registration.showNotification(data.title || 'Yeni bildirim', {
        body: data.body || '',
        icon: data.icon || 'assets/pwa-icon-192.png',
        badge: data.badge || 'assets/pwa-icon-192.png',
        data: {
            url: data.url || self.registration.scope,
        },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || self.registration.scope;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }

            return undefined;
        })
    );
});
