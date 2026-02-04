/**
 * Laravel Echo + Pusher bootstrap for real-time notifications.
 * Only initializes when Pusher key is configured.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Echo?: InstanceType<typeof Echo>;
        Pusher?: typeof Pusher;
    }
}

const key = import.meta.env.VITE_PUSHER_APP_KEY as string | undefined;
const cluster = (import.meta.env.VITE_PUSHER_APP_CLUSTER as string) || 'mt1';

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

if (key) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        },
    });
}

export function getEcho(): typeof window.Echo {
    return window.Echo;
}
