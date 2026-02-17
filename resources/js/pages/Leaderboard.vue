<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Trophy, ChevronLeft, Crown, Sparkles } from 'lucide-vue-next';
import { BottomNav } from '@/components/feed';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { useCsrfToken } from '@/composables/useCsrfToken';

type LeaderboardEntry = {
    rank: number;
    display_name: string;
    profile_picture: string | null;
    points: number;
};

const getCsrfToken = useCsrfToken();
const period = ref<'day' | 'week' | 'month'>('day');
const list = ref<LeaderboardEntry[]>([]);
const loading = ref(true);

const tabs = [
    { key: 'day' as const, label: 'Today' },
    { key: 'week' as const, label: 'Week' },
    { key: 'month' as const, label: 'Month' },
];

async function fetchLeaderboard() {
    loading.value = true;
    try {
        const res = await fetch(`/api/leaderboard?period=${period.value}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        list.value = (data.data ?? []) as LeaderboardEntry[];
    } finally {
        loading.value = false;
    }
}

watch(period, () => {
    void fetchLeaderboard();
});

onMounted(() => {
    void fetchLeaderboard();
});

function goBack() {
    router.visit('/browse');
}

// Top 3 in podium order: 2nd (left), 1st (center), 3rd (right)
const top3 = ref<LeaderboardEntry[]>([]);
const rest = ref<LeaderboardEntry[]>([]);

watch(list, (entries) => {
    const all = [...entries];
    const first = all.slice(0, 3);
    top3.value = [first[1], first[0], first[2]].filter(Boolean); // 2nd, 1st, 3rd
    rest.value = all.slice(3);
}, { immediate: true });

function crownColor(rank: number) {
    /* White icon on all three crown badges for contrast */
    if (rank <= 3) return 'text-white';
    return 'text-gray-400';
}

function borderColor(rank: number) {
    /* Blue motif: 1st strongest blue, 2nd/3rd cyan-blue */
    if (rank === 1) return 'border-blue-500 ring-4 ring-blue-400/50 shadow-xl shadow-blue-200/50';
    if (rank === 2) return 'border-cyan-400 ring-2 ring-cyan-300/50 shadow-lg shadow-cyan-100';
    if (rank === 3) return 'border-blue-400 ring-2 ring-blue-300/50 shadow-lg shadow-blue-100';
    return 'border-gray-200';
}

function crownBadgeClass(rank: number) {
    if (rank === 1) return 'crown-badge-first';
    if (rank === 2) return 'crown-badge-second';
    if (rank === 3) return 'crown-badge-third';
    return '';
}

function avatarSize(rank: number) {
    if (rank === 1) return 'w-24 h-24 sm:w-28 sm:h-28';
    return 'w-14 h-14 sm:w-16 sm:h-16';
}

/** Width only, for wrapper so avatar keeps a square size and stays circular */
function avatarWidth(rank: number) {
    if (rank === 1) return 'w-24 sm:w-28';
    return 'w-14 sm:w-16';
}

function crownSize(rank: number) {
    if (rank === 1) return 'w-14 h-14 sm:w-16 sm:h-16';
    /* Smaller so crown fits inside silver/bronze badge and isn’t clipped */
    return 'w-5 h-5 sm:w-6 sm:h-6';
}

function podiumOrder(rank: number) {
    if (rank === 1) return 'order-2';
    if (rank === 2) return 'order-1';
    return 'order-3';
}

function rankLabel(rank: number) {
    if (rank === 1) return '1st';
    if (rank === 2) return '2nd';
    if (rank === 3) return '3rd';
    return `${rank}th`;
}
</script>

<template>
    <Head title="Leaderboard - NEMSU Match" />
    <div class="min-h-screen leaderboard-page pb-20 relative overflow-hidden">
        <!-- Animated leaderboard background -->
        <div class="leaderboard-bg absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="leaderboard-bg-animate absolute inset-0 pointer-events-none" aria-hidden="true" />
        <div class="leaderboard-orbs absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div v-for="i in 12" :key="i" class="leaderboard-orb" :style="{ '--orb-i': i, '--orb-x': (i * 11 + 5) % 100, '--orb-y': (i * 13 + 10) % 100, '--orb-size': 180 + (i % 4) * 100, '--orb-dur': 16 + (i % 5) * 3 }" />
        </div>
        <div class="leaderboard-mesh absolute inset-0 pointer-events-none opacity-40" aria-hidden="true" />
        <!-- Confetti -->
        <div class="leaderboard-confetti absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div v-for="i in 20" :key="i" class="confetti" :style="{ '--x': (i * 5) % 100, '--delay': (i % 6) * 0.4 + 's', '--n': i }" />
        </div>

        <!-- Header -->
        <header class="sticky top-0 z-30 leaderboard-header">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="p-2.5 -ml-2 rounded-xl hover:bg-white/60 transition-all duration-200"
                    aria-label="Back to Browse"
                    @click="goBack"
                >
                    <ChevronLeft class="w-6 h-6 text-gray-800" />
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-200/50">
                        <Trophy class="w-5 h-5 text-white" />
                    </div>
                    <h1 class="text-lg font-bold text-gray-900">Leaderboard</h1>
                </div>
                <div class="w-10" />
            </div>
            <!-- Tabs -->
            <div class="max-w-2xl mx-auto px-4 pb-3 flex gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="tab-btn flex-1 py-3 rounded-xl text-sm font-semibold transition-all duration-300"
                    :class="period === tab.key ? 'tab-active' : 'tab-inactive'"
                    @click="period = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>
        </header>

        <main class="relative z-10 max-w-2xl mx-auto px-4 py-6">
            <div v-if="loading && list.length === 0" class="flex justify-center py-24">
                <div class="loader" />
            </div>

            <template v-else>
                <!-- Top 3 podium - 1st emphasized and bigger -->
                <section class="podium-section flex items-end justify-center gap-1 sm:gap-3 mb-8">
                    <div
                        v-for="(entry, idx) in top3"
                        :key="entry.rank"
                        class="podium-card flex flex-col items-center"
                        :class="[podiumOrder(entry.rank), entry.rank === 1 ? 'podium-first' : '']"
                        :style="{ '--stagger': idx }"
                    >
                        <!-- Podium step -->
                        <div
                            class="podium-step podium-step-animate rounded-t-2xl flex flex-col items-center justify-end pb-3 px-2 min-h-[140px] sm:min-h-[160px] overflow-visible"
                            :class="[entry.rank === 1 ? 'podium-step-first pt-6' : 'podium-step-other pt-10', `podium-step-rank-${entry.rank}`]"
                        >
                            <span
                                class="rank-label text-xs font-bold uppercase tracking-wider mb-1"
                                :class="entry.rank === 1 ? 'rank-label-gold' : entry.rank === 2 ? 'rank-label-silver' : 'rank-label-bronze'"
                            >
                                {{ rankLabel(entry.rank) }}
                            </span>
                            <div
                                class="crown-avatar-wrap flex flex-col items-center relative mb-2"
                                :class="avatarWidth(entry.rank)"
                            >
                                <div
                                    class="crown-icon crown-float shrink-0 -mb-4 flex items-center justify-center crown-badge"
                                    :class="crownBadgeClass(entry.rank)"
                                >
                                    <Crown
                                        class="block"
                                        :class="[crownSize(entry.rank), crownColor(entry.rank)]"
                                        :stroke-width="entry.rank === 1 ? 2.5 : 1.75"
                                    />
                                </div>
                                <div
                                    class="avatar-circle relative rounded-full bg-gray-200 overflow-hidden flex items-center justify-center font-bold text-gray-600 transition-transform duration-500 hover:scale-105 shrink-0"
                                    :class="[avatarSize(entry.rank), borderColor(entry.rank)]"
                                >
                                    <img
                                        v-if="entry.profile_picture"
                                        :src="profilePictureSrc(entry.profile_picture)"
                                        :alt="entry.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-2xl sm:text-3xl">{{ (entry.display_name || '?').charAt(0).toUpperCase() }}</span>
                                    <div v-if="entry.rank === 1" class="first-place-glow" aria-hidden="true" />
                                </div>
                            </div>
                            <p class="font-bold text-gray-900 truncate w-full text-center px-1" :class="entry.rank === 1 ? 'text-base sm:text-lg' : 'text-sm'">
                                {{ entry.display_name }}
                            </p>
                            <p class="text-xs font-semibold mt-0.5" :class="entry.rank === 1 ? 'text-blue-600' : 'text-gray-500'">
                                {{ entry.points.toLocaleString() }} pts
                            </p>
                        </div>
                        <div
                            class="w-full rounded-b-xl h-2 sm:h-3"
                            :class="entry.rank === 1 ? 'podium-bar-gold' : entry.rank === 2 ? 'podium-bar-silver' : 'podium-bar-bronze'"
                        />
                    </div>
                </section>

                <!-- List 4–10 -->
                <section class="space-y-3">
                    <div class="flex items-center gap-2 px-1 mb-2">
                        <Sparkles class="w-4 h-4 text-blue-500" />
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Top 10</span>
                    </div>
                    <ul class="space-y-2">
                        <li
                            v-for="(entry, idx) in rest"
                            :key="entry.rank"
                            class="list-card flex items-center gap-4 rounded-2xl py-3.5 px-4"
                            :style="{ '--idx': idx }"
                        >
                            <span class="rank-badge w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0">
                                {{ entry.rank }}
                            </span>
                            <div class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center text-lg font-bold text-gray-500 shrink-0 ring-2 ring-white shadow-inner">
                                <img
                                    v-if="entry.profile_picture"
                                    :src="profilePictureSrc(entry.profile_picture)"
                                    :alt="entry.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ (entry.display_name || '?').charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 truncate">{{ entry.display_name }}</p>
                                <p class="text-xs text-gray-500 font-medium">{{ entry.points.toLocaleString() }} pts</p>
                            </div>
                        </li>
                    </ul>
                </section>

                <div v-if="list.length === 0" class="empty-state py-20 text-center">
                    <Trophy class="w-14 h-14 mx-auto text-gray-300 mb-4" />
                    <p class="text-gray-600 font-medium">No likes in this period yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Likes received show up here.</p>
                </div>
            </template>
        </main>

        <BottomNav active-tab="home" />
    </div>
</template>

<style scoped>
.leaderboard-page {
    background: linear-gradient(180deg, #eff6ff 0%, #e0f2fe 30%, #f0f9ff 60%, #ffffff 100%);
}

.leaderboard-bg {
    background: radial-gradient(ellipse 80% 50% at 50% -10%, rgba(59, 130, 246, 0.22), transparent),
                radial-gradient(ellipse 60% 40% at 80% 50%, rgba(34, 211, 238, 0.15), transparent),
                radial-gradient(ellipse 50% 30% at 20% 80%, rgba(147, 197, 253, 0.18), transparent);
}

/* Animated gradient – blue motif (same as other app pages) */
.leaderboard-bg-animate {
    background: radial-gradient(ellipse 100% 80% at 70% 20%, rgba(59, 130, 246, 0.25) 0%, rgba(147, 197, 253, 0.1) 40%, transparent 60%),
                radial-gradient(ellipse 80% 60% at 20% 70%, rgba(34, 211, 238, 0.22) 0%, rgba(103, 232, 249, 0.08) 40%, transparent 60%),
                radial-gradient(ellipse 70% 70% at 85% 85%, rgba(147, 197, 253, 0.2) 0%, rgba(59, 130, 246, 0.06) 40%, transparent 55%);
    animation: bg-shift 10s ease-in-out infinite;
    opacity: 1;
}
@keyframes bg-shift {
    0%, 100% { opacity: 1; transform: scale(1) translate(0, 0); }
    33% { opacity: 1; transform: scale(1.08) translate(3%, -2%); }
    66% { opacity: 1; transform: scale(1.04) translate(-2%, 2%); }
}

/* Floating orbs – blue / cyan motif */
.leaderboard-orbs { z-index: 0; }
.leaderboard-orb {
    position: absolute;
    left: calc(var(--orb-x) * 1%);
    top: calc(var(--orb-y) * 1%);
    width: calc(var(--orb-size) * 1px);
    height: calc(var(--orb-size) * 1px);
    border-radius: 50%;
    animation: orb-float 20s ease-in-out infinite;
    animation-duration: calc(var(--orb-dur) * 1s);
    animation-delay: calc(var(--orb-i) * -2s);
}
.leaderboard-orb:nth-child(3n) {
    background: radial-gradient(circle, rgba(59, 130, 246, 0.35) 0%, rgba(147, 197, 253, 0.15) 35%, transparent 65%);
}
.leaderboard-orb:nth-child(3n+1) {
    background: radial-gradient(circle, rgba(34, 211, 238, 0.3) 0%, rgba(103, 232, 249, 0.12) 35%, transparent 65%);
}
.leaderboard-orb:nth-child(3n+2) {
    background: radial-gradient(circle, rgba(147, 197, 253, 0.4) 0%, rgba(59, 130, 246, 0.1) 35%, transparent 65%);
}
@keyframes orb-float {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.85; }
    25% { transform: translate(12px, -18px) scale(1.15); opacity: 1; }
    50% { transform: translate(-8px, 12px) scale(0.92); opacity: 0.9; }
    75% { transform: translate(14px, 8px) scale(1.08); opacity: 0.95; }
}

.leaderboard-mesh {
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2393c5fd' fill-opacity='0.11'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.7;
}

.leaderboard-header {
    background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}

.tab-btn { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.tab-btn:active { transform: scale(0.98); }
.tab-active {
    background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}
.tab-inactive {
    background: rgba(255,255,255,0.8);
    color: #64748b;
    border: 1px solid rgba(226, 232, 240, 0.9);
}
.tab-inactive:hover {
    background: rgba(248, 250, 252, 0.95);
    color: #475569;
}

/* Confetti */
.leaderboard-confetti { z-index: 0; }
.confetti {
    position: absolute;
    width: 12px;
    height: 12px;
    border-radius: 4px;
    left: calc(var(--x) * 1%);
    top: -20px;
    background: linear-gradient(135deg, rgb(147 197 253), rgb(103 232 249));
    opacity: 0.7;
    animation: float-down 14s linear infinite;
    animation-delay: var(--delay);
    animation-duration: calc(12s + (var(--n) % 4) * 2s);
}
.confetti:nth-child(3n) { background: linear-gradient(135deg, rgb(147 197 253), rgb(34 211 238)); }
.confetti:nth-child(5n) { background: linear-gradient(135deg, rgb(125 211 252), rgb(103 232 249)); }
.confetti:nth-child(4n) { background: linear-gradient(135deg, rgb(186 230 253), rgb(224 242 254)); }
.confetti:nth-child(7n) { background: linear-gradient(135deg, rgb(165 243 252), rgb(59 130 246)); }

@keyframes float-down {
    0% { transform: translateY(0) rotate(0deg); opacity: 0.4; }
    15% { opacity: 0.6; }
    85% { opacity: 0.3; }
    100% { transform: translateY(100vh) rotate(380deg); opacity: 0; }
}

/* Loader */
.loader {
    width: 44px;
    height: 44px;
    border: 4px solid rgba(59, 130, 246, 0.2);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Podium */
.podium-section { gap: 0.25rem; align-items: flex-end; }
.podium-card { flex: 1; max-width: 140px; overflow: visible; }
.podium-first { flex: 1.2; max-width: 180px; margin-bottom: 0.5rem; }

.podium-step {
    width: 100%;
    background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.98) 100%);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-bottom: none;
    box-shadow: 0 -2px 12px rgba(0,0,0,0.04);
    overflow: visible;
}
/* Continuous subtle animation for top 3 cards */
.podium-step-animate {
    animation: card-float 4s ease-in-out infinite;
}
.podium-step-rank-1 {
    animation: card-float 3.5s ease-in-out infinite, card-glow-first 3.5s ease-in-out infinite;
}
.podium-step-rank-2 {
    animation-delay: 0.3s;
}
.podium-step-rank-3 {
    animation-delay: 0.6s;
}
@keyframes card-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
@keyframes card-glow-first {
    0%, 100% { box-shadow: 0 -4px 24px rgba(59, 130, 246, 0.12), 0 0 0 1px rgba(59, 130, 246, 0.08); }
    50% { box-shadow: 0 -6px 28px rgba(59, 130, 246, 0.18), 0 0 0 1px rgba(59, 130, 246, 0.12); }
}
.podium-step-first {
    min-height: 180px !important;
    background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(239, 246, 255, 0.6) 100%);
    border-color: rgba(59, 130, 246, 0.25);
    box-shadow: 0 -4px 24px rgba(59, 130, 246, 0.12), 0 0 0 1px rgba(59, 130, 246, 0.08);
}
.podium-bar-gold {
    background: linear-gradient(to top, #f59e0b, #fcd34d);
}
.podium-bar-silver {
    background: linear-gradient(to top, #64748b, #cbd5e1);
}
.podium-bar-bronze {
    background: linear-gradient(to top, #92400e, #d6d3d1);
}

.rank-label {
    display: block;
    text-align: center;
}
.rank-label-gold { color: #b45309; }
.rank-label-silver { color: #475569; }
.rank-label-bronze { color: #92400e; }

/* Crown centered via flex */
.crown-avatar-wrap {
    box-sizing: content-box;
}
.crown-icon.crown-float {
    animation: crown-in 0.6s ease-out both;
    animation-delay: calc(var(--stagger) * 0.15s);
}
/* #1 crown: golden badge + glow so it’s clearly emphasized */
/* Gold */
.crown-badge-first {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: linear-gradient(145deg, #fef3c7 0%, #fcd34d 35%, #f59e0b 70%, #d97706 100%);
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.5), 0 0 0 3px rgba(253, 224, 71, 0.4);
}
@media (min-width: 640px) {
    .crown-badge-first {
        width: 4.5rem;
        height: 4.5rem;
    }
}
.crown-badge :deep(svg) {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}
/* Silver: metallic gray */
.crown-badge-second {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    background: linear-gradient(145deg, #f8fafc 0%, #cbd5e1 40%, #94a3b8 70%, #64748b 100%);
    box-shadow: 0 2px 14px rgba(100, 116, 139, 0.45), 0 0 0 2px rgba(226, 232, 240, 0.8);
}
/* Bronze: copper/brown */
.crown-badge-third {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    background: linear-gradient(145deg, #d6d3d1 0%, #b45309 40%, #92400e 70%, #78350f 100%);
    box-shadow: 0 2px 14px rgba(180, 83, 9, 0.45), 0 0 0 2px rgba(253, 186, 116, 0.35);
}
@media (min-width: 640px) {
    .crown-badge-second,
    .crown-badge-third {
        width: 3rem;
        height: 3rem;
    }
}
@keyframes crown-in {
    from { opacity: 0; transform: translateY(6px) scale(0.6); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.avatar-circle { transition: transform 0.3s ease; }

.podium-card {
    animation: podium-in 0.5s ease-out both;
    animation-delay: calc(var(--stagger) * 0.12s);
}
@keyframes podium-in {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.first-place-glow {
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
    animation: pulse-glow 2.5s ease-in-out infinite;
    pointer-events: none;
}
@keyframes pulse-glow {
    0%, 100% { opacity: 0.6; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.15); }
}

/* List cards */
.list-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.98) 100%);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    animation: list-in 0.4s ease-out both;
    animation-delay: calc(0.2s + var(--idx) * 0.06s);
}
.list-card:hover {
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.08);
    border-color: rgba(147, 197, 253, 0.4);
}

.rank-badge {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #475569;
    border: 1px solid rgba(226, 232, 240, 0.9);
}

@keyframes list-in {
    from { opacity: 0; transform: translateX(-12px); }
    to { opacity: 1; transform: translateX(0); }
}

.empty-state { animation: fade-in 0.4s ease-out; }

@media (prefers-reduced-motion: reduce) {
    .leaderboard-bg-animate { animation: none; }
    .leaderboard-orb { animation: none; opacity: 0.5; }
    .confetti { animation: none; opacity: 0.15; }
    .podium-card, .list-card, .crown-icon { animation: none; }
    .podium-step-animate { animation: none; }
    .first-place-glow { animation: none; }
}
</style>
