<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, Heart, Smile, BookOpen, MessageCircle } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { BottomNav } from '@/components/feed';

type MatchedUserModal = {
    id: number;
    display_name?: string;
    fullname?: string;
    profile_picture?: string | null;
};

type WhoLikedMeUser = {
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
    their_intent: 'dating' | 'friend' | 'study_buddy';
    liked_at: string;
};

const props = defineProps<{
    user?: { id: number; display_name?: string; profile_picture?: string | null };
}>();

const getCsrfToken = useCsrfToken();
const list = ref<WhoLikedMeUser[]>([]);
const loading = ref(true);
const loadingMore = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const actionUserId = ref<number | null>(null);
const matchModalUser = ref<MatchedUserModal | null>(null);

function intentLabel(intent: string): string {
    switch (intent) {
        case 'dating': return 'Heart';
        case 'friend': return 'Smile';
        case 'study_buddy': return 'Study buddy';
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

async function fetchWhoLikedMe(page = 1) {
    if (page > 1) loadingMore.value = true;
    else loading.value = true;
    try {
        const res = await fetch(`/api/matchmaking/who-liked-me?page=${page}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const items = (data.data ?? []) as WhoLikedMeUser[];
        if (page === 1) {
            list.value = items;
        } else {
            list.value = [...list.value, ...items];
        }
        currentPage.value = data.current_page ?? page;
        lastPage.value = data.last_page ?? page;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function matchBack(user: WhoLikedMeUser) {
    actionUserId.value = user.id;
    try {
        const res = await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                target_user_id: user.id,
                intent: user.their_intent,
            }),
        });
        const data = await res.json();
        list.value = list.value.filter((u) => u.id !== user.id);
        if (data.matched && data.other_user) {
            matchModalUser.value = data.other_user;
        } else if (data.matched) {
            router.visit('/like-you?tab=matches');
        }
    } finally {
        actionUserId.value = null;
    }
}

function displayName(u: MatchedUserModal | null): string {
    return u?.display_name || u?.fullname || 'User';
}

function closeMatchModal() {
    matchModalUser.value = null;
    router.visit('/like-you?tab=matches');
}

function goToChatFromMatch() {
    if (matchModalUser.value) {
        router.visit(`/chat?user=${matchModalUser.value.id}`);
        matchModalUser.value = null;
    }
}

async function pass(user: WhoLikedMeUser) {
    actionUserId.value = user.id;
    try {
        await fetch('/api/matchmaking/action', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                target_user_id: user.id,
                intent: 'ignored',
            }),
        });
        list.value = list.value.filter((u) => u.id !== user.id);
    } finally {
        actionUserId.value = null;
    }
}

function openProfile(id: number) {
    router.visit(`/profile/${id}`);
}

const goBack = () => router.visit('/browse');

onMounted(() => fetchWhoLikedMe(1));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head title="Match back - NEMSU Match" />

        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900">Match back</h1>
                <span class="w-10" />
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <p class="text-sm text-gray-600 mb-4">People who want to match with you. Match back or pass.</p>

            <div v-if="loading && list.length === 0" class="flex justify-center py-12">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="list.length === 0" class="py-12 text-center text-gray-500">
                <Heart class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="font-medium">No one to match back yet</p>
                <p class="text-sm mt-1">When someone likes you, they’ll show up here.</p>
                <button
                    type="button"
                    @click="router.visit('/like-you')"
                    class="mt-4 px-4 py-2.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold"
                >
                    Discover
                </button>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="user in list"
                    :key="user.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                >
                    <div class="flex gap-4 p-4">
                        <button
                            type="button"
                            @click="openProfile(user.id)"
                            class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 ring-2 ring-white shadow"
                        >
                            <img
                                v-if="user.profile_picture"
                                :src="profilePictureSrc(user.profile_picture)"
                                :alt="user.display_name"
                                class="w-full h-full object-cover"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xl"
                            >
                                {{ (user.display_name || user.fullname || '?').charAt(0).toUpperCase() }}
                            </div>
                        </button>
                        <div class="flex-1 min-w-0">
                            <button
                                type="button"
                                @click="openProfile(user.id)"
                                class="text-left block w-full"
                            >
                                <p class="font-semibold text-gray-900 truncate">{{ user.fullname || user.display_name }}</p>
                                <p v-if="user.campus" class="text-xs text-gray-500 truncate">{{ user.campus }}</p>
                            </button>
                            <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                <component :is="intentIcon(user.their_intent)" class="w-3.5 h-3.5" />
                                Wants to match ({{ intentLabel(user.their_intent) }})
                            </p>
                            <div class="flex gap-2 mt-3">
                                <button
                                    type="button"
                                    :disabled="actionUserId === user.id"
                                    @click="pass(user)"
                                    class="px-4 py-2 rounded-xl bg-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-300 disabled:opacity-50"
                                >
                                    Pass
                                </button>
                                <button
                                    type="button"
                                    :disabled="actionUserId === user.id"
                                    @click="matchBack(user)"
                                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 disabled:opacity-50"
                                >
                                    {{ actionUserId === user.id ? '…' : 'Match back' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-if="currentPage < lastPage && !loading" class="mt-4 flex justify-center">
                <button
                    type="button"
                    :disabled="loadingMore"
                    @click="fetchWhoLikedMe(currentPage + 1)"
                    class="px-4 py-2 rounded-full border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                    {{ loadingMore ? 'Loading…' : 'Load more' }}
                </button>
            </div>
        </div>

        <!-- Match modal: same UI as Discover when they match -->
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
                        <div class="relative pt-10 pb-2">
                            <span class="match-modal-heart match-modal-heart-1">♥</span>
                            <span class="match-modal-heart match-modal-heart-2">♥</span>
                            <span class="match-modal-heart match-modal-heart-3">♥</span>
                            <span class="match-modal-heart match-modal-heart-4">♥</span>
                            <span class="match-modal-squiggle match-modal-squiggle-1">～</span>
                            <span class="match-modal-squiggle match-modal-squiggle-2">～</span>
                            <h2 class="text-4xl font-black text-white tracking-tight">Congrats!</h2>
                        </div>
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
                        <div class="px-6 pb-8 pt-6">
                            <button
                                type="button"
                                @click="goToChatFromMatch"
                                class="w-full py-3.5 rounded-2xl bg-white text-blue-600 font-bold text-base hover:bg-blue-50 active:scale-[0.98] transition-all shadow-lg"
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

        <BottomNav />
    </div>
</template>

<style scoped>
.match-modal-heart,
.match-modal-squiggle {
    position: absolute;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.25rem;
    pointer-events: none;
    animation: match-float 3s ease-in-out infinite;
}
.match-modal-squiggle { font-size: 1.5rem; opacity: 0.7; }
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
.match-modal-leave-active { transition: opacity 0.25s ease; }
.match-modal-enter-from,
.match-modal-leave-to { opacity: 0; }
.match-modal-enter-active .match-modal-card,
.match-modal-leave-active .match-modal-card { transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1); }
.match-modal-enter-from .match-modal-card { transform: scale(0.9); }
.match-modal-enter-to .match-modal-card { transform: scale(1); }
.match-modal-leave-to .match-modal-card { transform: scale(0.95); }
</style>
