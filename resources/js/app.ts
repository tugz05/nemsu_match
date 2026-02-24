import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import { showBrowserNotificationIfAllowed } from './composables/useBrowserNotifications';
import type { RealtimeNotificationPayload } from './composables/useRealtimeNotifications';
import { subscribeToPush } from './composables/usePushSubscription';
import './echo';
import { getEcho } from './echo';
import PermissionPrompt from './components/PermissionPrompt.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h('div', { class: 'relative' }, [
                h(App, props),
                h(PermissionPrompt),
            ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Subscribe to app status changes for automatic navigation (only when Pusher/Echo is configured)
function subscribeToAppStatus() {
    const Echo = getEcho();
    if (!Echo) return;
    try {
        const statusChannel = Echo.channel('app-status');

        // Listen for maintenance mode changes
        statusChannel.listen('.MaintenanceModeChanged', (e: any) => {
            console.log('Maintenance mode changed:', e.maintenance_mode);
            // Reload page to trigger middleware
            router.reload();
        });

        // Listen for pre-registration mode changes
        statusChannel.listen('.PreRegistrationModeChanged', (e: any) => {
            console.log('Pre-registration mode changed:', e.pre_registration_mode);
            // Reload page to trigger middleware
            router.reload();
        });

        console.log('✓ Subscribed to app-status channel');
    } catch (error) {
        console.error('Failed to subscribe to app-status channel:', error);
    }
}

// Store Inertia page props globally for app status listener
router.on('navigate', (event) => {
    (window as any).inertiaPageProps = event.detail.page.props;
});

// Global realtime notifications: subscribe to user channel when authenticated, show browser notification when enabled
let notificationChannelLeave: (() => void) | null = null;
let subscribedUserId: number | null = null;

function setupUserNotificationChannel(pageProps: { auth?: { user?: { id?: number } } } | undefined): void {
    const userId = pageProps?.auth?.user?.id as number | undefined;
    const Echo = getEcho();

    if (!Echo || typeof userId !== 'number') {
        if (notificationChannelLeave) {
            notificationChannelLeave();
            notificationChannelLeave = null;
            subscribedUserId = null;
        }
        return;
    }
    if (subscribedUserId === userId) return;

    if (notificationChannelLeave) {
        notificationChannelLeave();
        notificationChannelLeave = null;
    }
    subscribedUserId = userId;

    const channel = Echo.private(`user.${userId}`);
    channel.listen('.NotificationSent', (e: RealtimeNotificationPayload & { id?: number; type?: string }) => {
        const payload: RealtimeNotificationPayload = {
            id: e.id ?? 0,
            type: e.type ?? 'unknown',
            from_user_id: e.from_user_id ?? 0,
            from_user: e.from_user ?? null,
            notifiable_type: e.notifiable_type ?? 'user',
            notifiable_id: e.notifiable_id ?? null,
            data: (e.data && typeof e.data === 'object') ? e.data : null,
            read_at: e.read_at ?? null,
            created_at: e.created_at ?? new Date().toISOString(),
        };
        window.dispatchEvent(new CustomEvent('realtime-notification', { detail: payload }));
        try {
            showBrowserNotificationIfAllowed(payload, appName);
        } catch (err) {
            console.warn('[Browser notification]', err);
        }
    });
    notificationChannelLeave = () => {
        Echo.leave(`user.${userId}`);
        subscribedUserId = null;
    };
}

// Ensure Web Push is subscribed when user has notifications enabled (so notifications work when browser is closed).
function ensurePushSubscriptionIfEnabled(props: { auth?: { user?: { id?: number } }; vapid_public_key?: string | null } | undefined): void {
    if (typeof window === 'undefined') return;
    if (!props?.auth?.user?.id || !props.vapid_public_key) return;
    if (typeof Notification !== 'undefined' && Notification.permission !== 'granted') return;
    if (localStorage.getItem('browser_notifications_enabled') !== '1') return;
    subscribeToPush(props.vapid_public_key);
}

router.on('navigate', (event) => {
    const props = event.detail.page.props as { auth?: { user?: { id?: number } }; vapid_public_key?: string | null };
    setupUserNotificationChannel(props);
    ensurePushSubscriptionIfEnabled(props);
});

// Run subscription on initial page load (navigate only fires on client-side navigation, not first load)
if (typeof window !== 'undefined') {
    subscribeToAppStatus();

    const win = window as Window & { inertiaPageProps?: { auth?: { user?: { id?: number } }; vapid_public_key?: string | null } };

    // Initial page props: from Inertia (div data-page or script[data-page="app"]). Run as early as possible so we read before Inertia may mutate the DOM.
    function runInitialNotificationSubscription(): void {
        let pageJson: string | null = null;
        const root = document.getElementById('app');
        if (root?.getAttribute('data-page')) pageJson = root.getAttribute('data-page');
        if (!pageJson) {
            const script = document.querySelector('script[data-page="app"][type="application/json"]');
            if (script?.textContent) pageJson = script.textContent;
        }
        if (pageJson) {
            try {
                const page = JSON.parse(pageJson) as { props?: { auth?: { user?: { id?: number } }; vapid_public_key?: string | null } };
                if (page?.props) {
                    win.inertiaPageProps = page.props;
                    setupUserNotificationChannel(page.props);
                    ensurePushSubscriptionIfEnabled(page.props);
                }
            } catch {
                // ignore
            }
        }
        if (!subscribedUserId && win.inertiaPageProps) setupUserNotificationChannel(win.inertiaPageProps);
    }

    // Run immediately (before Inertia app mounts) so data-page is still in the DOM
    runInitialNotificationSubscription();
    // Retry after DOM ready in case initial run was too early
    if (document.readyState !== 'complete') {
        window.addEventListener('load', () => runInitialNotificationSubscription());
    }
    // Retry again after a short delay if still not subscribed (catches late-rendered or different HTML structure)
    setTimeout(() => {
        if (!subscribedUserId && getEcho()) runInitialNotificationSubscription();
    }, 300);
    setTimeout(() => {
        if (!subscribedUserId && getEcho()) runInitialNotificationSubscription();
    }, 800);

    // When user returns to tab, re-ensure subscription (fixes missed subscribe after soft navigation)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible') return;
        if (subscribedUserId || !getEcho()) return;
        if (win.inertiaPageProps?.auth?.user?.id) setupUserNotificationChannel(win.inertiaPageProps);
    });
}
