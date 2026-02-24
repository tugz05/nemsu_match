<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { MapPin, Bell, X } from 'lucide-vue-next';
import { useBrowserNotifications } from '@/composables/useBrowserNotifications';

const STORAGE_KEY = 'nemsu_match_permission_prompt_shown';
const LOCATION_GRANTED_KEY = 'nemsu_match_location_granted';

const page = usePage();
const browserNotif = useBrowserNotifications();

const dismissed = ref(false);
const locationRequesting = ref(false);
const locationGranted = ref(false);
const notificationRequesting = ref(false);
const notificationGranted = ref(false);

const isAuthenticated = computed(() => !!page.props.auth?.user);

/** True if we have a record that the user already allowed location (e.g. in this modal or in Find Your Match). */
function hasLocationGrantedStorage(): boolean {
    if (typeof window === 'undefined') return false;
    try {
        return localStorage.getItem(LOCATION_GRANTED_KEY) === '1';
    } catch {
        return false;
    }
}

/** Only show when user has not already allowed both location and notifications. */
const shouldShow = computed(() => {
    if (!isAuthenticated.value || dismissed.value) return false;
    if (typeof window === 'undefined') return false;
    try {
        if (localStorage.getItem(STORAGE_KEY) === '1') return false;
        const notificationAllowed = browserNotif.permission === 'granted';
        const locationAllowed = hasLocationGrantedStorage();
        if (notificationAllowed && locationAllowed) return false;
        return true;
    } catch {
        return false;
    }
});

function markShown() {
    try {
        localStorage.setItem(STORAGE_KEY, '1');
    } catch {
        // ignore
    }
    dismissed.value = true;
}

function continueAnyway() {
    markShown();
}

async function requestLocation() {
    if (!navigator.geolocation) return;
    locationRequesting.value = true;
    try {
        await new Promise<void>((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                () => {
                    locationGranted.value = true;
                    try {
                        localStorage.setItem(LOCATION_GRANTED_KEY, '1');
                    } catch {
                        // ignore
                    }
                    resolve();
                },
                () => resolve(),
                { timeout: 10000, maximumAge: 0 }
            );
        });
    } finally {
        locationRequesting.value = false;
    }
}

async function requestNotification() {
    notificationRequesting.value = true;
    try {
        const result = await browserNotif.requestPermission();
        if (result === 'granted') notificationGranted.value = true;
    } finally {
        notificationRequesting.value = false;
    }
}

watch(shouldShow, (show) => {
    if (!show) return;
    locationGranted.value = hasLocationGrantedStorage();
    notificationGranted.value = browserNotif.permission === 'granted';
});

onMounted(() => {
    if (isAuthenticated.value && shouldShow.value) {
        locationGranted.value = hasLocationGrantedStorage();
        notificationGranted.value = browserNotif.permission === 'granted';
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="shouldShow"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="permission-prompt-title"
        >
            <div
                class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden"
                @click.stop
            >
                <div class="p-5 pb-4 flex items-center justify-between border-b border-gray-100">
                    <h2 id="permission-prompt-title" class="text-lg font-bold text-gray-900">
                        Use the app at its best
                    </h2>
                    <button
                        type="button"
                        class="p-1.5 rounded-full hover:bg-gray-100 text-gray-500"
                        aria-label="Close"
                        @click="continueAnyway"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-sm text-gray-600">
                        Allow these so you don’t miss matches and messages:
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                <MapPin class="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 text-sm">Location</p>
                                <p class="text-xs text-gray-600 mt-0.5">For Find Your Match and nearby features</p>
                                <button
                                    type="button"
                                    :disabled="locationGranted || locationRequesting"
                                    class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-700 disabled:text-gray-400 disabled:cursor-default"
                                    @click="requestLocation"
                                >
                                    {{ locationGranted ? 'Allowed' : locationRequesting ? 'Asking…' : 'Allow location' }}
                                </button>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                                <Bell class="w-5 h-5 text-amber-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 text-sm">Notifications</p>
                                <p class="text-xs text-gray-600 mt-0.5">For messages, matches, and alerts</p>
                                <button
                                    v-if="browserNotif.isSupported"
                                    type="button"
                                    :disabled="notificationGranted || notificationRequesting || browserNotif.permission === 'granted'"
                                    class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-700 disabled:text-gray-400 disabled:cursor-default"
                                    @click="requestNotification"
                                >
                                    {{ notificationGranted || browserNotif.permission === 'granted' ? 'Allowed' : notificationRequesting ? 'Asking…' : 'Allow notifications' }}
                                </button>
                                <p v-else class="mt-2 text-xs text-gray-500">Not supported in this browser.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button
                        type="button"
                        class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-95 transition-opacity"
                        @click="continueAnyway"
                    >
                        Continue
                    </button>
                    <p class="text-center text-xs text-gray-500 mt-3">You can change these later in Account settings.</p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
