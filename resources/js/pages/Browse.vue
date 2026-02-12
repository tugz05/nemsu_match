<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Sparkles, MapPin, GraduationCap, Users, Bell, ShieldCheck } from 'lucide-vue-next';
import { BottomNav, NotificationsDropdown } from '@/components/feed';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { useRealtimeNotifications } from '@/composables/useRealtimeNotifications';

const page = usePage();
const appLogoUrl = page.props.branding?.app_logo_url ?? null;
const subscription = page.props.subscription as { freemium_enabled?: boolean; is_plus?: boolean } | undefined;
const showSubscriptionStatus = subscription?.freemium_enabled === true;
const isPlus = subscription?.is_plus === true;

type MatchUser = {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
    campus: string | null;
    academic_program: string | null;
    year_level: string | null;
    bio: string | null;
    date_of_birth: string | null;
    age: number | null;
    gender: string | null;
    courses?: string[] | unknown;
    research_interests?: string[] | unknown;
    extracurricular_activities?: string[] | unknown;
    academic_goals?: string[] | unknown;
    interests?: string[] | unknown;
    compatibility_score: number;
    common_tags: string[];
};

const getCsrfToken = useCsrfToken();

const matches = ref<MatchUser[]>([]);
const loading = ref(true);
const loadingMore = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);

function ensureStringArray(v: unknown): string[] {
    if (v == null) return [];
    if (Array.isArray(v)) return v.filter((x): x is string => typeof x === 'string');
    if (typeof v === 'string') {
        try {
            const p = JSON.parse(v);
            return Array.isArray(p) ? p.filter((x: unknown): x is string => typeof x === 'string') : [];
        } catch {
            return [];
        }
    }
    return [];
}

function interestsFor(user: MatchUser): string[] {
    const fromInterests = ensureStringArray(user.interests);
    const fromCommon = Array.isArray(user.common_tags) ? user.common_tags : [];
    const combined = fromInterests.length ? fromInterests : fromCommon;
    return combined.slice(0, 5);
}

async function fetchMatches(page = 1) {
    if (page > 1) loadingMore.value = true;
    try {
        const res = await fetch(`/api/matchmaking?page=${page}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const list = (data.data ?? []) as MatchUser[];
        if (page === 1) {
            matches.value = list;
        } else {
            matches.value = [...matches.value, ...list];
        }
        currentPage.value = data.current_page ?? page;
        lastPage.value = data.last_page ?? page;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

function canLoadMore() {
    return currentPage.value < lastPage.value && !loadingMore.value;
}

function goDiscover() {
    router.visit('/like-you');
}

function openProfile(id: number) {
    router.visit(`/profile/${id}`);
}

// Notifications (integrated since Home is disabled)
const showNotificationsDropdown = ref(false);
const unreadNotificationsCount = ref(0);

useRealtimeNotifications(() => {
    unreadNotificationsCount.value += 1;
});

async function fetchUnreadNotificationsCount() {
    try {
        const res = await fetch('/api/notifications/unread-count', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            unreadNotificationsCount.value = data.count ?? 0;
        }
    } catch (e) {
        console.error(e);
    }
}

onMounted(() => {
    void fetchMatches(1);
    fetchUnreadNotificationsCount();
});
</script>

<template>
    <div class="min-h-screen bg-white pb-20">
        <Head title="Browse matches - NEMSU Match" />

        <!-- Top bar -->
        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-9 h-9 rounded-xl overflow-hidden bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-md shrink-0">
                        <img
                            v-if="appLogoUrl"
                            :src="appLogoUrl"
                            alt="App logo"
                            class="w-full h-full object-contain"
                        />
                        <Users v-else class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-bold text-gray-900 truncate">Browse matches</h1>
                        <p class="text-[11px] sm:text-xs text-gray-500">Filtered by your preferences & compatibility</p>
                    </div>
                    <a
                        v-if="showSubscriptionStatus"
                        href="/plus"
                        class="shrink-0 px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors"
                        :class="isPlus ? 'bg-gradient-to-r from-pink-500/15 to-rose-500/15 text-pink-700 border border-pink-200' : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200'"
                    >
                        {{ isPlus ? 'Plus' : 'Free' }}
                    </a>
                </div>
                <NotificationsDropdown
                    v-model:open="showNotificationsDropdown"
                    v-model:unread-count="unreadNotificationsCount"
                    fallback-route="/browse"
                    class="shrink-0"
                >
                    <template #trigger="{ toggle }">
                        <button
                            type="button"
                            @click.stop="toggle"
                            class="relative p-2 hover:bg-gray-100 rounded-full transition-colors"
                            aria-label="Notifications"
                        >
                            <Bell class="w-6 h-6 text-gray-700" />
                            <span
                                v-if="unreadNotificationsCount > 0"
                                class="absolute top-0.5 right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-blue-600 text-white text-xs font-bold rounded-full"
                            >
                                {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
                            </span>
                        </button>
                    </template>
                </NotificationsDropdown>
            </div>
        </div>

        <!-- Matches list -->
        <div class="max-w-2xl mx-auto px-4 py-4 space-y-3">
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                <div class="flex items-start gap-2">
                    <ShieldCheck class="w-4 h-4 text-blue-700 mt-0.5 shrink-0" />
                    <p class="text-xs sm:text-sm text-blue-900 leading-relaxed">
                        Safety tip: If an account looks malicious, open the profile and report it. A badge on the profile means the user is verified.
                    </p>
                </div>
            </div>

            <div v-if="loading && matches.length === 0" class="flex justify-center py-16">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="matches.length === 0" class="py-16 text-center text-gray-500 text-sm">
                <Sparkles class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                <p class="font-medium">No suggested matches yet.</p>
                <p class="text-xs mt-1">Try Discover to start swiping and we’ll refine your preferences.</p>
                <button
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-xs font-semibold shadow-md hover:shadow-lg transition-all"
                    @click="goDiscover"
                >
                    <Sparkles class="w-4 h-4" />
                    Go to Discover
                </button>
            </div>

            <!-- Grid of match cards (similar to dating apps) -->
            <ul v-else class="grid grid-cols-2 gap-3 sm:gap-4">
                <li
                    v-for="(user, idx) in matches"
                    :key="user.id"
                    class="browse-card relative rounded-3xl overflow-hidden bg-gray-200 shadow-sm hover:shadow-xl transition-shadow cursor-pointer aspect-[3/4]"
                    :style="{ animationDelay: `${Math.min(idx, 11) * 70}ms` }"
                    @click="openProfile(user.id)"
                >
                    <!-- Photo -->
                    <div class="absolute inset-0">
                        <img
                            v-if="user.profile_picture"
                            :src="profilePictureSrc(user.profile_picture)"
                            :alt="user.display_name"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-600 to-cyan-500 text-white text-4xl font-bold"
                        >
                            {{ (user.display_name || user.fullname || 'U').charAt(0).toUpperCase() }}
                        </div>

                        <!-- Gradient for text readability -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent" />
                    </div>

                    <!-- Match badge -->
                    <div class="absolute top-2 left-2">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-pink-500/95 text-white px-2.5 py-0.5 text-[11px] font-semibold shadow-md">
                            <Sparkles class="w-3 h-3" />
                            <span>{{ user.compatibility_score }}% match</span>
                        </div>
                    </div>

                    <!-- Info at bottom -->
                    <div class="absolute inset-x-0 bottom-0 p-2 sm:p-2.5 text-white">
                        <p class="font-semibold text-sm sm:text-base leading-tight truncate">
                            {{ user.display_name || user.fullname || 'Student' }}
                            <span v-if="user.age" class="font-normal text-xs sm:text-sm">, {{ user.age }}</span>
                        </p>
                        <p
                            v-if="user.campus || user.academic_program"
                            class="mt-0.5 text-[11px] sm:text-xs text-gray-100/90 flex items-center gap-1 truncate"
                        >
                            <MapPin class="w-3 h-3 shrink-0" />
                            <span v-if="user.campus">{{ user.campus }}</span>
                        </p>
                        <p
                            v-if="user.academic_program"
                            class="mt-0.5 text-[10px] sm:text-[11px] text-gray-100/90 flex items-center gap-1 truncate"
                        >
                            <GraduationCap class="w-3 h-3 shrink-0" />
                            <span>{{ user.academic_program }}</span>
                        </p>
                    </div>
                </li>
            </ul>

            <div v-if="canLoadMore()" class="pt-2 pb-4 flex justify-center">
                <button
                    type="button"
                    class="px-4 py-2 rounded-full border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                    :disabled="loadingMore"
                    @click="fetchMatches(currentPage + 1)"
                >
                    {{ loadingMore ? 'Loading…' : 'Load more matches' }}
                </button>
            </div>
        </div>

        <BottomNav active-tab="home" />
    </div>
</template>

<style scoped>
@keyframes browse-card-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
        filter: blur(1px);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

.browse-card {
    animation: browse-card-in 420ms ease-out both;
    will-change: transform, opacity;
}

.browse-card:hover {
    transform: translateY(-2px) scale(1.01);
}

@media (prefers-reduced-motion: reduce) {
    .browse-card {
        animation: none;
    }
    .browse-card:hover {
        transform: none;
    }
}
</style>

