<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Heart, ChevronLeft, RefreshCw, MapPin } from 'lucide-vue-next';
import { BottomNav } from '@/components/feed';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { getEcho } from '@/echo';

interface MatchUser {
    id: number;
    display_name: string;
    profile_picture: string | null;
}

interface ProximityData {
    match: MatchUser | null;
    /** Legacy: proximity to campus base (kept for compatibility) */
    location_percentage: number | null;
    /** Legacy: both user and match within 1m of campus base */
    in_proximity: boolean;
    /** Proximity between user and match (0–100) based on distance between their current locations */
    match_proximity_percentage: number | null;
    /** Approximate distance between user and match in meters */
    distance_to_match_m: number | null;
    /** True when user and match are within ~10 meters of each other */
    is_nearby_10m: boolean;
    campus: string | null;
}

const getCsrfToken = useCsrfToken();
const data = ref<ProximityData | null>(null);
const loading = ref(true);
const CALCULATING_MIN_MS = 2200;
const calculating = ref(true);
const resetting = ref(false);
const showProximityBanner = ref(false);
const locationUpdating = ref(false);
const locationError = ref<string | null>(null);
const tryingAgain = ref(false);
const geoPermission = ref<'granted' | 'denied' | 'prompt' | 'unsupported' | 'unknown'>('unknown');
const confirmed = ref(false);
const watchId = ref<number | null>(null);
let proximityChannelLeave: (() => void) | null = null;

const showLocationDeniedBanner = computed(() => geoPermission.value === 'denied');

async function fetchData() {
    loading.value = true;
    try {
        const res = await fetch('/api/proximity-match', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) {
            data.value = null;
            return;
        }
        const json = await res.json();
        if (json.ai_debug) {
            // Surface AI selection details in dev tools so we can verify OpenAI is working
            // eslint-disable-next-line no-console
            console.info('FindYourMatch AI debug:', json.ai_debug);
        }
        data.value = {
            match: json.match ?? null,
            location_percentage: json.location_percentage ?? null,
            in_proximity: !!json.in_proximity,
            match_proximity_percentage: json.match_proximity_percentage ?? null,
            distance_to_match_m: json.distance_to_match_m ?? null,
            is_nearby_10m: !!json.is_nearby_10m,
            campus: json.campus ?? null,
        };
        if (data.value?.in_proximity) showProximityBanner.value = true;
    } finally {
        loading.value = false;
    }
}

async function refreshProximityOnly() {
    try {
        const res = await fetch('/api/proximity-match', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) return;
        const json = await res.json();
        data.value = {
            match: json.match ?? data.value?.match ?? null,
            location_percentage: json.location_percentage ?? data.value?.location_percentage ?? null,
            in_proximity: !!(json.in_proximity ?? data.value?.in_proximity),
            match_proximity_percentage: json.match_proximity_percentage ?? data.value?.match_proximity_percentage ?? null,
            distance_to_match_m: json.distance_to_match_m ?? data.value?.distance_to_match_m ?? null,
            is_nearby_10m: !!(json.is_nearby_10m ?? data.value?.is_nearby_10m),
            campus: json.campus ?? data.value?.campus ?? null,
        };
    } catch {
        // ignore transient errors for background refresh
    }
}

async function runCalculation() {
    calculating.value = true;
    const start = Date.now();
    await fetchData();
    const elapsed = Date.now() - start;
    const remaining = Math.max(0, CALCULATING_MIN_MS - elapsed);
    await new Promise((r) => setTimeout(r, remaining));
    calculating.value = false;
}

async function resetMatch() {
    if (resetting.value || !data.value?.match) return;
    resetting.value = true;
    try {
        const res = await fetch('/api/proximity-match/reset', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
        });
        if (res.ok) await runCalculation();
    } finally {
        resetting.value = false;
    }
}

function goBack() {
    router.visit('/browse');
}

async function checkGeolocationPermission() {
    if (!('geolocation' in navigator)) {
        geoPermission.value = 'unsupported';
        return;
    }
    // Permissions API is not available on all browsers
    try {
        const anyNav = navigator as any;
        if (anyNav.permissions?.query) {
            const status = await anyNav.permissions.query({ name: 'geolocation' });
            geoPermission.value = status?.state ?? 'unknown';
            status.onchange = () => {
                geoPermission.value = status?.state ?? 'unknown';
            };
        } else {
            geoPermission.value = 'unknown';
        }
    } catch {
        geoPermission.value = 'unknown';
    }
}

function updateLocationForMatch() {
    if (!navigator.geolocation) {
        locationError.value = 'Location is not supported by your device.';
        geoPermission.value = 'unsupported';
        return;
    }
    locationUpdating.value = true;
    locationError.value = null;
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            try {
                geoPermission.value = 'granted';
                const res = await fetch('/api/account/location', {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                    }),
                });
                if (res.ok) await fetchData();
                else locationError.value = 'Could not update location.';
            } catch (e) {
                locationError.value = 'Could not update location.';
            } finally {
                locationUpdating.value = false;
            }
        },
        () => {
            locationError.value = 'Location permission denied or unavailable.';
            locationUpdating.value = false;
            geoPermission.value = 'denied';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}

async function tryAgain() {
    if (tryingAgain.value) return;
    tryingAgain.value = true;
    locationError.value = null;
    if (navigator.geolocation) {
        try {
            const pos = await new Promise<GeolocationPosition>((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                });
            });
            geoPermission.value = 'granted';
            await fetch('/api/account/location', {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                }),
            });
        } catch {
            locationError.value = 'Location permission denied or unavailable.';
            geoPermission.value = 'denied';
        }
    } else {
        geoPermission.value = 'unsupported';
    }
    try {
        await fetch('/api/proximity-match/reset', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
        });
    } catch {
        // ignore
    }
    await runCalculation();
    tryingAgain.value = false;
}

function startRealtimeLocationWatch() {
    if (!('geolocation' in navigator)) {
        geoPermission.value = 'unsupported';
        return;
    }
    if (watchId.value !== null) {
        return;
    }
    const id = navigator.geolocation.watchPosition(
        async (pos) => {
            try {
                geoPermission.value = 'granted';
                await fetch('/api/account/location', {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                    }),
                });
                await refreshProximityOnly();
            } catch {
                // ignore; will try again on next position update
            }
        },
        (err) => {
            if (err.code === err.PERMISSION_DENIED) {
                geoPermission.value = 'denied';
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 2000 }
    );
    watchId.value = id;
}

function subscribeToProximityUpdates() {
    const page = usePage();
    const userId = (page.props as any).auth?.user?.id as number | undefined;
    const Echo = getEcho();
    if (!Echo || typeof userId !== 'number') return;

    const channel = Echo.private(`user.${userId}`);
    channel.listen('.MatchProximityUpdated', (e: any) => {
        // eslint-disable-next-line no-console
        console.info('MatchProximityUpdated', e);
        if (!data.value?.match || data.value.match.id !== e.match_user_id) {
            // Ignore updates for different matches (or if match not loaded yet)
            return;
        }
        if (!data.value) return;
        data.value = {
            ...data.value,
            match_proximity_percentage: e.proximity_percentage ?? data.value.match_proximity_percentage,
            distance_to_match_m: typeof e.distance_m === 'number' ? e.distance_m : data.value.distance_to_match_m,
            is_nearby_10m: !!e.is_nearby_10m,
        };
    });
    proximityChannelLeave = () => {
        Echo.leave(`user.${userId}`);
        proximityChannelLeave = null;
    };
}

onMounted(() => {
    void checkGeolocationPermission();
    updateLocationForMatch();
    startRealtimeLocationWatch();
    subscribeToProximityUpdates();
    void runCalculation();
});

onBeforeUnmount(() => {
    if (watchId.value !== null && 'geolocation' in navigator) {
        navigator.geolocation.clearWatch(watchId.value);
        watchId.value = null;
    }
    if (proximityChannelLeave) {
        proximityChannelLeave();
        proximityChannelLeave = null;
    }
});
</script>

<template>
    <Head title="Find Your Match - NEMSU Match" />
    <div class="min-h-screen find-your-match-page pb-24 relative overflow-hidden">
        <!-- Animated background: gradient + soft motion (no cards, plain layout) -->
        <div class="find-match-bg absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-bg-soft absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-bg-animate absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-mesh absolute inset-0 pointer-events-none opacity-50" aria-hidden="true" />

        <!-- Header: aligned with Leaderboard/Browse -->
        <header class="sticky top-0 z-30 find-match-header">
            <div class="find-match-container flex items-center justify-between gap-3 py-3.5">
                <button
                    type="button"
                    class="p-2.5 -ml-2 rounded-xl hover:bg-white/70 active:bg-white/50 transition-all duration-200"
                    aria-label="Back"
                    @click="goBack"
                >
                    <ChevronLeft class="w-6 h-6 text-gray-800" />
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="find-match-header-icon w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-200/50">
                        <Heart class="w-5 h-5 text-white" fill="currentColor" />
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 tracking-tight">Find your match</h1>
                </div>
                <div class="w-10" />
            </div>
        </header>

        <!-- Banners: refined, consistent with app cards -->
        <div class="find-match-container space-y-2.5 pt-2">
            <Transition name="banner">
                <div
                    v-if="showProximityBanner && data?.in_proximity && !calculating"
                    class="find-match-banner-proximity rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-3.5 shadow-lg shadow-blue-900/20 flex items-center justify-between gap-3"
                >
                    <span class="find-match-banner-proximity-text font-semibold text-sm flex items-center gap-2">
                        <MapPin class="w-4 h-4 shrink-0" />
                        You're in the same place!
                    </span>
                    <button type="button" class="find-match-banner-proximity-dismiss shrink-0 p-1.5 rounded-full transition-colors" aria-label="Dismiss" @click="showProximityBanner = false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </Transition>
            <Transition name="banner">
                <div
                    v-if="showLocationDeniedBanner"
                    class="find-match-banner-location rounded-2xl px-4 py-3.5 shadow-sm flex items-center justify-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-sm find-match-banner-location-title">Location is turned off</p>
                        <p class="text-xs find-match-banner-location-desc mt-0.5">Enable location to see distance and proximity.</p>
                    </div>
                    <button
                        type="button"
                        class="find-match-banner-location-btn shrink-0 px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors disabled:opacity-60"
                        :disabled="locationUpdating"
                        @click="updateLocationForMatch"
                    >
                        {{ locationUpdating ? '…' : 'Enable' }}
                    </button>
                </div>
            </Transition>
        </div>

        <main class="relative z-10 w-full find-match-container flex flex-col items-center pt-4 pb-6">
            <!-- Progressive calculating state: central heart + concentric glowing arcs -->
            <Transition name="state" mode="out-in">
                <section
                    v-if="calculating"
                    key="calculating"
                    class="find-match-loading-section"
                >
                    <div class="find-match-loading-center">
                        <!-- Concentric arcs (SVG), rotating -->
                        <svg
                            class="find-match-arcs"
                            viewBox="0 0 200 200"
                            aria-hidden="true"
                        >
                            <defs>
                                <filter id="arc-glow" x="-50%" y="-50%" width="200%" height="200%">
                                    <feGaussianBlur in="SourceGraphic" stdDeviation="1" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <g class="find-match-arcs-group">
                                <circle v-for="(r, i) in [42, 58, 74, 90, 106]" :key="i" class="find-match-arc" :class="`find-match-arc-${i + 1}`" fill="none" :r="r" cx="100" cy="100" />
                            </g>
                        </svg>
                        <!-- Central heart with glow -->
                        <div class="find-match-loading-heart-wrap">
                            <Heart class="find-match-loading-heart w-16 h-16 sm:w-20 sm:h-20" stroke-width="1.5" fill="none" stroke="currentColor" />
                        </div>
                    </div>
                    <p class="find-match-loading-text find-match-loading-title">
                        Calculating and matching using AI...
                    </p>
                    <p class="find-match-loading-sub">Checking within a 10m radius</p>
                </section>

                <!-- No match: try again – same anchor as loading (centered, heart + content) -->
                <section
                    v-else-if="!data?.match"
                    key="no-match"
                    class="find-match-empty-section"
                >
                    <div class="find-match-loading-center find-match-empty-hero">
                        <div class="find-match-empty-heart-circle" />
                        <div class="find-match-loading-heart-wrap find-match-empty-heart-wrap find-match-empty-heart-pulse">
                            <Heart
                                class="find-match-loading-heart find-match-empty-heart-size text-blue-600"
                                fill="currentColor"
                                stroke="currentColor"
                                stroke-width="1.5"
                            />
                        </div>
                    </div>
                   
                    <p class="find-match-loading-title find-match-empty-desc find-match-stagger" style="--stagger: 1">
                        {{ data?.campus
                            ? "We couldn't find someone on your campus right now. Try again to get a new match."
                            : "Set your campus in your profile to find an AI match on your campus." }}
                    </p>
                    <div class="find-match-empty-actions find-match-stagger" style="--stagger: 2">
                        <button
                            v-if="data?.campus"
                            type="button"
                            class="find-match-btn-ghost px-6 py-3 rounded-xl text-xs font-medium active:scale-[0.98] transition-all duration-300 disabled:opacity-70"
                            :disabled="tryingAgain"
                            @click="tryAgain"
                        >
                            {{ tryingAgain ? '…' : 'Try again' }}
                        </button>
                    </div>
                    <p v-if="locationError" class="mt-4 find-match-warning text-[11px] find-match-stagger" style="--stagger: 3">{{ locationError }}</p>
                </section>

                <!-- Match found: professional color hierarchy on dark background -->
                <section v-else key="match" class="find-match-section find-match-section-dark flex flex-col items-center w-full space-y-5 text-center">
                    <p class="find-match-label">AI match</p>
                    <p class="find-match-subtitle">AI found your match · How close you are</p>

                    <div v-if="data.match_proximity_percentage != null" class="space-y-3">
                        <div class="match-heart-meter">
                            <div class="match-heart-echo-wrap">
                                <div class="match-heart-echo match-heart-echo-1" aria-hidden="true" />
                                <div class="match-heart-echo match-heart-echo-2" aria-hidden="true" />
                                <div class="match-heart-echo match-heart-echo-3" aria-hidden="true" />
                            </div>
                            <div class="match-heart-circle">
                                <div class="match-heart-inner">
                                    <Heart
                                        class="match-heart-icon"
                                        fill="currentColor"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <span class="match-heart-percent">
                                        {{ data.match_proximity_percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-if="data.distance_to_match_m != null" class="find-match-muted">
                            Approx. {{ data.distance_to_match_m }} m away
                        </p>
                    </div>
                    <div v-else class="space-y-2">
                        <p class="find-match-muted">Share location to see distance.</p>
                        <button
                            type="button"
                            class="find-match-btn-ghost inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[11px] font-medium active:scale-[0.98] transition-all disabled:opacity-60"
                            :disabled="locationUpdating"
                            @click="updateLocationForMatch"
                        >
                            <MapPin class="w-3.5 h-3.5" />
                            {{ locationUpdating ? '…' : 'Share location' }}
                        </button>
                        <p v-if="locationError" class="find-match-warning text-[10px]">{{ locationError }}</p>
                    </div>

                    <div
                        v-if="data.is_nearby_10m"
                        class="find-match-nearby rounded-full px-4 py-1.5 text-[11px] font-medium inline-flex items-center gap-1.5"
                    >
                        <span>Your match is nearby</span>
                    </div>

                    <div v-if="data.is_nearby_10m" class="w-full space-y-3 pt-1">
                        <p class="find-match-muted">Have you found each other?</p>
                        <div class="flex items-center justify-center gap-2 flex-wrap">
                            <button
                                type="button"
                                class="find-match-btn-ghost px-4 py-2 rounded-lg text-[11px] font-medium hover:bg-[var(--fm-surface-hover)] active:scale-[0.98] transition-all disabled:opacity-70"
                                :disabled="confirmed"
                                @click="confirmed = true"
                            >
                                {{ confirmed ? 'Confirmed' : 'Yes' }}
                            </button>
                            <button
                                type="button"
                                class="find-match-btn-ghost-alt px-4 py-2 rounded-lg text-[11px] font-medium active:scale-[0.98] transition-all"
                                @click="confirmed = false"
                            >
                                Not yet
                            </button>
                        </div>
                        <p v-if="confirmed" class="find-match-success text-[10px]">You can reset to find another match anytime.</p>
                    </div>

                    <button
                        type="button"
                        class="find-match-reset flex items-center justify-center gap-1.5 py-2 text-[11px] font-medium transition-colors disabled:opacity-50"
                        :disabled="resetting"
                        @click="resetMatch"
                    >
                        <RefreshCw class="w-4 h-4" :class="resetting ? 'animate-spin' : ''" />
                        {{ resetting ? '…' : 'Reset match' }}
                    </button>
                </section>
            </Transition>
        </main>
    </div>
    <BottomNav />
</template>

<style scoped>
/* Professional color tokens: dark canvas */
.find-your-match-page {
    min-height: 100dvh;
    --fm-primary: #f1f5f9;      /* primary text on dark */
    --fm-secondary: #94a3b8;    /* secondary text */
    --fm-muted: #64748b;       /* tertiary / placeholders */
    --fm-accent: #38bdf8;      /* accent (blue-400) */
    --fm-accent-soft: #7dd3fc; /* soft accent */
    --fm-surface: rgba(255, 255, 255, 0.12);
    --fm-surface-hover: rgba(255, 255, 255, 0.2);
    --fm-border: rgba(255, 255, 255, 0.15);
    --fm-warning: #fbbf24;
    --fm-warning-bg: rgba(251, 191, 36, 0.12);
    --fm-success: #34d399;
}

/* Darker gradient page background */
.find-match-bg {
    /* Slightly lighter (≈10%) dark blue gradient */
    background: linear-gradient(180deg, #264b72 0%, #295886 25%, #17426f 50%, #12355d 75%, #102e53 100%);
}

.find-match-bg-soft {
    background: radial-gradient(ellipse 80% 50% at 50% -10%, rgba(37, 99, 235, 0.45) 0%, transparent 55%),
        radial-gradient(ellipse 60% 40% at 80% 50%, rgba(14, 165, 233, 0.32) 0%, transparent 55%),
        radial-gradient(ellipse 50% 30% at 20% 80%, rgba(59, 130, 246, 0.3) 0%, transparent 58%);
}

/* Animated gradient layer – lightened to match base */
.find-match-bg-animate {
    background: radial-gradient(ellipse 100% 80% at 70% 20%, rgba(59, 130, 246, 0.3) 0%, rgba(37, 99, 235, 0.18) 40%, transparent 60%),
        radial-gradient(ellipse 80% 60% at 20% 70%, rgba(34, 211, 238, 0.26) 0%, rgba(14, 165, 233, 0.12) 40%, transparent 60%);
    animation: find-match-bg-shift 12s ease-in-out infinite;
}

@keyframes find-match-bg-shift {
    0%, 100% {
        opacity: 1;
        transform: scale(1) translate(0, 0);
    }
    33% {
        opacity: 1;
        transform: scale(1.06) translate(2%, -1%);
    }
    66% {
        opacity: 1;
        transform: scale(1.02) translate(-1%, 2%);
    }
}

/* Subtle mesh overlay (darker) */
.find-match-mesh {
    background-image: radial-gradient(at 40% 20%, rgba(59, 130, 246, 0.12) 0%, transparent 50%),
        radial-gradient(at 80% 60%, rgba(34, 211, 238, 0.1) 0%, transparent 45%);
    pointer-events: none;
}

.find-match-header-icon {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.find-match-header-icon:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
}

.find-match-banner-proximity-text {
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}
.find-match-banner-proximity-dismiss {
    color: rgba(255, 255, 255, 0.9);
}
.find-match-banner-proximity-dismiss:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.find-match-header {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 100%);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(203, 213, 225, 0.6);
}

/* Single content width for header, banners, main */
.find-match-container {
    width: 100%;
    max-width: 28rem;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

@media (min-width: 640px) {
    .find-match-container {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
}

/* Layout: sections and spacing */
.find-match-section {
    width: 100%;
    max-width: 28rem;
    margin-left: auto;
    margin-right: auto;
}

.find-match-btn-primary {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* Location-denied banner: professional warning (neutral bg, clear contrast) */
.find-match-banner-location {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.find-match-banner-location-title {
    color: #1e293b;
}
.find-match-banner-location-desc {
    color: #64748b;
}
.find-match-banner-location-btn {
    background: #fff;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.find-match-banner-location-btn:hover {
    background: #f1f5f9;
    color: #334155;
}

/* Dark section: semantic text and controls */
.find-match-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--fm-accent-soft);
}
.find-match-subtitle {
    font-size: 0.75rem;
    color: var(--fm-primary);
    opacity: 0.92;
}
.find-match-muted {
    font-size: 11px;
    color: var(--fm-secondary);
}
.find-match-warning {
    color: var(--fm-warning);
}
.find-match-success {
    color: var(--fm-success);
    font-weight: 500;
}
.find-match-btn-ghost {
    background: var(--fm-surface);
    color: var(--fm-primary);
    border: 1px solid var(--fm-border);
}
.find-match-btn-ghost:hover {
    background: var(--fm-surface-hover);
    border-color: rgba(255, 255, 255, 0.25);
}
.find-match-btn-ghost-alt {
    color: var(--fm-secondary);
}
.find-match-btn-ghost-alt:hover {
    color: var(--fm-primary);
}
.find-match-reset {
    color: var(--fm-secondary);
}
.find-match-reset:hover {
    color: var(--fm-primary);
}
.find-match-nearby {
    background: rgba(52, 211, 153, 0.9);
    color: #022c22;
    font-weight: 600;
    animation: findMatchPulse 2s ease-in-out infinite;
}

@keyframes findMatchPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.95;
        transform: scale(1.01);
    }
}

.find-match-hero-wrap {
    min-height: 8rem;
}

/* Loading: centered, emphasized – heart + concentric glowing arcs */
.find-match-loading-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 45vh;
    padding: 1.5rem 0 2rem;
}

.find-match-loading-center {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 200px;
    height: 200px;
    margin: 0 auto;
}

@media (min-width: 640px) {
    .find-match-loading-center {
        width: 260px;
        height: 260px;
    }
    .find-match-arcs {
        width: 260px !important;
        height: 260px !important;
    }
    .find-match-loading-heart-wrap {
        --heart-size: 5rem;
    }
}

.find-match-arcs {
    position: absolute;
    width: 200px;
    height: 200px;
    inset: 0;
    margin: auto;
}

.find-match-arcs-group {
    transform-origin: 50% 50%;
    animation: arcsRotate 3s linear infinite;
}

.find-match-arc {
    stroke: rgba(255, 255, 255, 0.9);
    stroke-width: 2;
    stroke-linecap: round;
    stroke-dasharray: 55 180;
    filter: url(#arc-glow);
    transform-origin: 100px 100px;
}

.find-match-arc-1 {
    stroke: rgba(255, 255, 255, 0.5);
    stroke-width: 1.5;
    stroke-dasharray: 35 230;
    stroke-dashoffset: 0;
}

.find-match-arc-2 {
    stroke: rgba(255, 255, 255, 0.65);
    stroke-width: 1.8;
    stroke-dasharray: 42 215;
    stroke-dashoffset: 15;
}

.find-match-arc-3 {
    stroke: rgba(255, 255, 255, 0.8);
    stroke-width: 2;
    stroke-dasharray: 48 200;
    stroke-dashoffset: 30;
}

.find-match-arc-4 {
    stroke: rgba(255, 255, 255, 0.9);
    stroke-width: 2.2;
    stroke-dasharray: 55 185;
    stroke-dashoffset: 45;
}

.find-match-arc-5 {
    stroke: rgba(255, 255, 255, 1);
    stroke-width: 2.5;
    stroke-dasharray: 62 170;
    stroke-dashoffset: 60;
}

@keyframes arcsRotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.find-match-loading-heart-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    --heart-size: 4rem;
    width: var(--heart-size);
    height: var(--heart-size);
    margin: auto;
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.9)) drop-shadow(0 0 16px rgba(147, 197, 253, 0.6)) drop-shadow(0 0 24px rgba(59, 130, 246, 0.4));
    animation: heartGlow 2s ease-in-out infinite;
}

.find-match-loading-heart {
    width: var(--heart-size) !important;
    height: var(--heart-size) !important;
    color: white;
    stroke: rgba(255, 255, 255, 0.95);
}

@keyframes heartGlow {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.92;
        transform: scale(1.04);
    }
}

.find-match-loading-title {
    margin-top: 1.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--fm-primary);
    letter-spacing: 0.02em;
    animation: shimmerText 2s ease-in-out infinite;
}

.find-match-loading-sub {
    margin-top: 0.375rem;
    font-size: 0.6875rem;
    color: var(--fm-secondary);
    font-weight: 500;
}

@media (min-width: 640px) {
    .find-match-loading-title {
        font-size: 0.875rem;
    }
    .find-match-loading-sub {
        font-size: 0.75rem;
    }
}

/* No match: anchored to loading UI (same section layout + centered heart) */
.find-match-empty-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 45vh;
    padding: 1.5rem 0 2rem;
    text-align: center;
}

.find-match-empty-hero {
    margin-bottom: 0;
}

.find-match-empty-heart-circle {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 200px;
    height: 200px;
    margin-left: -100px;
    margin-top: -100px;
    border-radius: 50%;
    background: rgba(30, 58, 95, 0.4);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.15);
}

@media (min-width: 640px) {
    .find-match-empty-heart-circle {
        width: 260px;
        height: 260px;
        margin-left: -130px;
        margin-top: -130px;
    }
}

.find-match-empty-heart-wrap {
    filter: none;
    width: 6rem;
    height: 6rem;
}

@media (min-width: 640px) {
    .find-match-empty-heart-wrap {
        width: 8rem;
        height: 8rem;
    }
}

.find-match-empty-heart-wrap .find-match-loading-heart {
    color: var(--fm-accent-soft);
    stroke: var(--fm-accent-soft);
}

.find-match-empty-heart-size {
    width: 5rem !important;
    height: 5rem !important;
}

@media (min-width: 640px) {
    .find-match-empty-heart-size {
        width: 6.5rem !important;
        height: 6.5rem !important;
    }
}

.find-match-empty-heart-pulse {
    animation: heartPulse 1.5s ease-in-out infinite;
}

@keyframes heartPulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.12);
        opacity: 0.92;
    }
}

.find-match-empty-count {
    margin-top: 1.5rem;
    font-size: 3.5rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}

@media (min-width: 640px) {
    .find-match-empty-count {
        font-size: 4rem;
    }
}

.find-match-empty-desc {
    margin-top: 1.25rem;
    font-weight: 500;
    color: var(--fm-primary);
    opacity: 0.92;
    max-width: 20rem;
    font-size: 0.75rem;
    line-height: 1.5;
}

@media (min-width: 640px) {
    .find-match-empty-desc {
        font-size: 0.8125rem;
    }
}

.find-match-empty-actions {
    margin-top: 1.5rem;
}

/* Heart-based proximity meter: larger, emphasized focal point + echoing rings */
.match-heart-meter {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22rem;
    min-height: 22rem;
    animation: fadeInUp 0.5s ease forwards;
}

@media (min-width: 640px) {
    .match-heart-meter {
        min-width: 26rem;
        min-height: 26rem;
    }
}

/* Echoing rings: scale with larger heart */
.match-heart-echo-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.match-heart-echo {
    position: absolute;
    width: 13rem;
    height: 13rem;
    border-radius: 9999px;
    border: 2px solid rgba(56, 189, 248, 0.4);
    animation: heartEchoRing 2.4s ease-out infinite;
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.5;
    left: 50%;
    top: 50%;
}

@media (min-width: 640px) {
    .match-heart-echo {
        width: 15rem;
        height: 15rem;
    }
}

.match-heart-echo-1 {
    animation-delay: 0s;
}

.match-heart-echo-2 {
    animation-delay: 0.8s;
}

.match-heart-echo-3 {
    animation-delay: 1.6s;
}

@keyframes heartEchoRing {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.5;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.85);
        opacity: 0;
    }
}

.match-heart-circle {
    position: relative;
    z-index: 1;
    width: 13rem;
    height: 13rem;
    border-radius: 9999px;
    border: 3px solid rgba(56, 189, 248, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 50% 35%, #f0f9ff 0%, #e0f2fe 45%, #f8fafc 100%);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.8), 0 8px 24px rgba(15, 23, 42, 0.12);
}

@media (min-width: 640px) {
    .match-heart-circle {
        width: 15rem;
        height: 15rem;
        border-width: 4px;
    }
}

.match-heart-inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 8.5rem;
    height: 8.5rem;
    animation: heartMeterPulse 1.8s ease-in-out infinite;
}

@media (min-width: 640px) {
    .match-heart-inner {
        width: 10rem;
        height: 10rem;
    }
}

.match-heart-icon {
    width: 100%;
    height: 100%;
    color: #1d4ed8;
}

.match-heart-percent {
    position: absolute;
    font-size: 1.75rem;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.25), 0 0 1px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.03em;
}

@media (min-width: 640px) {
    .match-heart-percent {
        font-size: 2.15rem;
    }
}

@keyframes heartMeterPulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* State transition (calculating ↔ result) */
.state-enter-active,
.state-leave-active {
    transition: opacity 0.35s ease, transform 0.35s ease;
}

.state-enter-from {
    opacity: 0;
    transform: translateY(12px);
}

.state-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* Banner transition */
.banner-enter-active,
.banner-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.banner-enter-from,
.banner-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

/* Staggered entrance for result content */
.find-match-stagger {
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
    animation-delay: calc(var(--stagger, 0) * 0.1s);
}

.find-match-count {
    opacity: 0;
    animation: fadeInUp 0.5s ease forwards, countPop 0.5s ease forwards;
    animation-delay: calc(var(--stagger, 0) * 0.1s);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes countPop {
    0% {
        transform: translateY(16px) scale(0.85);
    }
    60% {
        transform: translateY(0) scale(1.08);
    }
    100% {
        transform: translateY(0) scale(1);
    }
}

.find-match-heart-found {
    animation: heartFound 0.6s ease forwards;
}

@keyframes heartFound {
    0% {
        transform: scale(0.6);
        opacity: 0;
    }
    60% {
        transform: scale(1.12);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.find-match-card-item {
    animation: fadeInUp 0.4s ease forwards;
}

/* Calculating: ping + spin */
@keyframes ping-slow {
    75%,
    100% {
        transform: scale(1.15);
        opacity: 0;
    }
}

.animate-ping-slow {
    animation: ping-slow 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 4s linear infinite;
}

/* Subtle float on heart while calculating */
.animate-heart-float {
    animation: heartFloat 3s ease-in-out infinite;
}

@keyframes heartFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

.animate-pulse-slow {
    animation: pulseSlow 2.5s ease-in-out infinite;
}

@keyframes pulseSlow {
    0%,
    100% {
        opacity: 0.4;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.05);
    }
}

.animate-shimmer-text {
    animation: shimmerText 2s ease-in-out infinite;
}

@keyframes shimmerText {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.75;
    }
}

/* Scanning bar */
.animate-scan {
    animation: scan 1.8s ease-in-out infinite;
}

@keyframes scan {
    0% {
        transform: translateX(-100%);
    }
    50% {
        transform: translateX(200%);
    }
    100% {
        transform: translateX(-100%);
    }
}

.animate-heart-soft {
    animation: heartSoft 2.5s ease-in-out infinite;
}

@keyframes heartSoft {
    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}
</style>
