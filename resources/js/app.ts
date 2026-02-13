import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import { showBrowserNotificationIfAllowed } from './composables/useBrowserNotifications';
import type { RealtimeNotificationPayload } from './composables/useRealtimeNotifications';
import './echo';
import { getEcho } from './echo';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Subscribe to app status changes for automatic navigation
function subscribeToAppStatus() {
    try {
        const statusChannel = window.Echo.channel('app-status');

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

router.on('navigate', (event) => {
    const props = event.detail.page.props as { auth?: { user?: { id?: number } } };
    const userId = props.auth?.user?.id as number | undefined;

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
            from_user_id: e.from_user_id,
            from_user: e.from_user ?? null,
            notifiable_type: e.notifiable_type,
            notifiable_id: e.notifiable_id,
            data: e.data ?? null,
            read_at: e.read_at ?? null,
            created_at: e.created_at,
        };
        window.dispatchEvent(new CustomEvent('realtime-notification', { detail: payload }));
        showBrowserNotificationIfAllowed(payload, appName);
    });
    notificationChannelLeave = () => {
        Echo.leave(`user.${userId}`);
        subscribedUserId = null;
    };
});

// Initialize app status subscription
if (typeof window !== 'undefined') {
    subscribeToAppStatus();
}
