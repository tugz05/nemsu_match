<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, ChevronLeft } from 'lucide-vue-next';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';

interface SearchUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
    is_following: boolean;
}

const props = defineProps<{
    q?: string;
}>();

const query = ref(props.q ?? '');
const results = ref<SearchUser[]>([]);
const loading = ref(false);
const followLoadingId = ref<number | null>(null);
const getCsrfToken = useCsrfToken();

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchResults() {
    const q = query.value.trim();
    if (!q) {
        results.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await fetch(
            `/api/users/search?${new URLSearchParams({ q })}`,
            {
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
            },
        );
        if (res.ok) {
            const data = await res.json();
            results.value = data.data ?? [];
        } else {
            results.value = [];
        }
    } catch (e) {
        results.value = [];
    } finally {
        loading.value = false;
    }
}

watch(query, (newQuery) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (!newQuery.trim()) {
        results.value = [];
        return;
    }
    debounceTimer = setTimeout(() => fetchResults(), 300);
});

onMounted(() => {
    if (query.value.trim()) fetchResults();
});

async function toggleFollow(user: SearchUser) {
    if (followLoadingId.value !== null) return;
    followLoadingId.value = user.id;
    try {
        const res = await fetch(`/api/users/${user.id}/follow`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            user.is_following = data.following;
        }
    } catch (e) {
        console.error(e);
    } finally {
        followLoadingId.value = null;
    }
}

function displayName(u: SearchUser): string {
    return u.display_name || u.fullname || 'User';
}

function goToProfile(user: SearchUser) {
    router.visit(`/profile/${user.id}`);
}

function goBack() {
    // From manual search, go back to Browse list.
    router.visit('/browse');
}
</script>

<template>
    <div class="min-h-screen bg-white pb-20">
        <Head title="Search profiles - NEMSU Match" />

        <!-- Top bar -->
        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="goBack"
                        class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors shrink-0"
                        aria-label="Back"
                    >
                        <ChevronLeft class="w-6 h-6 text-gray-700" />
                    </button>
                    <div class="flex-1 relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search by name..."
                            autocomplete="off"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-100 rounded-xl text-gray-900 placeholder-gray-500 outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>
            <div v-else-if="query.trim() && results.length === 0" class="py-12 text-center text-gray-500">
                <p class="font-medium">No profiles found for "{{ query }}"</p>
                <p class="text-sm mt-1">Try a different name</p>
            </div>
            <div v-else-if="!query.trim()" class="py-12 text-center text-gray-500">
                <Search class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="font-medium">Search profiles</p>
                <p class="text-sm mt-1">Enter a display name or full name</p>
            </div>
            <ul v-else class="divide-y divide-gray-100">
                <li
                    v-for="user in results"
                    :key="user.id"
                    class="flex items-center gap-3 py-3 cursor-pointer hover:bg-gray-50 -mx-2 px-2 rounded-xl transition-colors"
                    @click="goToProfile(user)"
                >
                    <div
                        class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0 flex items-center justify-center"
                    >
                        <img
                            v-if="user.profile_picture"
                            :src="profilePictureSrc(user.profile_picture)"
                            :alt="displayName(user)"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-blue-600 font-bold text-lg">
                            {{ displayName(user).charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ displayName(user) }}</p>
                        <p v-if="user.fullname && user.display_name" class="text-sm text-gray-500 truncate">
                            {{ user.fullname }}
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="followLoadingId === user.id"
                        class="shrink-0 px-4 py-2 rounded-full text-sm font-semibold transition-colors disabled:opacity-50"
                        :class="user.is_following ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700'"
                        @click.stop="toggleFollow(user)"
                    >
                        {{ followLoadingId === user.id ? '...' : (user.is_following ? 'Following' : 'Follow') }}
                    </button>
                </li>
            </ul>
        </div>

        <BottomNav active-tab="home" />
    </div>
</template>
