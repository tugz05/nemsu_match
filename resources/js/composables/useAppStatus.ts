import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Listen to app status changes (maintenance mode, pre-registration mode)
 * and automatically navigate users to appropriate pages
 */
export function useAppStatus() {
    let statusChannel: any = null;

    const subscribeToAppStatus = () => {
        // Subscribe to app-status channel
        statusChannel = window.Echo.channel('app-status');

        // Listen for maintenance mode changes
        statusChannel.listen('.MaintenanceModeChanged', (e: any) => {
            console.log('Maintenance mode changed:', e.maintenance_mode);

            if (e.maintenance_mode) {
                // Maintenance mode enabled - reload page to trigger middleware
                router.reload({
                    onSuccess: () => {
                        console.log('Navigated to maintenance page');
                    },
                });
            } else {
                // Maintenance mode disabled - reload to restore normal access
                router.reload({
                    onSuccess: () => {
                        console.log('Maintenance mode disabled, app restored');
                    },
                });
            }
        });

        // Listen for pre-registration mode changes
        statusChannel.listen('.PreRegistrationModeChanged', (e: any) => {
            console.log('Pre-registration mode changed:', e.pre_registration_mode);

            if (e.pre_registration_mode) {
                // Pre-registration mode enabled - reload for guests
                router.reload({
                    onSuccess: () => {
                        console.log('Navigated to pre-registration page');
                    },
                });
            } else {
                // Pre-registration mode disabled - reload to restore normal access
                router.reload({
                    onSuccess: () => {
                        console.log('Pre-registration mode disabled, app restored');
                    },
                });
            }
        });

        console.log('Subscribed to app-status channel');
    };

    const unsubscribeFromAppStatus = () => {
        if (statusChannel) {
            window.Echo.leave('app-status');
            statusChannel = null;
            console.log('Unsubscribed from app-status channel');
        }
    };

    onMounted(() => {
        subscribeToAppStatus();
    });

    onUnmounted(() => {
        unsubscribeFromAppStatus();
    });

    return {
        subscribeToAppStatus,
        unsubscribeFromAppStatus,
    };
}
