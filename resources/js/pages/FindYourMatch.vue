<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Heart, ChevronLeft, MapPin } from 'lucide-vue-next';
import { BottomNav } from '@/components/feed';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { getEcho } from '@/echo';

/** Data we need: campus (for scope) and anonymous count of people with romantic interest within 10m */
interface ProximityData {
    campus: string | null;
    likers_within_10m_count: number;
}

const getCsrfToken = useCsrfToken();
const data = ref<ProximityData | null>(null);
const loading = ref(true);
const CALCULATING_MIN_MS = 1800;
const calculating = ref(true);
const locationUpdating = ref(false);
const locationError = ref<string | null>(null);
const geoPermission = ref<'granted' | 'denied' | 'prompt' | 'unsupported' | 'unknown'>('unknown');
const watchId = ref<number | null>(null);
let proximityChannelLeave: (() => void) | null = null;
const alarmJustTriggered = ref(false);

const showLocationDeniedBanner = computed(() => geoPermission.value === 'denied');
const likersWithin10m = computed(() => data.value?.likers_within_10m_count ?? 0);

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
        data.value = {
            campus: json.campus ?? null,
            likers_within_10m_count: typeof json.likers_within_10m_count === 'number' ? json.likers_within_10m_count : 0,
        };
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
        if (data.value) {
            data.value = {
                campus: data.value.campus,
                likers_within_10m_count: typeof json.likers_within_10m_count === 'number' ? json.likers_within_10m_count : data.value.likers_within_10m_count,
            };
        }
    } catch {
        // ignore
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

function goBack() {
    router.visit('/browse');
}

async function checkGeolocationPermission() {
    if (!('geolocation' in navigator)) {
        geoPermission.value = 'unsupported';
        return;
    }
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
                if (res.ok) {
                    await fetchData();
                } else {
                    locationError.value = 'Could not update location.';
                }
            } catch {
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

function startRealtimeLocationWatch() {
    if (!('geolocation' in navigator) || watchId.value !== null) return;
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
                // ignore
            }
        },
        (err) => {
            if (err.code === err.PERMISSION_DENIED) geoPermission.value = 'denied';
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
    channel.listen('.ProximityAlarmTriggered', (e: any) => {
        const count = typeof e?.likers_within_10m_count === 'number' ? e.likers_within_10m_count : 0;
        if (data.value) {
            data.value = { ...data.value, likers_within_10m_count: count };
        }
        if (count > 0) {
            alarmJustTriggered.value = true;
            setTimeout(() => {
                alarmJustTriggered.value = false;
            }, 3000);
        }
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
        <div class="find-match-bg absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-bg-soft absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-bg-animate absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="find-match-mesh absolute inset-0 pointer-events-none opacity-50" aria-hidden="true" />

        <header class="sticky top-0 z-30 find-match-header find-match-header-glass">
            <div class="find-match-container flex items-center justify-between gap-3 py-3.5">
                <button
                    type="button"
                    class="find-match-back-btn p-2.5 -ml-2 rounded-xl transition-all duration-200"
                    aria-label="Back"
                    @click="goBack"
                >
                    <ChevronLeft class="w-6 h-6 text-gray-800" />
                </button>
                <div class="flex items-center gap-3">
                    <div class="find-match-header-icon w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center shadow-lg shadow-rose-200/60">
                        <Heart class="w-5 h-5 text-white" fill="currentColor" stroke="currentColor" stroke-width="2" />
                    </div>
                    <h1 class="text-base font-bold text-gray-900 tracking-tight">Find your match</h1>
                </div>
                <div class="w-10" />
            </div>
        </header>

        <div class="find-match-container space-y-2.5 pt-2">
            <Transition name="banner">
                <div
                    v-if="showLocationDeniedBanner"
                    class="find-match-banner-location rounded-2xl px-4 py-3.5 shadow-sm flex items-center justify-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-sm find-match-banner-location-title">Location is turned off</p>
                        <p class="text-xs find-match-banner-location-desc mt-0.5">Enable location so the alarm can ring when someone is nearby.</p>
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
            <!-- No campus -->
            <section
                v-if="!calculating && !data?.campus"
                class="find-match-concept-empty w-full text-center py-8"
            >
                <Heart class="find-match-concept-empty-icon mx-auto mb-4 text-[var(--fm-accent-soft)]" stroke-width="1.5" fill="none" />
                <h2 class="find-match-concept-title">Proximity alarm is based on your campus</h2>
                <p class="find-match-muted mt-2 max-w-xs mx-auto">
                    Set your campus in your profile. The app will ring when someone who has romantic feelings for you enters a 10m radius — we only show the number, not who they are.
                </p>
            </section>

            <!-- Loading -->
            <section
                v-if="calculating"
                key="calculating"
                class="find-match-loading-section"
            >
                <div class="find-match-loading-center">
                    <svg class="find-match-arcs" viewBox="0 0 200 200" aria-hidden="true">
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
                    <div class="find-match-loading-heart-wrap">
                        <Heart class="find-match-loading-heart w-16 h-16 sm:w-20 sm:h-20" stroke-width="1.5" fill="none" stroke="currentColor" />
                    </div>
                </div>
                <p class="find-match-loading-text find-match-loading-title">Checking who's nearby…</p>
                <p class="find-match-loading-sub">Finding nearby users</p>
                <p class="find-match-loading-sub find-match-loading-sub-2">{{ data?.campus ? 'Based on ' + data.campus : 'Waiting for response…' }}</p>
            </section>

            <!-- Heart only: message + circle with one heart (outline/filled) + count -->
            <section
                v-if="!calculating && data?.campus"
                class="find-match-hero w-full flex flex-col items-center text-center"
            >
                <p class="find-match-hero-message">
                    {{ likersWithin10m === 0
                        ? 'No one within a 10m radius loves you'
                        : likersWithin10m === 1
                            ? 'Someone within a 10m radius loves you'
                            : likersWithin10m + ' people within a 10m radius love you' }}
                </p>
                <div class="find-match-hero-circle" :class="{ 'find-match-hero-circle-active': likersWithin10m > 0 }">
                    <Heart
                        class="find-match-hero-heart"
                        :class="{ 'find-match-hero-heart-filled': likersWithin10m > 0 }"
                        :fill="likersWithin10m > 0 ? 'currentColor' : 'none'"
                        stroke="currentColor"
                        stroke-width="2"
                    />
                </div>
                <p class="find-match-hero-count">{{ likersWithin10m }}</p>
            </section>

            <!-- Footer: campus + update location -->
            <div v-if="!calculating && data?.campus" class="find-match-radar-footer w-full flex flex-col items-center gap-1 mt-2">
                <p class="find-match-muted text-[10px]">Based on {{ data.campus }}</p>
                <button
                    type="button"
                    class="find-match-link-ghost inline-flex items-center gap-1 text-[10px] font-medium text-[var(--fm-accent-soft)] hover:underline disabled:opacity-60"
                    :disabled="locationUpdating"
                    @click="updateLocationForMatch"
                >
                    <MapPin class="w-3 h-3" />
                    {{ locationUpdating ? 'Updating…' : 'Update location' }}
                </button>
                <p v-if="locationError" class="find-match-warning text-[10px]">{{ locationError }}</p>
            </div>
        </main>
    </div>
    <BottomNav />
</template>

<style scoped>
.find-your-match-page {
    min-height: 100dvh;
    --fm-primary: #f8fafc;
    --fm-secondary: #94a3b8;
    --fm-muted: #64748b;
    --fm-accent: #38bdf8;
    --fm-accent-soft: #bae6fd;
    --fm-surface: rgba(255, 255, 255, 0.1);
    --fm-surface-hover: rgba(255, 255, 255, 0.18);
    --fm-border: rgba(255, 255, 255, 0.12);
    --fm-warning: #fbbf24;
    --fm-heart: #f43f5e;
    --fm-heart-soft: rgba(244, 63, 94, 0.15);
}

/* Reference UI: sky blue (top) → magenta/purple (bottom) */
.find-match-bg {
    background: linear-gradient(180deg, #38bdf8 0%, #0ea5e9 18%, #06b6d4 35%, #a78bfa 55%, #c026d3 78%, #a21caf 100%);
}

.find-match-bg-soft {
    background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(255, 255, 255, 0.25) 0%, transparent 50%),
        radial-gradient(ellipse 60% 40% at 80% 60%, rgba(192, 38, 211, 0.2) 0%, transparent 50%),
        radial-gradient(ellipse 50% 35% at 20% 80%, rgba(167, 139, 250, 0.2) 0%, transparent 50%);
}

.find-match-bg-animate {
    background: radial-gradient(ellipse 100% 70% at 60% 20%, rgba(56, 189, 248, 0.2) 0%, transparent 50%),
        radial-gradient(ellipse 70% 50% at 30% 70%, rgba(192, 38, 211, 0.15) 0%, transparent 50%);
    animation: find-match-bg-shift 14s ease-in-out infinite;
}

@keyframes find-match-bg-shift {
    0%, 100% { opacity: 1; transform: scale(1) translate(0, 0); }
    33% { opacity: 1; transform: scale(1.06) translate(2%, -1%); }
    66% { opacity: 1; transform: scale(1.02) translate(-1%, 2%); }
}

.find-match-mesh {
    background-image: radial-gradient(at 40% 25%, rgba(255, 255, 255, 0.08) 0%, transparent 45%),
        radial-gradient(at 75% 65%, rgba(192, 38, 211, 0.06) 0%, transparent 40%);
}

.find-match-header-glass {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.88) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
}

.find-match-header-icon {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.find-match-header-icon:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(244, 63, 94, 0.35);
}

.find-match-back-btn:hover {
    background: rgba(248, 250, 252, 0.9);
}

.find-match-back-btn:active {
    background: rgba(241, 245, 249, 0.95);
}

.find-match-header {
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.4) inset;
}

/* Hero: message + circle + one heart (outline/filled) + count */
.find-match-hero {
    padding: 1.5rem 0 1.25rem;
    animation: fadeInUp 0.45s ease forwards;
}

.find-match-hero-message {
    font-size: 1.125rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    line-height: 1.35;
    max-width: 20rem;
    margin: 0 auto 1.5rem;
    padding: 0 0.5rem;
}

@media (min-width: 640px) {
    .find-match-hero-message { font-size: 1.25rem; }
}

.find-match-hero-circle {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3),
        0 0 40px rgba(255, 255, 255, 0.2),
        inset 0 0 30px rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08);
    margin: 0 auto;
    transition: border-color 0.35s ease, box-shadow 0.35s ease;
}

.find-match-hero-circle-active {
    border-color: rgba(255, 255, 255, 0.95);
    box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.3),
        0 0 50px rgba(244, 63, 94, 0.25),
        inset 0 0 30px rgba(255, 255, 255, 0.08);
}

.find-match-hero-heart {
    width: 80px;
    height: 80px;
    color: rgba(255, 255, 255, 0.95);
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.15));
    transition: color 0.3s ease, filter 0.3s ease;
}

.find-match-hero-heart-filled {
    color: #f43f5e;
    filter: drop-shadow(0 0 16px rgba(244, 63, 94, 0.5)) drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
}

.find-match-hero-count {
    margin-top: 1.25rem;
    font-size: 3.5rem;
    font-weight: 800;
    color: #fff;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
    line-height: 1;
    letter-spacing: -0.03em;
}

@media (min-width: 640px) {
    .find-match-hero-circle { width: 220px; height: 220px; }
    .find-match-hero-heart { width: 90px; height: 90px; }
    .find-match-hero-count { font-size: 4rem; }
}

.find-match-container {
    width: 100%;
    max-width: 28rem;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

@media (min-width: 640px) {
    .find-match-container { padding-left: 1.5rem; padding-right: 1.5rem; }
}

.find-match-label {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--fm-accent-soft);
    opacity: 0.95;
}

.find-match-radar-caption {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.find-match-radar-caption-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--fm-heart);
    box-shadow: 0 0 8px var(--fm-heart-soft);
    animation: radar-caption-dot 2s ease-in-out infinite;
}

@keyframes radar-caption-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.85); }
}

.find-match-radar-caption-text {
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--fm-primary);
    opacity: 0.95;
}

.find-match-muted {
    font-size: 0.6875rem;
    color: var(--fm-secondary);
    line-height: 1.45;
}

.find-match-warning {
    color: var(--fm-warning);
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

.find-match-radar-footer {
    animation: fadeInUp 0.4s ease forwards;
}

.find-match-link-ghost {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.35rem 0.5rem;
    border-radius: 0.5rem;
    transition: background 0.2s ease, color 0.2s ease;
}

.find-match-link-ghost:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
    color: var(--fm-primary);
}

.find-match-concept-empty {
    animation: fadeInUp 0.45s ease forwards;
    padding: 2.5rem 1rem;
}

.find-match-concept-empty-icon {
    width: 4.5rem;
    height: 4.5rem;
    color: var(--fm-accent-soft);
    opacity: 0.9;
}

.find-match-concept-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--fm-primary);
    letter-spacing: -0.02em;
    line-height: 1.35;
}

.find-match-alarm-section {
    animation: fadeInUp 0.4s ease forwards;
}

.find-match-alarm-hero .find-match-alarm-count {
    font-size: 2.5rem;
}

.find-match-alarm-box {
    width: 100%;
    max-width: 20rem;
    padding: 1rem 1.25rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    text-align: center;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.find-match-alarm-ring {
    border-color: rgba(244, 63, 94, 0.4);
    box-shadow: 0 0 20px rgba(244, 63, 94, 0.15);
    animation: alarm-pulse 2s ease-in-out infinite;
}

.find-match-alarm-just-triggered {
    border-color: rgba(244, 63, 94, 0.7);
    box-shadow: 0 0 28px rgba(244, 63, 94, 0.35);
    animation: alarm-ring 0.6s ease-out;
}

@keyframes alarm-pulse {
    0%, 100% { box-shadow: 0 0 20px rgba(244, 63, 94, 0.15); }
    50% { box-shadow: 0 0 26px rgba(244, 63, 94, 0.25); }
}

@keyframes alarm-ring {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4); }
    50% { transform: scale(1.03); box-shadow: 0 0 0 12px rgba(244, 63, 94, 0); }
    100% { transform: scale(1); box-shadow: 0 0 28px rgba(244, 63, 94, 0.35); }
}

.find-match-alarm-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.find-match-alarm-count {
    font-size: 2rem;
    font-weight: 800;
    color: var(--fm-primary);
    line-height: 1;
}

.find-match-alarm-label {
    font-size: 0.75rem;
    color: var(--fm-primary);
    opacity: 0.95;
    font-weight: 600;
}

.find-match-alarm-hint {
    font-size: 0.625rem;
    color: var(--fm-secondary);
    margin-top: 0.125rem;
}

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
    .find-match-loading-center { width: 260px; height: 260px; }
    .find-match-arcs { width: 260px !important; height: 260px !important; }
    .find-match-loading-heart-wrap { --heart-size: 5rem; }
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

.find-match-arc-1 { stroke: rgba(255, 255, 255, 0.5); stroke-width: 1.5; stroke-dasharray: 35 230; }
.find-match-arc-2 { stroke: rgba(255, 255, 255, 0.65); stroke-width: 1.8; stroke-dasharray: 42 215; stroke-dashoffset: 15; }
.find-match-arc-3 { stroke: rgba(255, 255, 255, 0.8); stroke-width: 2; stroke-dasharray: 48 200; stroke-dashoffset: 30; }
.find-match-arc-4 { stroke: rgba(255, 255, 255, 0.9); stroke-width: 2.2; stroke-dasharray: 55 185; stroke-dashoffset: 45; }
.find-match-arc-5 { stroke: rgba(255, 255, 255, 1); stroke-width: 2.5; stroke-dasharray: 62 170; stroke-dashoffset: 60; }

@keyframes arcsRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
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
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.9)) drop-shadow(0 0 16px rgba(147, 197, 253, 0.6));
    animation: heartGlow 2s ease-in-out infinite;
}

.find-match-loading-heart {
    width: var(--heart-size) !important;
    height: var(--heart-size) !important;
    color: white;
    stroke: rgba(255, 255, 255, 0.95);
}

@keyframes heartGlow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.92; transform: scale(1.04); }
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

.find-match-loading-sub-2 {
    margin-top: 0.125rem;
    opacity: 0.9;
}

@media (min-width: 640px) {
    .find-match-loading-title { font-size: 0.875rem; }
    .find-match-loading-sub { font-size: 0.75rem; }
}

.find-match-banner-location {
    background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
}

.find-match-banner-location-title { color: #1e293b; }
.find-match-banner-location-desc { color: #64748b; }

.find-match-banner-location-btn {
    background: #fff;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.find-match-banner-location-btn:hover {
    background: #f1f5f9;
    color: #334155;
}

.banner-enter-active,
.banner-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.banner-enter-from,
.banner-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes shimmerText {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.75; }
}

/* Radar: real-time nearby users (hearts). Tap = send match request. */
.find-match-radar-section {
    animation: fadeInUp 0.5s ease forwards;
}

/* Loading in radar area: finding or updating nearby users */
.find-match-radar-loading {
    min-height: 200px;
}

.find-match-radar-loading-center {
    width: 140px;
    height: 140px;
}

.find-match-radar-arcs {
    width: 140px !important;
    height: 140px !important;
}

.find-match-radar-heart-wrap {
    --heart-size: 3rem;
}

.find-match-radar-loading-text {
    margin-top: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--fm-primary);
    animation: shimmerText 2s ease-in-out infinite;
}

.find-match-radar-updating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 260px;
}

.find-match-radar-updating-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--fm-accent-soft);
    animation: radar-updating-dot 1.2s ease-in-out infinite;
}

.find-match-radar-updating-dot:nth-child(2) { animation-delay: 0.15s; }
.find-match-radar-updating-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes radar-updating-dot {
    0%, 100% { opacity: 0.4; transform: scale(0.9); }
    50% { opacity: 1; transform: scale(1.1); }
}

.find-match-radar-updating-label {
    font-size: 0.6875rem;
    color: var(--fm-secondary);
    font-weight: 500;
}

.find-match-radar {
    position: relative;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: linear-gradient(145deg, rgba(15, 23, 42, 0.5) 0%, rgba(30, 41, 59, 0.35) 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.2) inset,
        0 8px 32px rgba(0, 0, 0, 0.25),
        0 0 48px rgba(56, 189, 248, 0.06);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* Shareit-style scanning sweep */
.find-match-radar-sweep {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: conic-gradient(from 0deg, transparent 0deg, rgba(56, 189, 248, 0.18) 25deg, transparent 55deg);
    animation: radar-sweep 4s linear infinite;
    pointer-events: none;
}

@keyframes radar-sweep {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.find-match-radar-rings {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    pointer-events: none;
}

.find-match-radar-ring {
    position: absolute;
    left: 50%;
    top: 50%;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transform: translate(-50%, -50%);
    animation: radar-ring-pulse 3s ease-in-out infinite;
}

.find-match-radar-ring-1 { width: 33%; height: 33%; }
.find-match-radar-ring-2 { width: 66%; height: 66%; animation-delay: 0.35s; }
.find-match-radar-ring-3 { width: 100%; height: 100%; animation-delay: 0.7s; }

@keyframes radar-ring-pulse {
    0%, 100% { opacity: 0.35; }
    50% { opacity: 0.7; }
}

.find-match-radar-center {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.2),
        0 4px 16px rgba(0, 0, 0, 0.3);
    z-index: 2;
    animation: radar-center-pulse 2.5s ease-in-out infinite;
}

@keyframes radar-center-pulse {
    0%, 100% { box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.2), 0 4px 16px rgba(0, 0, 0, 0.3); }
    50% { box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.35), 0 0 20px var(--fm-heart-soft), 0 4px 16px rgba(0, 0, 0, 0.3); }
}

.find-match-radar-center-heart {
    width: 24px;
    height: 24px;
    color: var(--fm-heart);
    filter: drop-shadow(0 0 8px rgba(244, 63, 94, 0.5)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

/* Empty state when no radar data (e.g. share location, or campus has no base) */
.find-match-radar-empty-msg {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-align: center;
    font-size: 0.6875rem;
    color: var(--fm-secondary);
    max-width: 70%;
    pointer-events: none;
}

.find-match-radar-empty-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: var(--fm-accent-soft);
    opacity: 0.9;
}

.find-match-radar-loading-msg {
    color: var(--fm-primary);
    opacity: 0.9;
}

.find-match-radar-hint {
    margin-top: 1rem;
    font-size: 0.6875rem;
    color: var(--fm-secondary);
    line-height: 1.5;
    text-align: center;
    max-width: 18rem;
}

.find-match-radar-blip {
    position: absolute;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.find-match-radar-you {
    min-width: 36px;
    height: 22px;
    padding: 0 8px;
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(6px);
}

.find-match-radar-you-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--fm-primary);
    letter-spacing: 0.02em;
}

.find-match-radar-heart {
    width: 36px;
    height: 36px;
    cursor: pointer;
    border: none;
    background: none;
    padding: 0;
    border-radius: 50%;
    transition: transform 0.2s ease, filter 0.2s ease;
    animation: radar-heart-pulse 2.2s ease-in-out infinite;
}

.find-match-radar-heart:hover:not(:disabled) {
    transform: translate(-50%, -50%) scale(1.18);
    filter: drop-shadow(0 0 12px rgba(244, 63, 94, 0.6));
}

.find-match-radar-heart:active:not(:disabled) {
    transform: translate(-50%, -50%) scale(0.92);
}

.find-match-radar-heart:disabled {
    cursor: default;
}

.find-match-radar-heart-icon {
    width: 100%;
    height: 100%;
    color: var(--fm-heart);
    filter: drop-shadow(0 0 8px rgba(244, 63, 94, 0.45)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
    transition: color 0.2s ease, filter 0.2s ease;
}

.find-match-radar-heart-sent-icon {
    color: var(--fm-muted);
    opacity: 0.85;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

.find-match-radar-heart-loading .find-match-radar-heart-icon {
    animation: radar-heart-pulse 0.6s ease-in-out infinite;
}

@keyframes radar-heart-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}

.find-match-toast {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.08) 100%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: var(--fm-primary);
    max-width: 20rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(8px);
}

.find-match-toast-chat {
    color: var(--fm-accent-soft);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
