<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, MessageCircle, Heart } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';

type MutualMatchUser = {
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
};

const list = ref<MutualMatchUser[]>([]);
const loading = ref(true);
const loadingMore = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);

async function fetchMutual(page = 1) {
    if (page > 1) loadingMore.value = true;
    else loading.value = true;
    try {
        const res = await fetch(`/api/matchmaking/mutual?page=${page}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        const items = (data.data ?? []) as MutualMatchUser[];
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

function openProfile(id: number) {
    router.visit(`/profile/${id}`);
}

function openChat(userId: number) {
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

const goBack = () => router.visit('/browse');

onMounted(() => fetchMutual(1));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head title="Matches - NEMSU Match" />

        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900">Matches</h1>
                <span class="w-10" />
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <p class="text-sm text-gray-600 mb-4">You and these people have liked each other. Say hello!</p>

            <div v-if="loading && list.length === 0" class="flex justify-center py-12">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="list.length === 0" class="py-12 text-center text-gray-500">
                <Heart class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="font-medium">No matches yet</p>
                <p class="text-sm mt-1">When you and someone like each other, they’ll appear here.</p>
                <button
                    type="button"
                    @click="router.visit('/like-you?tab=match_back')"
                    class="mt-4 px-4 py-2.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold"
                >
                    Match back
                </button>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="user in list"
                    :key="user.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                >
                    <div class="flex gap-4 p-4 items-center">
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
                                <p v-if="user.matched_at" class="text-xs text-gray-400 mt-0.5">
                                    Matched {{ formatMatchedAt(user.matched_at) }}
                                </p>
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="openChat(user.id)"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 shrink-0"
                        >
                            <MessageCircle class="w-4 h-4" />
                            Message
                        </button>
                    </div>
                </li>
            </ul>

            <div v-if="currentPage < lastPage && !loading" class="mt-4 flex justify-center">
                <button
                    type="button"
                    :disabled="loadingMore"
                    @click="fetchMutual(currentPage + 1)"
                    class="px-4 py-2 rounded-full border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                    {{ loadingMore ? 'Loading…' : 'Load more' }}
                </button>
            </div>
        </div>

        <BottomNav />
    </div>
</template>
