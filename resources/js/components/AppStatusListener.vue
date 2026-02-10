<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUser = page.props.auth?.user as any;

let statusChannel: any = null;

const subscribeToAppStatus = () => {
    try {
        // Subscribe to app-status public channel
        statusChannel = window.Echo.channel('app-status');

        // Listen for maintenance mode changes
        statusChannel.listen('.MaintenanceModeChanged', (e: any) => {
            console.log('Maintenance mode changed:', e.maintenance_mode);

            // Skip if user is admin or superadmin (they can bypass)
            if (currentUser?.is_admin || currentUser?.is_superadmin) {
                console.log('User is admin, bypassing maintenance mode');
                return;
            }

            if (e.maintenance_mode) {
                // Maintenance mode enabled - reload page to trigger middleware redirect
                console.log('Maintenance mode enabled, reloading...');
                router.reload();
            } else {
                // Maintenance mode disabled - reload to restore normal access
                console.log('Maintenance mode disabled, reloading...');
                router.reload();
            }
        });

        // Listen for pre-registration mode changes
        statusChannel.listen('.PreRegistrationModeChanged', (e: any) => {
            console.log('Pre-registration mode changed:', e.pre_registration_mode);

            // Skip if user is authenticated (they can bypass)
            if (currentUser) {
                console.log('User is authenticated, bypassing pre-registration mode');
                return;
            }

            if (e.pre_registration_mode) {
                // Pre-registration mode enabled - reload page for guests
                console.log('Pre-registration mode enabled, reloading...');
                router.reload();
            } else {
                // Pre-registration mode disabled - reload to restore normal access
                console.log('Pre-registration mode disabled, reloading...');
                router.reload();
            }
        });

        console.log('✓ Subscribed to app-status channel');
    } catch (error) {
        console.error('Failed to subscribe to app-status channel:', error);
    }
};

const unsubscribeFromAppStatus = () => {
    if (statusChannel) {
        try {
            window.Echo.leave('app-status');
            statusChannel = null;
            console.log('✓ Unsubscribed from app-status channel');
        } catch (error) {
            console.error('Failed to unsubscribe from app-status channel:', error);
        }
    }
};

onMounted(() => {
    subscribeToAppStatus();
});

onUnmounted(() => {
    unsubscribeFromAppStatus();
});
</script>

<template>
    <!-- This component has no visible UI - it only listens to events -->
</template>
