<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Heart, X, Sparkles, Smile, BookOpen, ChevronLeft } from 'lucide-vue-next';
import { BottomNav } from '@/components/feed';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';

interface MatchUser {
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
}

interface MatchedUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
}

/** User as returned from GET /api/matchmaking/likes */
interface LikedUser {
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
    liked_at: string;
    matched: boolean;
}

type DiscoverTab = 'discover' | 'heart' | 'study_buddy' | 'smile';

const props = defineProps<{
    user?: { id: number; display_name?: string; profile_picture?: string | null };
}>();

const getCsrfToken = useCsrfToken();
const profiles = ref<MatchUser[]>([]);
const currentIndex = ref(0);
const loading = ref(true);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const followLoading = ref<number | null>(null);

// Tabs: discover (swipe) | heart | study_buddy | smile (lists of liked users)
const activeTab = ref<DiscoverTab>('discover');
const likedLists = ref<Record<DiscoverTab, { data: LikedUser[]; page: number; lastPage: number; total: number; loading: boolean }>>({
    discover: { data: [], page: 1, lastPage: 1, total: 0, loading: false },
    heart: { data: [], page: 1, lastPage: 1, total: 0, loading: false },
    study_buddy: { data: [], page: 1, lastPage: 1, total: 0, loading: false },
    smile: { data: [], page: 1, lastPage: 1, total: 0, loading: false },
});

// Match modal (when mutual like)
const matchModalUser = ref<MatchedUser | null>(null);

// Button feedback (popup + button pop animation)
type ActionIntent = 'ignored' | 'friend' | 'study_buddy' | 'dating';
const actionPopupVisible = ref(false);
const actionPopup = ref<{ intent: ActionIntent; text: string } | null>(null);
const lastClickedIntent = ref<ActionIntent | null>(null);
let actionPopupTimer: number | undefined;
let buttonPopTimer: number | undefined;

// Engaging action-row animations (stagger intro + subtle idle float)
const actionsIntroActive = ref(false);
let actionsIntroTimer: number | undefined;

// Swipe state
const swipeStartX = ref(0);
const swipeCurrentX = ref(0);
const isDragging = ref(false);
const exitDirection = ref<'left' | 'right' | null>(null);
const isExiting = ref(false);
/** When true, next card appears at rest with no transition (avoids bounce-back). */
const skipNextTransition = ref(false);
const SWIPE_THRESHOLD = 80;
const ROTATE_FACTOR = 0.15;
const EXIT_DURATION_MS = 380;

const currentProfile = computed(() => profiles.value[currentIndex.value] ?? null);
const nextProfileInStack = computed(() => profiles.value[currentIndex.value + 1] ?? null);

const cardStyle = computed(() => {
    if (isExiting.value && exitDirection.value) {
        const x = exitDirection.value === 'left' ? -1200 : 1200;
        const rotate = exitDirection.value === 'left' ? -18 : 18;
        return {
            transform: `translateX(${x}px) rotate(${rotate}deg) scale(0.88)`,
            opacity: 0,
            transition: `transform ${EXIT_DURATION_MS}ms cubic-bezier(0.34, 1.2, 0.64, 1), opacity ${EXIT_DURATION_MS * 0.6}ms ease-out`,
        };
    }
    if (!isDragging.value) {
        const noTransition = skipNextTransition.value;
        return {
            transform: 'translateX(0) rotate(0deg) scale(1)',
            opacity: 1,
            transition: noTransition ? 'none' : 'transform 0.25s cubic-bezier(0.34, 1.2, 0.64, 1), opacity 0.2s ease-out',
        };
    }
    const x = swipeCurrentX.value - swipeStartX.value;
    const opacity = Math.max(0.4, 1 - Math.abs(x) / 400);
    return {
        transform: `translateX(${x}px) rotate(${x * ROTATE_FACTOR}deg) scale(${1 - Math.abs(x) / 2000})`,
        opacity,
        transition: 'none',
    };
});

const showNopeOverlay = computed(() => isDragging.value && swipeCurrentX.value - swipeStartX.value < -SWIPE_THRESHOLD);
const showLikeOverlay = computed(() => isDragging.value && swipeCurrentX.value - swipeStartX.value > SWIPE_THRESHOLD);

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

const interestBadges = computed(() => {
    const p = currentProfile.value;
    if (!p) return [];
    return ensureStringArray(p.interests).slice(0, 6);
});

async function fetchMatches(page: number) {
    const res = await fetch(`/api/matchmaking?page=${page}`, {
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
    });
    if (!res.ok) {
        loading.value = false;
        return;
    }
    const data = await res.json();
    const list = (data.data ?? []).map((u: MatchUser) => ({ ...u }));
    if (page === 1) {
        profiles.value = list;
        currentIndex.value = 0;
    } else {
        profiles.value = [...profiles.value, ...list];
    }
    currentPage.value = data.current_page ?? 1;
    lastPage.value = data.last_page ?? 1;
    total.value = data.total ?? 0;
    loading.value = false;
}

async function loadMore() {
    if (currentPage.value >= lastPage.value || loading.value) return;
    loading.value = true;
    await fetchMatches(currentPage.value + 1);
}

/** Fetch liked users for a list tab (heart = dating, study_buddy, smile = friend). */
async function fetchLikes(tab: DiscoverTab, page = 1) {
    const intent = tab === 'heart' ? 'dating' : tab === 'smile' ? 'friend' : tab;
    if (intent === 'discover') return;
    const list = likedLists.value[tab];
    if (list.loading) return;
    list.loading = true;
    try {
        const res = await fetch(`/api/matchmaking/likes?intent=${intent}&page=${page}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) {
            list.loading = false;
            return;
        }
        const data = await res.json();
        const items = (data.data ?? []) as LikedUser[];
        if (page === 1) {
            list.data = items;
            list.page = 1;
        } else {
            list.data = [...list.data, ...items];
            list.page = page;
        }
        list.lastPage = data.last_page ?? 1;
        list.total = data.total ?? 0;
    } finally {
        list.loading = false;
    }
}

function setTab(tab: DiscoverTab) {
    activeTab.value = tab;
    if (tab !== 'discover') {
        const list = likedLists.value[tab];
        if (list.data.length === 0 && !list.loading) fetchLikes(tab, 1);
    }
}

function nextProfile() {
    if (currentIndex.value < profiles.value.length - 1) {
        currentIndex.value++;
    } else if (currentPage.value < lastPage.value) {
        loadMore();
    } else {
        currentIndex.value = 0;
    }
}

function openChat(userId?: number) {
    const id = userId ?? currentProfile.value?.id;
    if (id) router.visit(`/chat?user=${id}`);
}

/** Submit swipe action to API; if matched, show modal. Does NOT advance index (caller does that after animation). */
async function submitAction(targetUserId: number, intent: 'dating' | 'friend' | 'study_buddy' | 'ignored') {
    if (followLoading.value !== null) return;

    followLoading.value = targetUserId;
    try {
        const res = await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ target_user_id: targetUserId, intent }),
        });
        const data = await res.json().catch(() => ({}));

        if (data.matched && data.other_user) {
            matchModalUser.value = data.other_user;
        }
    } finally {
        followLoading.value = null;
    }
}

function triggerButtonFeedback(intent: ActionIntent) {
    // button pop
    lastClickedIntent.value = intent;
    if (buttonPopTimer) window.clearTimeout(buttonPopTimer);
    buttonPopTimer = window.setTimeout(() => {
        if (lastClickedIntent.value === intent) lastClickedIntent.value = null;
    }, 260);

    // popup
    const text =
        intent === 'ignored'
            ? 'Ignored'
            : intent === 'friend'
              ? 'Friend'
              : intent === 'study_buddy'
                ? 'Study Buddy'
                : 'Liked';

    actionPopup.value = { intent, text };
    actionPopupVisible.value = true;
    if (actionPopupTimer) window.clearTimeout(actionPopupTimer);
    actionPopupTimer = window.setTimeout(() => {
        actionPopupVisible.value = false;
    }, 700);
}

function playActionsIntro() {
    actionsIntroActive.value = true;
    if (actionsIntroTimer) window.clearTimeout(actionsIntroTimer);
    actionsIntroTimer = window.setTimeout(() => {
        actionsIntroActive.value = false;
    }, 520);
}

function handleDating() {
    const p = currentProfile.value;
    if (isExiting.value || !p) return;
    triggerButtonFeedback('dating');
    isExiting.value = true;
    exitDirection.value = 'right';
    const targetId = p.id;
    setTimeout(() => {
        nextProfile();
        resetSwipeState();
        void submitAction(targetId, 'dating');
    }, EXIT_DURATION_MS);
}

function handleFriend() {
    const p = currentProfile.value;
    if (isExiting.value || !p) return;
    triggerButtonFeedback('friend');
    isExiting.value = true;
    exitDirection.value = 'right';
    const targetId = p.id;
    setTimeout(() => {
        nextProfile();
        resetSwipeState();
        void submitAction(targetId, 'friend');
    }, EXIT_DURATION_MS);
}

function handleStudyBuddy() {
    const p = currentProfile.value;
    if (isExiting.value || !p) return;
    triggerButtonFeedback('study_buddy');
    isExiting.value = true;
    exitDirection.value = 'right';
    const targetId = p.id;
    setTimeout(() => {
        nextProfile();
        resetSwipeState();
        void submitAction(targetId, 'study_buddy');
    }, EXIT_DURATION_MS);
}

function handleIgnore() {
    const p = currentProfile.value;
    if (isExiting.value || !p) return;
    triggerButtonFeedback('ignored');
    isExiting.value = true;
    exitDirection.value = 'left';
    const targetId = p.id;
    setTimeout(() => {
        nextProfile();
        resetSwipeState();
        void submitAction(targetId, 'ignored');
    }, EXIT_DURATION_MS);
}

function resetSwipeState() {
    isExiting.value = false;
    exitDirection.value = null;
    isDragging.value = false;
    swipeStartX.value = 0;
    swipeCurrentX.value = 0;
    skipNextTransition.value = true;
    nextTick(() => {
        skipNextTransition.value = false;
    });
}

// Swipe handlers (touch + mouse)
function getClientX(e: TouchEvent | MouseEvent): number {
    return 'touches' in e ? e.touches[0].clientX : e.clientX;
}

function onSwipeStart(e: TouchEvent | MouseEvent) {
    if (isExiting.value || !currentProfile.value) return;
    isDragging.value = true;
    swipeStartX.value = getClientX(e);
    swipeCurrentX.value = swipeStartX.value;
    if (!('touches' in e)) {
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }
}

function onMouseMove(e: MouseEvent) {
    if (!isDragging.value) return;
    swipeCurrentX.value = e.clientX;
}

function onMouseUp() {
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
    onSwipeEnd();
}

function onSwipeMove(e: TouchEvent | MouseEvent) {
    if (!isDragging.value) return;
    if ('touches' in e) swipeCurrentX.value = e.touches[0].clientX;
}

function onSwipeEnd() {
    if (!isDragging.value) return;
    const delta = swipeCurrentX.value - swipeStartX.value;
    const p = currentProfile.value;
    if (Math.abs(delta) >= SWIPE_THRESHOLD && p) {
        isExiting.value = true;
        exitDirection.value = delta < 0 ? 'left' : 'right';
        const targetId = p.id;
        const intent = delta < 0 ? 'ignored' : 'dating';
        setTimeout(() => {
            nextProfile();
            resetSwipeState();
            void submitAction(targetId, intent);
        }, EXIT_DURATION_MS);
    } else {
        isDragging.value = false;
        swipeStartX.value = 0;
        swipeCurrentX.value = 0;
    }
}

function closeMatchModal() {
    matchModalUser.value = null;
}

function goToChatFromMatch() {
    if (matchModalUser.value) {
        openChat(matchModalUser.value.id);
        closeMatchModal();
    }
}

function goBack() {
    router.visit('/home');
}

onMounted(() => fetchMatches(1));

// When switching back to Discover, replay the action-row intro animation
watch(
    () => activeTab.value,
    (tab) => {
        if (tab === 'discover') playActionsIntro();
    },
    { immediate: true },
);

onUnmounted(() => {
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
    if (actionPopupTimer) window.clearTimeout(actionPopupTimer);
    if (buttonPopTimer) window.clearTimeout(buttonPopTimer);
    if (actionsIntroTimer) window.clearTimeout(actionsIntroTimer);
});

/** Only display name is shown in UI (no full name for unmatched users). */
function displayName(u: MatchUser | MatchedUser | null): string {
    return u?.display_name || 'User';
}
</script>

<template>
    <div class="h-screen flex flex-col overflow-hidden discover-screen">
        <Head title="Discover - NEMSU Match" />

        <!-- Compact header -->
        <header class="flex-shrink-0 bg-white/95 backdrop-blur-sm border-b border-gray-100 z-40">
            <div class="px-3 py-2.5 flex items-center justify-between">
                <button type="button" @click="goBack" class="p-2 -ml-1 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <div class="flex flex-col items-center">
                    <span class="text-base font-bold text-gray-900">Discover</span>
                    <span class="text-[10px] text-gray-500 font-medium">Swipe to connect</span>
                </div>
                <button type="button" class="p-2 rounded-full hover:bg-accent transition-colors" aria-label="Filter">
                    <Sparkles class="w-5 h-5 text-primary" />
                </button>
            </div>
            <!-- Tabs: Discover | Heart | Study | Smile (compact to avoid overflow) -->
            <div class="flex border-t border-gray-100 min-w-0">
                <button
                    type="button"
                    :class="activeTab === 'discover' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors truncate px-1"
                    @click="setTab('discover')"
                >
                    Discover
                </button>
                <button
                    type="button"
                    :class="activeTab === 'heart' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors flex items-center justify-center gap-0.5 px-1"
                    @click="setTab('heart')"
                >
                    <Heart class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" />
                    <span class="truncate">Heart</span>
                </button>
                <button
                    type="button"
                    :class="activeTab === 'study_buddy' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors flex items-center justify-center gap-0.5 px-1"
                    @click="setTab('study_buddy')"
                >
                    <BookOpen class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" />
                    <span class="truncate">Study</span>
                </button>
                <button
                    type="button"
                    :class="activeTab === 'smile' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors flex items-center justify-center gap-0.5 px-1"
                    @click="setTab('smile')"
                >
                    <Smile class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" />
                    <span class="truncate">Smile</span>
                </button>
            </div>
        </header>

        <!-- Full-bleed main: reserve bottom space for fixed nav (pb-20) -->
        <main class="flex-1 min-h-0 flex flex-col overflow-hidden relative pb-20">
            <!-- List tabs: Heart / Study Buddy / Smile -->
            <template v-if="activeTab !== 'discover'">
                <div class="flex-1 min-h-0 overflow-y-auto bg-white pb-6">
                    <div v-if="likedLists[activeTab].loading && likedLists[activeTab].data.length === 0" class="flex items-center justify-center py-16">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="likedLists[activeTab].data.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-gray-500">
                        <component :is="activeTab === 'heart' ? Heart : activeTab === 'study_buddy' ? BookOpen : Smile" class="w-14 h-14 mb-3 opacity-50" />
                        <p class="text-center font-medium">No one here yet</p>
                        <p class="text-sm mt-1 text-center">People you choose with {{ activeTab === 'heart' ? 'Heart' : activeTab === 'study_buddy' ? 'Study Buddy' : 'Smile' }} will appear here.</p>
                        <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li
                            v-for="u in likedLists[activeTab].data"
                            :key="u.id"
                            class="flex items-center gap-4 px-4 py-3 active:bg-gray-50"
                            @click="router.visit(`/profile/${u.id}`)"
                        >
                            <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-200 shrink-0">
                                <img
                                    v-if="u.profile_picture"
                                    :src="profilePictureSrc(u.profile_picture)"
                                    :alt="u.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="w-full h-full flex items-center justify-center text-xl font-bold text-gray-400">{{ (u.display_name || '?').charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 truncate">{{ u.display_name }}</p>
                                <p v-if="u.academic_program" class="text-sm text-gray-500 truncate">{{ u.academic_program }}</p>
                            </div>
                            <button
                                type="button"
                                :disabled="!u.matched"
                                :title="u.matched ? 'Send message' : 'Match first to message'"
                                class="shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                :class="u.matched ? 'bg-primary/10 text-primary hover:bg-primary/15' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                @click.stop="u.matched && openChat(u.id)"
                            >
                                Message
                            </button>
                        </li>
                    </ul>
                    <div v-if="likedLists[activeTab].data.length > 0 && likedLists[activeTab].page < likedLists[activeTab].lastPage" class="p-4 flex justify-center">
                        <button
                            type="button"
                            :disabled="likedLists[activeTab].loading"
                            class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50"
                            @click="fetchLikes(activeTab, likedLists[activeTab].page + 1)"
                        >
                            {{ likedLists[activeTab].loading ? 'Loading…' : 'Load more' }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- Discover: swipe cards -->
            <template v-else>
            <div v-if="loading && profiles.length === 0" class="absolute inset-0 flex items-center justify-center bg-gradient-to-b from-background to-muted">
                <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin" />
            </div>

            <template v-else-if="currentProfile">
                <!-- Full-area card stack: card fills 100% of main -->
                <div class="absolute inset-0 flex items-stretch justify-center">
                    <!-- Next card: peeking behind with inset -->
                    <div
                        v-if="nextProfileInStack"
                        class="absolute inset-2 rounded-2xl overflow-hidden pointer-events-none transform scale-[0.97] opacity-70"
                        style="z-index: 0;"
                    >
                        <div class="w-full h-full bg-gradient-to-br from-slate-200 to-slate-300 rounded-2xl shadow-2xl">
                            <img
                                v-if="nextProfileInStack.profile_picture"
                                :src="profilePictureSrc(nextProfileInStack.profile_picture)"
                                :alt="displayName(nextProfileInStack)"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-500 text-6xl font-bold rounded-2xl">
                                {{ displayName(nextProfileInStack).charAt(0).toUpperCase() }}
                            </div>
                        </div>
                    </div>

                    <!-- Current card: FULL WIDTH & HEIGHT of main (occupies all space) -->
                    <div
                        class="absolute inset-0 rounded-none sm:rounded-2xl overflow-hidden cursor-grab active:cursor-grabbing select-none touch-none"
                        style="z-index: 1; user-select: none;"
                        :style="cardStyle"
                        @touchstart="onSwipeStart"
                        @touchmove.prevent="onSwipeMove"
                        @touchend="onSwipeEnd"
                        @mousedown="onSwipeStart"
                    >
                        <div class="absolute inset-0 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200">
                            <!-- NOPE / LIKE overlays -->
                            <div
                                v-if="showNopeOverlay"
                                class="absolute inset-0 z-20 flex items-start justify-start pt-10 pl-6 pointer-events-none"
                            >
                                <span class="px-6 py-3 rounded-2xl border-4 border-red-500 text-red-500 text-3xl font-black uppercase rotate-[-20deg] shadow-2xl bg-white/10">Nope</span>
                            </div>
                            <div
                                v-if="showLikeOverlay"
                                class="absolute inset-0 z-20 flex items-start justify-end pt-10 pr-6 pointer-events-none"
                            >
                                <span class="px-6 py-3 rounded-2xl border-4 border-emerald-500 text-emerald-500 text-3xl font-black uppercase rotate-[12deg] shadow-2xl bg-white/10">Like</span>
                            </div>

                            <!-- Photo / gradient fill -->
                            <div class="absolute inset-0">
                                <img
                                    v-if="currentProfile.profile_picture"
                                    :src="profilePictureSrc(currentProfile.profile_picture)"
                                    :alt="displayName(currentProfile)"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="discover-placeholder w-full h-full flex items-center justify-center text-primary-foreground text-8xl font-bold bg-primary"
                                >
                                    {{ displayName(currentProfile).charAt(0).toUpperCase() }}
                                </div>
                                <!-- Overlay gradient for text readability -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent" />
                                <!-- Match badge: gradient pill, more engaging -->
                                <div class="absolute top-5 left-4 z-10 discover-badge">
                                    <Sparkles class="w-5 h-5 text-amber-200 shrink-0" />
                                    <span class="text-lg font-black tabular-nums text-white">{{ currentProfile.compatibility_score }}%</span>
                                    <span class="text-xs font-bold uppercase tracking-widest text-white/95">match</span>
                                </div>
                                <!-- Name, course, interests: above action buttons (pb-44 clears button row + nav) -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 pr-4 pb-44 text-white">
                                    <!-- Interest badges -->
                                    <div v-if="interestBadges.length" class="flex flex-wrap gap-1.5 mb-3">
                                        <span
                                            v-for="(interest, i) in interestBadges"
                                            :key="i"
                                            class="px-2.5 py-1 rounded-full text-xs font-semibold bg-white/25 backdrop-blur-sm border border-white/40"
                                        >
                                            {{ interest }}
                                        </span>
                                    </div>
                                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight drop-shadow-lg">{{ displayName(currentProfile) }}</h2>
                                    <p v-if="currentProfile.academic_program" class="text-sm sm:text-base font-semibold text-white/95 mt-1 drop-shadow">{{ currentProfile.academic_program }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horizontal action buttons: above bottom nav (bottom-24 = 6rem from main bottom) -->
                    <div class="absolute bottom-24 left-0 right-0 z-30 flex items-center justify-center gap-3 px-4">
                        <button
                            type="button"
                            @click="handleIgnore"
                            :disabled="isExiting"
                            class="action-btn action-btn--ignored w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white/95 backdrop-blur shadow-lg flex items-center justify-center border-2 border-gray-200 group disabled:opacity-60"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-0' : '',
                                'btn-float float-0',
                                lastClickedIntent === 'ignored' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Ignore"
                            title="Ignore"
                        >
                            <X class="w-7 h-7 sm:w-8 sm:h-8 text-gray-500 group-hover:text-red-500" stroke-width="2.5" />
                        </button>
                        <button
                            type="button"
                            @click="handleFriend"
                            :disabled="isExiting"
                            class="action-btn action-btn--friend w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-amber-50/95 backdrop-blur shadow-lg flex items-center justify-center text-amber-500 border-2 border-amber-400 disabled:opacity-60"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-1' : '',
                                'btn-float float-1',
                                lastClickedIntent === 'friend' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Friend"
                            title="Friend"
                        >
                            <Smile class="w-7 h-7 sm:w-8 sm:h-8" stroke-width="2" />
                        </button>
                        <button
                            type="button"
                            @click="handleStudyBuddy"
                            :disabled="isExiting"
                            class="action-btn action-btn--study w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-teal-50/95 backdrop-blur shadow-lg flex items-center justify-center text-teal-600 border-2 border-teal-400 disabled:opacity-60"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-2' : '',
                                'btn-float float-2',
                                lastClickedIntent === 'study_buddy' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Study Buddy"
                            title="Study Buddy"
                        >
                            <BookOpen class="w-7 h-7 sm:w-8 sm:h-8" stroke-width="2" />
                        </button>
                        <button
                            type="button"
                            @click="handleDating"
                            :disabled="isExiting"
                            class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-primary shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 transition-transform border-2 border-primary disabled:opacity-60"
                            :class="[
                                'action-btn action-btn--dating',
                                actionsIntroActive ? 'btn-intro delay-3' : '',
                                'btn-float float-3',
                                lastClickedIntent === 'dating' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Dating"
                            title="Dating"
                        >
                            <Heart class="w-7 h-7 sm:w-8 sm:h-8 text-primary-foreground fill-current" stroke-width="2.5" />
                        </button>
                    </div>

                    <!-- Action popup (on button click) -->
                    <Transition name="action-pop">
                        <div
                            v-if="actionPopupVisible && actionPopup"
                            class="absolute bottom-44 left-0 right-0 z-40 flex justify-center pointer-events-none"
                        >
                            <div
                                class="px-5 py-2.5 rounded-full shadow-xl text-sm font-semibold backdrop-blur border"
                                :class="actionPopup.intent === 'ignored'
                                    ? 'bg-background/90 text-foreground border-border'
                                    : actionPopup.intent === 'friend'
                                      ? 'bg-accent/90 text-accent-foreground border-border'
                                      : actionPopup.intent === 'study_buddy'
                                        ? 'bg-secondary/90 text-secondary-foreground border-border'
                                        : 'bg-primary/10 text-primary border-primary/20'"
                            >
                                {{ actionPopup.text }}
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Swipe hint: above button row (bottom-44 = same as card text padding) -->
                <div v-if="!actionPopupVisible" class="absolute bottom-44 left-0 right-0 z-20 flex justify-center pointer-events-none">
                    <span class="px-4 py-1.5 rounded-full bg-black/30 backdrop-blur text-white/90 text-xs font-medium">Swipe or tap buttons</span>
                </div>
            </template>

            <div v-else class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-b from-background to-muted px-6">
                <div class="w-24 h-24 rounded-full bg-primary flex items-center justify-center shadow-xl mb-4">
                    <Heart class="w-12 h-12 text-primary-foreground fill-current" />
                </div>
                <h3 class="text-xl font-bold text-gray-900">No more profiles</h3>
                <p class="text-gray-600 mt-2 text-center">Check back later for new matches.</p>
            </div>
            </template>
        </main>

        <!-- Match modal: CONGRATULATIONS / It's a match [name]!! / two avatars / Say hello / Keep swiping -->
        <Teleport to="body">
            <Transition name="match-modal">
                <div
                    v-if="matchModalUser"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="closeMatchModal"
                >
                    <div
                        class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden text-center animate-match-in"
                        @click.stop
                    >
                        <div class="px-6 pt-8">
                            <p class="text-xs font-bold uppercase tracking-wider text-primary">Congratulations</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">It's a match, {{ displayName(matchModalUser) }}!!</p>
                        </div>
                        <!-- Two overlapping avatars with floating hearts -->
                        <div class="relative flex justify-center items-center py-8">
                            <div class="relative flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-xl flex items-center justify-center text-2xl font-bold bg-gradient-to-br from-blue-100 to-cyan-100 text-blue-600 -mr-4 z-10">
                                    <img
                                        v-if="props.user?.profile_picture"
                                        :src="profilePictureSrc(props.user?.profile_picture)"
                                        :alt="props.user?.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else>{{ (props.user?.display_name || 'You').charAt(0).toUpperCase() }}</span>
                                </div>
                                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-xl flex items-center justify-center text-2xl font-bold bg-primary/10 text-primary z-20">
                                    <img
                                        v-if="matchModalUser.profile_picture"
                                        :src="profilePictureSrc(matchModalUser.profile_picture)"
                                        :alt="displayName(matchModalUser)"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else>{{ displayName(matchModalUser).charAt(0).toUpperCase() }}</span>
                                </div>
                            </div>
                            <!-- Floating hearts -->
                            <span class="absolute top-4 left-1/4 text-primary/70 text-2xl animate-float">❤</span>
                            <span class="absolute top-6 right-1/4 text-primary/70 text-xl animate-float-delay">❤</span>
                            <span class="absolute bottom-6 left-1/3 text-primary/60 text-lg animate-float">❤</span>
                            <span class="absolute bottom-4 right-1/3 text-primary/60 text-xl animate-float-delay">❤</span>
                        </div>
                        <p class="text-sm text-gray-600 px-6">Start a conversation now with each other</p>
                        <div class="px-6 pb-8 pt-6 flex flex-col gap-3">
                            <button
                                type="button"
                                @click="goToChatFromMatch"
                                class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-primary text-primary-foreground font-semibold hover:opacity-90 transition-colors"
                            >
                                <Heart class="w-5 h-5 fill-current" />
                                Say hello
                            </button>
                            <button
                                type="button"
                                @click="closeMatchModal"
                                class="w-full py-3 rounded-xl bg-secondary text-secondary-foreground font-semibold hover:bg-secondary/80 transition-colors"
                            >
                                Keep swiping
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <BottomNav active-tab="likeyou" :like-you-badge="0" />
    </div>
</template>

<style scoped>
.match-modal-enter-active,
.match-modal-leave-active {
    transition: opacity 0.25s ease;
}
.match-modal-enter-from,
.match-modal-leave-to {
    opacity: 0;
}
.match-modal-enter-active .bg-white,
.match-modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}
.match-modal-enter-from .bg-white {
    transform: scale(0.9);
}
.match-modal-enter-to .bg-white {
    transform: scale(1);
}
.match-modal-leave-to .bg-white {
    transform: scale(0.95);
}

@keyframes match-in {
    from {
        transform: scale(0.85);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-match-in {
    animation: match-in 0.35s ease-out;
}

@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.9; }
    50% { transform: translateY(-6px) scale(1.1); opacity: 1; }
}
.animate-float {
    animation: float 1.5s ease-in-out infinite;
}
.animate-float-delay {
    animation: float 1.5s ease-in-out infinite 0.5s;
}

.discover-screen {
    /* Use app theme tokens so this page follows global theme. */
    background: linear-gradient(180deg, var(--background) 0%, var(--muted) 55%, var(--accent) 100%);
}

.discover-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    border-radius: 9999px;
    background: color-mix(in oklab, var(--primary) 85%, transparent);
    border: 1px solid color-mix(in oklab, var(--primary) 25%, var(--border));
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
}

.discover-placeholder {
    letter-spacing: -0.02em;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
}

/* Action popup transition */
.action-pop-enter-active,
.action-pop-leave-active {
    transition: opacity 0.18s ease, transform 0.18s cubic-bezier(0.34, 1.2, 0.64, 1);
}
.action-pop-enter-from,
.action-pop-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.96);
}
.action-pop-enter-to,
.action-pop-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Button pop animation */
@keyframes btn-pop {
    0% { transform: scale(1); }
    28% { transform: scale(1.26); }
    52% { transform: scale(0.94); }
    74% { transform: scale(1.10); }
    100% { transform: scale(1); }
}
.btn-pop {
    animation: btn-pop 340ms cubic-bezier(0.22, 1.25, 0.36, 1);
}

/* Staggered intro for action row */
@keyframes btn-intro {
    0% { opacity: 0; transform: translateY(26px) scale(0.86); filter: blur(2px); }
    55% { opacity: 1; transform: translateY(-6px) scale(1.08); filter: blur(0); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
.btn-intro {
    animation: btn-intro 520ms cubic-bezier(0.34, 1.2, 0.64, 1);
    animation-fill-mode: both;
}
.delay-0 { animation-delay: 0ms; }
.delay-1 { animation-delay: 60ms; }
.delay-2 { animation-delay: 120ms; }
.delay-3 { animation-delay: 180ms; }

/* Subtle idle float */
@keyframes btn-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.btn-float {
    animation: btn-float 2.4s ease-in-out infinite;
}
.float-0 { animation-delay: 0ms; }
.float-1 { animation-delay: 240ms; }
.float-2 { animation-delay: 480ms; }
.float-3 { animation-delay: 720ms; }

/* Ripple/glow on click (uses pseudo-element) */
.action-btn {
    position: relative;
    overflow: hidden;
    will-change: transform;
    transition: transform 180ms cubic-bezier(0.22, 1.25, 0.36, 1), box-shadow 180ms ease;
}

/* Hover effects (mouse/trackpad only) */
@media (hover: hover) and (pointer: fine) {
    .action-btn::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 9999px;
        border: 2px solid transparent;
        opacity: 0;
        transform: scale(0.98);
        transition: opacity 180ms ease, transform 180ms cubic-bezier(0.22, 1.25, 0.36, 1);
        pointer-events: none;
    }

    .action-btn:hover {
        transform: translateY(-4px) scale(1.14);
        box-shadow: 0 26px 64px rgba(0, 0, 0, 0.24);
    }

    .action-btn:hover::before {
        opacity: 1;
        transform: scale(1);
        border-color: var(--glow);
        box-shadow: 0 0 0 8px color-mix(in oklab, var(--glow) 55%, transparent);
    }

    .action-btn:hover svg {
        animation: icon-wiggle 520ms cubic-bezier(0.22, 1.25, 0.36, 1);
        transform-origin: 50% 60%;
    }
}

.action-btn:active {
    transform: translateY(0) scale(0.96);
}
.btn-ripple::after {
    content: '';
    position: absolute;
    inset: -35%;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0) 58%);
    opacity: 0;
    transform: scale(0.6);
    animation: ripple 520ms cubic-bezier(0.22, 1.1, 0.36, 1);
    pointer-events: none;
}
@keyframes ripple {
    0% { opacity: 0.0; transform: scale(0.5); }
    22% { opacity: 0.85; transform: scale(0.92); }
    100% { opacity: 0; transform: scale(1.28); }
}

@keyframes icon-wiggle {
    0% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(-8deg) scale(1.06); }
    55% { transform: rotate(10deg) scale(1.08); }
    80% { transform: rotate(-4deg) scale(1.04); }
    100% { transform: rotate(0deg) scale(1); }
}

/* Strong glow pulse (color depends on action) */
.action-btn--ignored { --glow: rgba(239, 68, 68, 0.45); } /* red */
.action-btn--friend { --glow: rgba(245, 158, 11, 0.45); } /* amber */
.action-btn--study { --glow: rgba(20, 184, 166, 0.42); } /* teal */
.action-btn--dating { --glow: color-mix(in oklab, var(--primary) 55%, transparent); } /* theme primary */

@keyframes glowPulse {
    0% { box-shadow: 0 10px 26px rgba(0, 0, 0, 0.18), 0 0 0 rgba(0,0,0,0); }
    35% { box-shadow: 0 20px 52px rgba(0, 0, 0, 0.22), 0 0 0 10px var(--glow); }
    100% { box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18), 0 0 0 0 rgba(0,0,0,0); }
}
.btn-glow {
    animation: glowPulse 520ms cubic-bezier(0.22, 1.1, 0.36, 1);
}

@media (prefers-reduced-motion: reduce) {
    .btn-intro,
    .btn-float,
    .btn-pop,
    .btn-glow {
        animation: none !important;
    }
    .action-btn,
    .action-btn:hover,
    .action-btn:active {
        transition: none !important;
        transform: none !important;
    }
    .btn-ripple::after {
        animation: none !important;
    }
}
</style>
