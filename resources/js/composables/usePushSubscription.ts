/**
 * Web Push subscription: register SW, subscribe with VAPID, send to backend.
 * Enables notifications when the browser/app is closed (primary approach for iOS PWA and Android).
 */

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Register the service worker and subscribe to push; send subscription to backend.
 * Call when the user has granted notification permission and enabled notifications in app.
 */
export async function subscribeToPush(vapidPublicKey: string | null): Promise<boolean> {
    if (typeof window === 'undefined' || !vapidPublicKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }
    try {
        const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        await reg.update();
        let sub = await reg.pushManager.getSubscription();
        const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);
        if (!sub) {
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey,
            });
        }
        if (!sub) return false;

        const subscription = sub.toJSON();
        const res = await fetch('/api/push-subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: subscription.keys,
            }),
        });
        return res.ok;
    } catch (e) {
        console.warn('[Push] subscribe failed', e);
        return false;
    }
}

/**
 * Unsubscribe from push and remove subscription on the server.
 */
export async function unsubscribeFromPush(): Promise<void> {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return;
    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            await sub.unsubscribe();
            await fetch('/api/push-subscribe', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });
        }
    } catch (e) {
        console.warn('[Push] unsubscribe failed', e);
    }
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

export function isPushSupported(): boolean {
    if (typeof window === 'undefined') return false;
    return 'serviceWorker' in navigator && 'PushManager' in window;
}
