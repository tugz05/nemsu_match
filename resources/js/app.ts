import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import './echo';

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

// Initialize app status subscription
if (typeof window !== 'undefined') {
    subscribeToAppStatus();
}
