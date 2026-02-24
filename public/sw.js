/* Service worker for Web Push: show notification when app is closed or in background; open URL on click. */
self.addEventListener('push', function (event) {
    if (!event.data) return;
    let payload;
    try {
        payload = event.data.json();
    } catch {
        return;
    }
    // When a window is focused, the in-page Pusher handler will show the notification; avoid duplicate.
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            const hasVisible = clientList.some(function (c) { return c.visibilityState === 'visible'; });
            if (hasVisible) return;
            const title = payload.title || 'NEMSU Match';
            const base = self.registration.scope.replace(/\/$/, '');
            const options = {
                body: payload.body || 'You have a new notification',
                icon: payload.icon || base + '/favicon.ico',
                badge: payload.badge || base + '/favicon.ico',
                tag: payload.id ? 'notification-' + payload.id : 'notification',
                data: {
                    url: payload.url || '/notifications',
                    id: payload.id,
                    type: payload.type
                },
                requireInteraction: false
            };
            return self.registration.showNotification(title, options);
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const data = event.notification.data || {};
    let url = data.url || '/notifications';
    if (url.startsWith('/')) {
        const base = self.registration.scope.replace(/\/$/, '');
        url = base + url;
    }
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.indexOf(self.registration.scope) === 0 && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
