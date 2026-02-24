/**
 * Web Push subscription: register SW, subscribe with VAPID, send to backend.
 * Enables notifications when the browser/app is closed (primary approach for iOS PWA and Android).
 */

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/** Web Push requires HTTPS (or localhost). Avoids "Registration failed - push service error" on HTTP. */
function isSecureContextForPush(): boolean {
    if (typeof window === 'undefined') return false;
    return window.isSecureContext === true;
}

/** VAPID public key must decode to 65 bytes (uncompressed P-256). Returns null if invalid. */
function parseVapidPublicKey(vapidPublicKey: string): Uint8Array | null {
    try {
        const padding = '='.repeat((4 - (vapidPublicKey.length % 4)) % 4);
        const base64 = (vapidPublicKey + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        if (outputArray.length !== 65) return null;
        return outputArray;
    } catch {
        return null;
    }
}

/**
 * Register the service worker and subscribe to push; send subscription to backend.
 * Call when the user has granted notification permission and enabled notifications in app.
 * Skips push when not in a secure context (e.g. HTTP) or when VAPID key is invalid, so in-tab notifications still work.
 */
export async function subscribeToPush(vapidPublicKey: string | null): Promise<boolean> {
    if (typeof window === 'undefined' || !vapidPublicKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }
    if (!isSecureContextForPush()) return false;

    const applicationServerKey = parseVapidPublicKey(vapidPublicKey);
    if (!applicationServerKey) return false;

    try {
        const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        await reg.update();
        let sub = await reg.pushManager.getSubscription();
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
        const err = e as { name?: string; message?: string };
        const isPushServiceError =
            err?.name === 'AbortError' ||
            (typeof err?.message === 'string' && /push service|registration failed/i.test(err.message));
        if (!isPushServiceError) console.warn('[Push] subscribe failed', e);
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

export function isPushSupported(): boolean {
    if (typeof window === 'undefined') return false;
    return window.isSecureContext === true && 'serviceWorker' in navigator && 'PushManager' in window;
}
