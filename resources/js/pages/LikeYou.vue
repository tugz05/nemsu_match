<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Heart, X, Sparkles, Smile, BookOpen, ChevronLeft, MessageCircle, ShieldCheck } from 'lucide-vue-next';
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
    compatibility_score?: number | null;
    common_tags: string[];
}

interface MatchedUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
}

/** User as returned from GET /api/matchmaking/who-liked-me */
interface WhoLikedMeUser {
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
    their_intent?: 'dating' | 'friend' | 'study_buddy';
    my_intent?: 'dating' | 'friend' | 'study_buddy';
    liked_at: string;
}

/** User as returned from GET /api/matchmaking/mutual */
interface MutualMatchUser {
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
    matched_at: string;
    intent?: string;
}

type DiscoverTab = 'discover' | 'recent' | 'likes_you' | 'matches';

interface FreemiumState {
    freemium_enabled: boolean;
    is_plus: boolean;
    remaining_likes_today: number;
    daily_likes_limit: number;
    can_super_like_today: boolean;
    plus_monthly_price: number;
}

interface MatchedUserForModal {
    id: number;
    display_name?: string;
    fullname?: string;
    profile_picture?: string | null;
}

const props = defineProps<{
    user?: { id: number; display_name?: string; profile_picture?: string | null };
    /** When set (e.g. from /like-you?show_match=id), show the match modal for this user on load */
    showMatchUser?: MatchedUserForModal | null;
}>();

const getCsrfToken = useCsrfToken();
const profiles = ref<MatchUser[]>([]);
const currentIndex = ref(0);
const loading = ref(true);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const followLoading = ref<number | null>(null);

// Main tabs: Discover (swipe) | Recent (my likes) | Likes you (who liked me) | Matches
const activeTab = ref<DiscoverTab>('discover');

// Freemium state (from discover response or freemium-state API)
const freemium = ref<FreemiumState>({
    freemium_enabled: false,
    is_plus: true,
    remaining_likes_today: 999,
    daily_likes_limit: 999,
    can_super_like_today: false,
    plus_monthly_price: 49,
});

// Discover filters (Plus only when freemium on)
const filterCampus = ref('');
const filterProgram = ref('');
const filterYear = ref('');
const showFilterPanel = ref(false);

// Recent: my recent likes (users I liked)
const whoLikedMeList = ref<WhoLikedMeUser[]>([]);
const whoLikedMeLoading = ref(false);
const whoLikedMeLoadingMore = ref(false);
const whoLikedMePage = ref(1);
const whoLikedMeLastPage = ref(1);
const matchBackActionUserId = ref<number | null>(null);

// Likes you: who liked me (Plus only when freemium on)
const likesYouList = ref<WhoLikedMeUser[]>([]);
const likesYouCount = ref(0);
const likesYouCountLocked = ref(false);
const likesYouLoading = ref(false);
const likesYouLoadingMore = ref(false);
const likesYouPage = ref(1);
const likesYouLastPage = ref(1);
const likesYouActionUserId = ref<number | null>(null);

// Matches: mutual matches
const mutualList = ref<MutualMatchUser[]>([]);
const mutualLoading = ref(false);
const mutualLoadingMore = ref(false);
const mutualPage = ref(1);
const mutualLastPage = ref(1);

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
const swipeStartY = ref(0);
const swipeCurrentY = ref(0);
const isDragging = ref(false);
const exitDirection = ref<'left' | 'right' | null>(null);
const isExiting = ref(false);
/** When true, next card appears at rest with no transition (avoids bounce-back). */
const skipNextTransition = ref(false);
const SWIPE_THRESHOLD = 80;
const TAP_MOVE_THRESHOLD = 12;
const ROTATE_FACTOR = 0.15;
const EXIT_DURATION_MS = 380;

const currentProfile = computed(() => profiles.value[currentIndex.value] ?? null);
const nextProfileInStack = computed(() => profiles.value[currentIndex.value + 1] ?? null);

const atLikesLimit = computed(() =>
    freemium.value.freemium_enabled && !freemium.value.is_plus && freemium.value.remaining_likes_today <= 0,
);
const canLike = computed(() => !atLikesLimit.value);
const likesLabel = computed(() => {
    if (!freemium.value.freemium_enabled || freemium.value.is_plus) return null;
    return `${freemium.value.remaining_likes_today}/${freemium.value.daily_likes_limit} likes today`;
});

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
    const fromInterests = ensureStringArray(p.interests);
    const fromCommon = Array.isArray(p.common_tags) ? p.common_tags.slice(0, 6) : [];
    const combined = fromInterests.length ? fromInterests : fromCommon;
    return combined.slice(0, 6);
});

function buildDiscoverUrl(page: number): string {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('_ts', String(Date.now()));
    if (freemium.value.freemium_enabled && freemium.value.is_plus) {
        if (filterCampus.value) params.set('campus', filterCampus.value);
        if (filterProgram.value) params.set('academic_program', filterProgram.value);
        if (filterYear.value) params.set('year_level', filterYear.value);
    }
    return `/api/matchmaking/discover?${params.toString()}`;
}

async function fetchMatches(page: number) {
    const res = await fetch(buildDiscoverUrl(page), {
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        cache: 'no-store',
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
    if (data.freemium) {
        freemium.value = { ...freemium.value, ...data.freemium };
    }
    loading.value = false;
}

async function fetchFreemiumState() {
    const res = await fetch('/api/matchmaking/freemium-state', {
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
    });
    if (res.ok) {
        const data = await res.json();
        freemium.value = { ...freemium.value, ...data };
    }
}

async function loadMore() {
    if (currentPage.value >= lastPage.value || loading.value) return;
    loading.value = true;
    await fetchMatches(currentPage.value + 1);
}


async function fetchWhoLikedMe(page = 1) {
    if (page > 1) whoLikedMeLoadingMore.value = true;
    else whoLikedMeLoading.value = true;
    try {
        const res = await fetch(`/api/matchmaking/my-recent-likes?page=${page}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const items = (data.data ?? []) as WhoLikedMeUser[];
        if (page === 1) whoLikedMeList.value = items;
        else whoLikedMeList.value = [...whoLikedMeList.value, ...items];
        whoLikedMePage.value = data.current_page ?? page;
        whoLikedMeLastPage.value = data.last_page ?? page;
    } finally {
        whoLikedMeLoading.value = false;
        whoLikedMeLoadingMore.value = false;
    }
}

async function fetchLikesYouCount() {
    const res = await fetch('/api/matchmaking/who-liked-me-count', {
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
    });
    if (!res.ok) return;
    const data = await res.json();
    likesYouCount.value = data.count ?? 0;
    likesYouCountLocked.value = data.locked === true;
}

async function fetchLikesYou(page = 1) {
    if (page > 1) likesYouLoadingMore.value = true;
    else likesYouLoading.value = true;
    try {
        const res = await fetch(`/api/matchmaking/who-liked-me?page=${page}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) {
            if (res.status === 403) return;
            return;
        }
        const data = await res.json();
        const items = (data.data ?? []) as WhoLikedMeUser[];
        if (page === 1) likesYouList.value = items;
        else likesYouList.value = [...likesYouList.value, ...items];
        likesYouPage.value = data.current_page ?? page;
        likesYouLastPage.value = data.last_page ?? page;
    } finally {
        likesYouLoading.value = false;
        likesYouLoadingMore.value = false;
    }
}

async function fetchMutual(page = 1) {
    if (page > 1) mutualLoadingMore.value = true;
    else mutualLoading.value = true;
    try {
        const res = await fetch(`/api/matchmaking/mutual?page=${page}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const items = (data.data ?? []) as MutualMatchUser[];
        if (page === 1) mutualList.value = items;
        else mutualList.value = [...mutualList.value, ...items];
        mutualPage.value = data.current_page ?? page;
        mutualLastPage.value = data.last_page ?? page;
    } finally {
        mutualLoading.value = false;
        mutualLoadingMore.value = false;
    }
}

function setTab(tab: DiscoverTab) {
    activeTab.value = tab;
    if (tab === 'recent' && whoLikedMeList.value.length === 0 && !whoLikedMeLoading.value) fetchWhoLikedMe(1);
    if (tab === 'likes_you') {
        if (freemium.value.freemium_enabled && !freemium.value.is_plus) fetchLikesYouCount();
        else if (likesYouList.value.length === 0 && !likesYouLoading.value) fetchLikesYou(1);
    }
    if (tab === 'matches' && mutualList.value.length === 0 && !mutualLoading.value) fetchMutual(1);
}


function intentLabel(intent: string): string {
    switch (intent) {
        case 'dating': return 'Heart';
        case 'friend': return 'Smile';
        case 'study_buddy': return 'Study';
        default: return 'Match';
    }
}

function intentIcon(intent: string) {
    switch (intent) {
        case 'dating': return Heart;
        case 'friend': return Smile;
        case 'study_buddy': return BookOpen;
        default: return Heart;
    }
}

function intentColor(intent: string): string {
    switch (intent) {
        case 'dating': return 'text-pink-600';
        case 'friend': return 'text-amber-600';
        case 'study_buddy': return 'text-teal-600';
        default: return 'text-blue-600';
    }
}

async function matchBack(user: WhoLikedMeUser) {
    matchBackActionUserId.value = user.id;
    try {
        const res = await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ target_user_id: user.id, intent: user.their_intent }),
        });
        const data = await res.json();
        whoLikedMeList.value = whoLikedMeList.value.filter((u) => u.id !== user.id);
        if (data.matched && data.other_user) {
            matchModalUser.value = data.other_user;
        }
    } finally {
        matchBackActionUserId.value = null;
    }
}

async function pass(user: WhoLikedMeUser) {
    matchBackActionUserId.value = user.id;
    try {
        await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ target_user_id: user.id, intent: 'ignored' }),
        });
        whoLikedMeList.value = whoLikedMeList.value.filter((u) => u.id !== user.id);
    } finally {
        matchBackActionUserId.value = null;
    }
}

async function matchBackLikesYou(user: WhoLikedMeUser) {
    likesYouActionUserId.value = user.id;
    try {
        const res = await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ target_user_id: user.id, intent: user.their_intent ?? 'dating' }),
        });
        const data = await res.json();
        likesYouList.value = likesYouList.value.filter((u) => u.id !== user.id);
        if (data.matched && data.other_user) matchModalUser.value = data.other_user;
    } finally {
        likesYouActionUserId.value = null;
    }
}

function openProfileForMatch(id: number) {
    router.visit(`/profile/${id}`);
}

function openChatForMatch(userId: number) {
    router.visit(`/chat?user=${userId}`);
}

function formatMatchedAt(iso: string): string {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const diffDays = Math.floor((now.getTime() - d.getTime()) / 86400000);
    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    return d.toLocaleDateString();
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
async function submitAction(
    targetUserId: number,
    intent: 'dating' | 'friend' | 'study_buddy' | 'ignored',
    superLike = false,
) {
    if (followLoading.value !== null) return;
    if (atLikesLimit.value && intent !== 'ignored') return;

    followLoading.value = targetUserId;
    try {
        const body: { target_user_id: number; intent: string; super_like?: boolean } = {
            target_user_id: targetUserId,
            intent,
        };
        if (superLike && freemium.value.can_super_like_today) body.super_like = true;
        const res = await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 403 && data.code === 'daily_limit_reached') {
            await fetchFreemiumState();
            return;
        }
        if (data.matched && data.other_user) {
            matchModalUser.value = data.other_user;
        }
        if (res.ok && intent !== 'ignored') {
            freemium.value.remaining_likes_today = Math.max(0, freemium.value.remaining_likes_today - 1);
            if (superLike && freemium.value.can_super_like_today) freemium.value.can_super_like_today = false;
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

function handleDating(superLike = false) {
    const p = currentProfile.value;
    if (isExiting.value || !p || !canLike.value) return;
    triggerButtonFeedback('dating');
    isExiting.value = true;
    exitDirection.value = 'right';
    const targetId = p.id;
    setTimeout(() => {
        nextProfile();
        resetSwipeState();
        void submitAction(targetId, 'dating', superLike);
    }, EXIT_DURATION_MS);
}

function handleFriend() {
    const p = currentProfile.value;
    if (isExiting.value || !p || !canLike.value) return;
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
    if (isExiting.value || !p || !canLike.value) return;
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
    swipeStartY.value = 0;
    swipeCurrentY.value = 0;
    skipNextTransition.value = true;
    nextTick(() => {
        skipNextTransition.value = false;
    });
}

// Swipe handlers (touch + mouse)
function getClientX(e: TouchEvent | MouseEvent): number {
    return 'touches' in e ? e.touches[0].clientX : e.clientX;
}
function getClientY(e: TouchEvent | MouseEvent): number {
    return 'touches' in e ? e.touches[0].clientY : e.clientY;
}

function onSwipeStart(e: TouchEvent | MouseEvent) {
    if (isExiting.value || !currentProfile.value) return;
    isDragging.value = true;
    swipeStartX.value = getClientX(e);
    swipeCurrentX.value = swipeStartX.value;
    swipeStartY.value = getClientY(e);
    swipeCurrentY.value = swipeStartY.value;
    if (!('touches' in e)) {
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }
}

function onMouseMove(e: MouseEvent) {
    if (!isDragging.value) return;
    swipeCurrentX.value = e.clientX;
    swipeCurrentY.value = e.clientY;
}

function onMouseUp() {
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
    onSwipeEnd();
}

function onSwipeMove(e: TouchEvent | MouseEvent) {
    if (!isDragging.value) return;
    if ('touches' in e) {
        swipeCurrentX.value = e.touches[0].clientX;
        swipeCurrentY.value = e.touches[0].clientY;
    }
}

function onSwipeEnd() {
    if (!isDragging.value) return;
    const deltaX = swipeCurrentX.value - swipeStartX.value;
    const deltaY = swipeCurrentY.value - swipeStartY.value;
    const p = currentProfile.value;
    if (Math.abs(deltaX) >= SWIPE_THRESHOLD && p) {
        isExiting.value = true;
        exitDirection.value = deltaX < 0 ? 'left' : 'right';
        const targetId = p.id;
        const intent = deltaX < 0 ? 'ignored' : 'dating';
        setTimeout(() => {
            nextProfile();
            resetSwipeState();
            void submitAction(targetId, intent);
        }, EXIT_DURATION_MS);
    } else if (Math.abs(deltaX) < TAP_MOVE_THRESHOLD && Math.abs(deltaY) < TAP_MOVE_THRESHOLD && p) {
        resetSwipeState();
        router.visit(`/profile/${p.id}`);
    } else {
        resetSwipeState();
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
    router.visit('/browse');
}

onMounted(() => {
    fetchFreemiumState().then(() => fetchMatches(1));
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab === 'match_back') setTab('likes_you');
    else if (tab === 'matches') setTab('matches');
});

function applyFilters() {
    showFilterPanel.value = false;
    loading.value = true;
    fetchMatches(1);
}

// When showMatchUser is set (e.g. from notification → like-you?show_match=id), open the match modal (works on first load and when Inertia updates props)
watch(
    () => props.showMatchUser,
    (val) => {
        if (val?.id) {
            activeTab.value = 'matches';
            nextTick(() => {
                matchModalUser.value = {
                    id: val.id,
                    display_name: val.display_name ?? '',
                    fullname: val.fullname ?? '',
                    profile_picture: val.profile_picture ?? null,
                };
            });
        }
    },
    { immediate: true },
);

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
                    <span v-if="likesLabel" class="text-[10px] text-amber-600 font-medium">{{ likesLabel }}</span>
                    <span v-else class="text-[10px] text-gray-500 font-medium">Swipe to connect</span>
                </div>
                <button
                    v-if="freemium.freemium_enabled && freemium.is_plus"
                    type="button"
                    class="p-2 rounded-full hover:bg-accent transition-colors"
                    aria-label="Filter"
                    @click="showFilterPanel = !showFilterPanel"
                >
                    <Sparkles class="w-5 h-5 text-primary" />
                </button>
                <div v-else class="w-10" />
            </div>
            <!-- Tabs: Discover | Recent | Likes you (when freemium) | Matches -->
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
                    :class="activeTab === 'recent' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors px-1 truncate"
                    @click="setTab('recent')"
                >
                    Recent
                </button>
                <button
                    v-if="freemium.freemium_enabled"
                    type="button"
                    :class="activeTab === 'likes_you' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors px-1 truncate"
                    @click="setTab('likes_you')"
                >
                    Likes you
                </button>
                <button
                    type="button"
                    :class="activeTab === 'matches' ? 'border-primary text-primary font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="flex-1 min-w-0 py-2.5 text-xs sm:text-sm border-b-2 transition-colors px-1 truncate"
                    @click="setTab('matches')"
                >
                    Matches
                </button>
            </div>
            <!-- Plus filters (campus, program, year) -->
            <div v-if="showFilterPanel && freemium.freemium_enabled && freemium.is_plus" class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[100px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Campus</label>
                    <input v-model="filterCampus" type="text" placeholder="Any" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" @keyup.enter="applyFilters" />
                </div>
                <div class="flex-1 min-w-[100px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Program</label>
                    <input v-model="filterProgram" type="text" placeholder="Any" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" @keyup.enter="applyFilters" />
                </div>
                <div class="flex-1 min-w-[80px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Year</label>
                    <input v-model="filterYear" type="text" placeholder="Any" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" @keyup.enter="applyFilters" />
                </div>
                <button type="button" class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90" @click="applyFilters">Apply</button>
            </div>
        </header>

        <!-- Full-bleed main: reserve space for action buttons + bottom nav (safe area on mobile) -->
        <main class="flex-1 min-h-0 flex flex-col overflow-hidden relative discover-main">
            <!-- Recent: who liked you -->
            <template v-if="activeTab === 'recent'">
                <div class="flex-1 min-h-0 overflow-y-auto bg-gradient-to-br from-blue-50 via-white to-cyan-50 pb-6">
                    <div v-if="whoLikedMeLoading && whoLikedMeList.length === 0" class="flex justify-center py-20">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 border-4 border-pink-500 border-t-transparent rounded-full animate-spin" />
                            <p class="text-sm text-gray-600 font-medium">Loading your admirers...</p>
                        </div>
                    </div>
                    <div v-else-if="whoLikedMeList.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
                        <Heart class="w-14 h-14 mb-3 opacity-50" />
                        <p class="text-center font-medium">No one to match back yet</p>
                        <p class="text-sm mt-1 text-center">When someone likes you, they’ll show up here.</p>
                        <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li
                            v-for="u in whoLikedMeList"
                            :key="u.id"
                            class="flex gap-4 px-4 py-3"
                        >
                            <button type="button" @click="openProfileForMatch(u.id)" class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow">
                                <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" :alt="u.display_name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xl">{{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}</div>
                            </button>
                            <div class="flex-1 min-w-0">
                                <button type="button" @click="openProfileForMatch(u.id)" class="text-left block w-full">
                                    <p class="font-semibold text-gray-900 truncate">{{ u.fullname || u.display_name }}</p>
                                    <p v-if="u.campus" class="text-xs text-gray-500 truncate">{{ u.campus }}</p>
                                </button>
                                <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                    <component :is="intentIcon(u.my_intent)" class="w-3.5 h-3.5" />
                                    You liked: {{ intentLabel(u.my_intent) }}
                                </p>
                                <div class="flex gap-2 mt-3">
                                    <button type="button" @click="openProfileForMatch(u.id)" class="flex-1 px-4 py-2 rounded-xl bg-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-300">View Profile</button>
                                    <button type="button" @click="openChatForMatch(u.id)" class="flex-1 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95">Message</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-if="whoLikedMeList.length > 0 && whoLikedMePage < whoLikedMeLastPage" class="p-4 flex justify-center">
                        <button type="button" :disabled="whoLikedMeLoadingMore" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50" @click="fetchWhoLikedMe(whoLikedMePage + 1)">{{ whoLikedMeLoadingMore ? 'Loading…' : 'Load more' }}</button>
                    </div>
                </div>
            </template>

            <!-- Likes you: who liked me (Plus when freemium on; else upgrade CTA) -->
            <template v-else-if="activeTab === 'likes_you'">
                <div class="flex-1 min-h-0 overflow-y-auto bg-gradient-to-br from-pink-50 via-white to-rose-50 pb-6">
                    <div v-if="freemium.freemium_enabled && !freemium.is_plus" class="flex flex-col items-center justify-center py-16 px-6">
                        <Heart class="w-14 h-14 mb-3 text-pink-400" />
                        <p class="text-center font-semibold text-gray-900">{{ likesYouCount }} {{ likesYouCount === 1 ? 'person likes' : 'people like' }} you</p>
                        <p class="text-sm mt-1 text-center text-gray-600">Upgrade to NEMSU Match Plus to see who and match back.</p>
                        <a href="/plus" class="mt-6 px-6 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold shadow-lg hover:opacity-95">Upgrade for ₱{{ freemium.plus_monthly_price }}/mo</a>
                    </div>
                    <template v-else>
                        <div v-if="likesYouLoading && likesYouList.length === 0" class="flex justify-center py-20">
                            <div class="w-12 h-12 border-4 border-pink-500 border-t-transparent rounded-full animate-spin" />
                        </div>
                        <div v-else-if="likesYouList.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
                            <Heart class="w-14 h-14 mb-3 opacity-50" />
                            <p class="text-center font-medium">No one has liked you yet</p>
                            <p class="text-sm mt-1 text-center">Keep swiping — when someone likes you, they’ll show up here.</p>
                            <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                        </div>
                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="u in likesYouList" :key="u.id" class="flex gap-4 px-4 py-3">
                                <button type="button" @click="openProfileForMatch(u.id)" class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow">
                                    <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" :alt="u.display_name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-pink-600 font-bold text-xl">{{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}</div>
                                </button>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ u.fullname || u.display_name }}</p>
                                    <p v-if="u.campus" class="text-xs text-gray-500 truncate">{{ u.campus }}</p>
                                    <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                        <component :is="intentIcon(u.their_intent)" class="w-3.5 h-3.5" />
                                        {{ intentLabel(u.their_intent) }}
                                    </p>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="openProfileForMatch(u.id)" class="flex-1 px-4 py-2 rounded-xl bg-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-300">View</button>
                                        <button type="button" :disabled="likesYouActionUserId === u.id" class="flex-1 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 disabled:opacity-50" @click="matchBackLikesYou(u)">{{ likesYouActionUserId === u.id ? '…' : 'Like back' }}</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div v-if="likesYouList.length > 0 && likesYouPage < likesYouLastPage" class="p-4 flex justify-center">
                            <button type="button" :disabled="likesYouLoadingMore" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50" @click="fetchLikesYou(likesYouPage + 1)">{{ likesYouLoadingMore ? 'Loading…' : 'Load more' }}</button>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Matches: mutual matches -->
            <template v-else-if="activeTab === 'matches'">
                <div class="flex-1 min-h-0 overflow-y-auto bg-white pb-6">
                    <div v-if="mutualLoading && mutualList.length === 0" class="flex justify-center py-16">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="mutualList.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-gray-500">
                        <Heart class="w-14 h-14 mb-3 opacity-50" />
                        <p class="text-center font-medium">No matches yet</p>
                        <p class="text-sm mt-1 text-center">When you and someone like each other, they’ll appear here.</p>
                        <button type="button" @click="setTab('discover')" class="mt-4 px-4 py-2 rounded-xl bg-primary text-primary-foreground font-medium text-sm hover:opacity-90">Discover</button>
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="u in mutualList" :key="u.id" class="flex items-center gap-4 px-4 py-3">
                            <button type="button" @click="openProfileForMatch(u.id)" class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow">
                                <img v-if="u.profile_picture" :src="profilePictureSrc(u.profile_picture)" :alt="u.display_name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xl">{{ (u.display_name || u.fullname || '?').charAt(0).toUpperCase() }}</div>
                            </button>
                            <div class="min-w-0 flex-1">
                                <button type="button" @click="openProfileForMatch(u.id)" class="text-left block w-full">
                                    <p class="font-semibold text-gray-900 truncate">{{ u.fullname || u.display_name }}</p>
                                    <p v-if="u.campus" class="text-xs text-gray-500 truncate">{{ u.campus }}</p>
                                    <p v-if="u.matched_at" class="text-xs text-gray-400 mt-0.5">Matched {{ formatMatchedAt(u.matched_at) }}</p>
                                </button>
                                <p v-if="u.intent" class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    <component :is="intentIcon(u.intent)" class="w-3 h-3" />
                                    {{ intentLabel(u.intent) }}
                                </p>
                            </div>
                            <button type="button" @click="openChatForMatch(u.id)" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 shrink-0">
                                <MessageCircle class="w-4 h-4" />
                                Message
                            </button>
                        </li>
                    </ul>
                    <div v-if="mutualList.length > 0 && mutualPage < mutualLastPage" class="p-4 flex justify-center">
                        <button type="button" :disabled="mutualLoadingMore" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium disabled:opacity-50" @click="fetchMutual(mutualPage + 1)">{{ mutualLoadingMore ? 'Loading…' : 'Load more' }}</button>
                    </div>
                </div>
            </template>

            <!-- Discover: swipe cards - wrapper gets flex height so card stack can fill it -->
            <template v-else>
            <div class="discover-fill flex-1 min-h-0 relative flex flex-col">
            <div class="absolute top-3 left-3 right-20 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 z-20">
                <div class="rounded-xl border border-blue-200 bg-blue-50/95 backdrop-blur px-3 py-2 shadow-sm">
                    <div class="flex items-start gap-2">
                        <ShieldCheck class="w-4 h-4 text-blue-700 mt-0.5 shrink-0" />
                        <p class="text-[11px] sm:text-xs text-blue-900 leading-snug">
                            Safety tip: You can report malicious accounts from their profile. A badge on the profile means the user is verified.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="loading && profiles.length === 0" class="absolute inset-0 flex items-center justify-center bg-gradient-to-b from-background to-muted">
                <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin" />
            </div>

            <template v-else-if="currentProfile">
                <!-- Full-area card stack: fills the flex wrapper so height is guaranteed -->
                <div class="absolute inset-0 flex items-stretch justify-center discover-card-stack">
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
                        class="absolute inset-0 h-full w-full min-h-0 rounded-none sm:rounded-2xl overflow-hidden cursor-grab active:cursor-grabbing select-none touch-none"
                        style="z-index: 1; user-select: none;"
                        :style="cardStyle"
                        @touchstart="onSwipeStart"
                        @touchmove.prevent="onSwipeMove"
                        @touchend="onSwipeEnd"
                        @mousedown="onSwipeStart"
                    >
                        <div class="absolute inset-0 h-full w-full min-h-0 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200">
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

                            <!-- Photo / gradient fill: explicit full size so content is visible -->
                            <div class="absolute inset-0 h-full w-full min-h-0 discover-photo-layer">
                                <img
                                    v-if="currentProfile.profile_picture"
                                    :src="profilePictureSrc(currentProfile.profile_picture)"
                                    :alt="displayName(currentProfile)"
                                    class="absolute inset-0 w-full h-full object-cover discover-card-img"
                                />
                                <div
                                    v-else
                                    class="discover-placeholder absolute inset-0 w-full h-full flex items-center justify-center text-primary-foreground text-6xl sm:text-8xl font-bold bg-primary"
                                >
                                    {{ displayName(currentProfile).charAt(0).toUpperCase() }}
                                </div>
                                <!-- Overlay gradient for text readability (below badge and card info) -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent discover-card-overlay" aria-hidden="true" />
                                <!-- Match badge: gradient pill, more engaging -->
                                <div v-if="currentProfile.compatibility_score != null" class="absolute top-5 left-4 z-10 discover-badge">
                                    <Sparkles class="w-5 h-5 text-amber-200 shrink-0" />
                                    <span class="text-lg font-black tabular-nums text-white">{{ currentProfile.compatibility_score }}%</span>
                                    <span class="text-xs font-bold uppercase tracking-widest text-white/95">match</span>
                                </div>
                                <!-- Name, course, interests: above overlay so always visible -->
                                <div class="absolute left-0 right-0 p-4 discover-card-info text-white z-10">
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
                                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight drop-shadow-lg">
                                        {{ displayName(currentProfile) }}
                                    </h2>
                                    <p
                                        v-if="currentProfile.academic_program"
                                        class="text-sm sm:text-base font-semibold text-white/95 mt-1 drop-shadow"
                                    >
                                        {{ currentProfile.academic_program }}
                                    </p>
                                    <p
                                        v-if="currentProfile.campus || currentProfile.year_level"
                                        class="text-xs sm:text-sm text-white/90 mt-0.5 drop-shadow-sm"
                                    >
                                        <span v-if="currentProfile.campus">{{ currentProfile.campus }}</span>
                                        <span v-if="currentProfile.campus && currentProfile.year_level"> • </span>
                                        <span v-if="currentProfile.year_level">{{ currentProfile.year_level }}</span>
                                    </p>
                                    <p
                                        v-if="currentProfile.age || currentProfile.gender"
                                        class="text-xs sm:text-sm text-white/80 mt-0.5 drop-shadow-sm"
                                    >
                                        <span v-if="currentProfile.age">{{ currentProfile.age }} yrs</span>
                                        <span v-if="currentProfile.age && currentProfile.gender"> • </span>
                                        <span v-if="currentProfile.gender">{{ currentProfile.gender }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vertical action bar on the right (mobile-first, always visible) -->
                    <div class="discover-actions flex flex-col items-center justify-center gap-4 sm:gap-5 py-2">
                        <button
                            type="button"
                            @click="handleIgnore"
                            :disabled="isExiting"
                            class="action-btn action-btn--ignored w-16 h-16 sm:w-18 sm:h-18 rounded-full bg-white/95 backdrop-blur shadow-2xl flex items-center justify-center border-3 border-gray-300 group disabled:opacity-60 shrink-0 hover:scale-125 hover:shadow-[0_20px_50px_rgba(239,68,68,0.4)] hover:border-red-400 transition-all duration-300"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-0' : '',
                                'btn-float float-0',
                                lastClickedIntent === 'ignored' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Ignore"
                            title="Ignore"
                        >
                            <X class="w-8 h-8 sm:w-9 sm:h-9 text-gray-500 group-hover:text-red-500 group-hover:rotate-90 transition-all duration-300" stroke-width="3" />
                        </button>
                        <button
                            type="button"
                            @click="handleFriend"
                            :disabled="isExiting || atLikesLimit"
                            class="action-btn action-btn--friend w-16 h-16 sm:w-18 sm:h-18 rounded-full bg-gradient-to-br from-amber-100 to-amber-200 backdrop-blur shadow-2xl flex items-center justify-center text-amber-600 border-3 border-amber-400 disabled:opacity-60 shrink-0 hover:scale-125 hover:shadow-[0_20px_50px_rgba(251,191,36,0.5)] hover:from-amber-200 hover:to-amber-300 transition-all duration-300 group"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-1' : '',
                                'btn-float float-1',
                                lastClickedIntent === 'friend' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Friend"
                            title="Friend"
                        >
                            <Smile class="w-8 h-8 sm:w-9 sm:h-9 group-hover:scale-110 transition-transform duration-300" stroke-width="2.5" />
                        </button>
                        <button
                            type="button"
                            @click="handleStudyBuddy"
                            :disabled="isExiting || atLikesLimit"
                            class="action-btn action-btn--study w-16 h-16 sm:w-18 sm:h-18 rounded-full bg-gradient-to-br from-cyan-100 to-teal-200 backdrop-blur shadow-2xl flex items-center justify-center text-teal-700 border-3 border-teal-400 disabled:opacity-60 shrink-0 hover:scale-125 hover:shadow-[0_20px_50px_rgba(20,184,166,0.5)] hover:from-cyan-200 hover:to-teal-300 transition-all duration-300 group"
                            :class="[
                                actionsIntroActive ? 'btn-intro delay-2' : '',
                                'btn-float float-2',
                                lastClickedIntent === 'study_buddy' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Study Buddy"
                            title="Study Buddy"
                        >
                            <BookOpen class="w-8 h-8 sm:w-9 sm:h-9 group-hover:scale-110 transition-transform duration-300" stroke-width="2.5" />
                        </button>
                        <button
                            type="button"
                            @click="handleDating(false)"
                            :disabled="isExiting || atLikesLimit"
                            class="w-16 h-16 sm:w-18 sm:h-18 rounded-full bg-gradient-to-br from-pink-500 via-red-500 to-rose-600 shadow-2xl flex items-center justify-center hover:scale-125 active:scale-95 transition-all duration-300 border-3 border-pink-400 disabled:opacity-60 shrink-0 hover:shadow-[0_20px_50px_rgba(236,72,153,0.6)] animate-pulse-slow group"
                            :class="[
                                'action-btn action-btn--dating',
                                actionsIntroActive ? 'btn-intro delay-3' : '',
                                'btn-float float-3',
                                lastClickedIntent === 'dating' ? 'btn-pop btn-ripple btn-glow' : '',
                            ]"
                            aria-label="Dating"
                            title="Dating"
                        >
                            <Heart class="w-8 h-8 sm:w-9 sm:h-9 text-white fill-white group-hover:scale-110 transition-transform duration-300 drop-shadow-lg" stroke-width="2.5" />
                        </button>
                    </div>

                    <!-- Action popup (on button click) -->
                    <Transition name="action-pop">
                        <div
                            v-if="actionPopupVisible && actionPopup"
                            class="discover-action-popup fixed left-4 right-20 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 z-40 flex justify-start sm:justify-center pointer-events-none"
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

                <!-- Out of likes CTA (freemium) -->
                <div v-if="atLikesLimit" class="absolute top-4 left-4 right-24 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 z-20 px-4 py-3 rounded-2xl bg-amber-500/95 text-white text-sm font-medium shadow-lg flex items-center justify-center gap-2">
                    <span>Out of likes for today.</span>
                    <a href="/plus" class="underline font-semibold">Upgrade to Plus</a>
                </div>
                <!-- Swipe hint: bottom left -->
                <div v-else-if="!actionPopupVisible" class="absolute bottom-6 left-4 right-24 sm:right-1/2 z-20 flex justify-start sm:justify-center pointer-events-none">
                    <span class="px-3 py-1.5 rounded-full bg-black/30 backdrop-blur text-white/90 text-xs font-medium">Swipe or tap buttons</span>
                </div>
            </template>

            <div v-else class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-b from-background to-muted px-6">
                <div class="w-24 h-24 rounded-full bg-primary flex items-center justify-center shadow-xl mb-4">
                    <Heart class="w-12 h-12 text-primary-foreground fill-current" />
                </div>
                <h3 class="text-xl font-bold text-gray-900">No more profiles</h3>
                <p class="text-gray-600 mt-2 text-center">Check back later for new matches.</p>
            </div>
            </div>
            </template>
        </main>

        <!-- Match modal: Congrats! / You Have A Match! – uses app motif (blue/cyan) -->
        <Teleport to="body">
            <Transition name="match-modal">
                <div
                    v-if="matchModalUser"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gradient-to-br from-blue-50/95 to-cyan-50/95 backdrop-blur-sm"
                    @click.self="closeMatchModal"
                >
                    <div
                        class="match-modal-card rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden text-center bg-gradient-to-br from-blue-600 to-cyan-500"
                        @click.stop
                    >
                        <!-- Top: decorative hearts + squiggles + Congrats! -->
                        <div class="relative pt-10 pb-2">
                            <span class="match-modal-heart match-modal-heart-1">♥</span>
                            <span class="match-modal-heart match-modal-heart-2">♥</span>
                            <span class="match-modal-heart match-modal-heart-3">♥</span>
                            <span class="match-modal-heart match-modal-heart-4">♥</span>
                            <span class="match-modal-squiggle match-modal-squiggle-1">～</span>
                            <span class="match-modal-squiggle match-modal-squiggle-2">～</span>
                            <h2 class="text-4xl font-black text-white tracking-tight">Congrats!</h2>
                        </div>
                        <!-- Two profile pictures side by side -->
                        <div class="flex justify-center items-center gap-4 px-8 py-6">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-white/90 shadow-xl flex items-center justify-center text-xl font-bold bg-white/20 text-white shrink-0">
                                <img
                                    v-if="props.user?.profile_picture"
                                    :src="profilePictureSrc(props.user?.profile_picture)"
                                    :alt="props.user?.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ (props.user?.display_name || 'You').charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-white/90 shadow-xl flex items-center justify-center text-xl font-bold bg-white/20 text-white shrink-0">
                                <img
                                    v-if="matchModalUser.profile_picture"
                                    :src="profilePictureSrc(matchModalUser.profile_picture)"
                                    :alt="displayName(matchModalUser)"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ displayName(matchModalUser).charAt(0).toUpperCase() }}</span>
                            </div>
                        </div>
                        <p class="text-lg sm:text-xl font-bold text-white px-4">You Have A Match!</p>
                        <!-- Send Message: white CTA to match other pages -->
                        <div class="px-6 pb-8 pt-6">
                            <button
                                type="button"
                                @click="goToChatFromMatch"
                                class="w-full py-3.5 rounded-2xl bg-white text-primary font-bold text-base hover:bg-blue-50 active:scale-[0.98] transition-all shadow-lg"
                            >
                                Send Message
                            </button>
                            <button
                                type="button"
                                @click="closeMatchModal"
                                class="mt-3 text-white/80 text-sm font-medium hover:text-white transition-colors"
                            >
                                Maybe later
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <BottomNav active-tab="likeyou" />
    </div>
</template>

<style scoped>
/* Reserve space for bottom nav + safe area so content doesn't sit under it */
.discover-main {
    padding-bottom: 5rem;
    padding-bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
}

/* Wrapper that takes flex height so absolute card stack has a defined containing block */
.discover-fill {
    position: relative;
}

/* Card stack fills the wrapper so profile photo and details are visible */
.discover-card-stack {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

/* Photo layer fills the card so image/placeholder and text overlay are visible */
.discover-photo-layer {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
.discover-card-overlay {
    z-index: 1;
    pointer-events: none;
}
.discover-card-img {
    z-index: 0;
}

/* Ensure card info (name, interests, course) is on top and always visible */
.discover-card-info {
    bottom: calc(4.5rem + env(safe-area-inset-bottom, 0px));
    padding-bottom: 1.25rem;
    padding-right: calc(4.5rem + env(safe-area-inset-right, 0px));
    z-index: 10;
    isolation: isolate;
}

/* Vertical action bar on the right, centered vertically, above bottom nav */
.discover-actions {
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    right: 0.75rem;
    right: max(0.75rem, env(safe-area-inset-right, 0px));
    z-index: 40;
}

/* Card info: padding so name/tags don't sit under the vertical button strip or bottom nav */
.discover-card-info {
    padding-bottom: 5rem;
    padding-bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
    padding-right: 5rem;
    padding-right: calc(4.5rem + env(safe-area-inset-right, 0px));
}

/* Action popup: above bottom nav, not under vertical buttons */
.discover-action-popup {
    bottom: 5rem;
    bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
}

/* Match modal: card scale target for transition (colors from Tailwind motif) */
/* Floating decorative hearts and squiggles */
.match-modal-heart,
.match-modal-squiggle {
    position: absolute;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.25rem;
    pointer-events: none;
    animation: match-float 3s ease-in-out infinite;
}
.match-modal-squiggle {
    font-size: 1.5rem;
    opacity: 0.7;
}
.match-modal-heart-1 { top: 0.5rem; left: 12%; animation-delay: 0s; }
.match-modal-heart-2 { top: 0.25rem; right: 15%; animation-delay: 0.4s; }
.match-modal-heart-3 { top: 2.5rem; left: 8%; animation-delay: 0.8s; }
.match-modal-heart-4 { top: 2.25rem; right: 10%; animation-delay: 0.2s; }
.match-modal-squiggle-1 { top: 0.75rem; left: 38%; animation-delay: 0.3s; }
.match-modal-squiggle-2 { top: 0.5rem; right: 40%; animation-delay: 0.6s; }
@keyframes match-float {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.9; }
    50% { transform: translateY(-6px) scale(1.05); opacity: 1; }
}

.match-modal-enter-active,
.match-modal-leave-active {
    transition: opacity 0.25s ease;
}
.match-modal-enter-from,
.match-modal-leave-to {
    opacity: 0;
}
.match-modal-enter-active .match-modal-card,
.match-modal-leave-active .match-modal-card {
    transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
}
.match-modal-enter-from .match-modal-card {
    transform: scale(0.9);
}
.match-modal-enter-to .match-modal-card {
    transform: scale(1);
}
.match-modal-leave-to .match-modal-card {
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

@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.85;
        transform: scale(1.03);
    }
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}

/* Enhanced hover effects for bigger buttons */
.action-btn {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.action-btn:hover:not(:disabled) {
    filter: brightness(1.1);
}

.action-btn:active:not(:disabled) {
    transform: scale(0.92) !important;
}

@media (prefers-reduced-motion: reduce) {
    .btn-intro,
    .btn-float,
    .btn-pop,
    .btn-glow,
    .animate-pulse-slow {
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

/* Match-back & Matches Card Animations */
.matchback-card,
.matches-card {
    animation: card-slide-in 0.4s ease-out both;
    animation-delay: calc(0.05s * var(--index, 0));
}

@keyframes card-slide-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .matchback-card,
    .matches-card {
        animation: none;
    }
}
</style>
