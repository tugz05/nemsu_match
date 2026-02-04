<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { onClickOutside } from '@vueuse/core';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';

export interface SearchUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
    is_following: boolean;
}

const emit = defineEmits<{
    close: [];
}>();

const inputEl = ref<HTMLInputElement | null>(null);
const query = ref('');
const results = ref<SearchUser[]>([]);
const loading = ref(false);
const showDropdown = ref(false);
const dropdownEl = ref<HTMLElement | null>(null);
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

onClickOutside(dropdownEl, () => {
    showDropdown.value = false;
});

function openSearch() {
    showDropdown.value = true;
    setTimeout(() => inputEl.value?.focus(), 50);
}

function goToProfile(user: SearchUser) {
    showDropdown.value = false;
    query.value = '';
    results.value = [];
    router.visit(`/profile/${user.id}`);
}

async function toggleFollow(user: SearchUser, e: Event) {
    e.preventDefault();
    e.stopPropagation();
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
</script>

<template>
    <div ref="dropdownEl" class="relative flex-1 max-w-md">
        <!-- Trigger: search bar -->
        <button
            type="button"
            class="w-full flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-100 text-gray-500 text-left text-sm hover:bg-gray-200 transition-colors"
            @click="openSearch"
        >
            <Search class="w-4 h-4 shrink-0" />
            <span>Search profiles</span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="showDropdown"
            class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50 max-h-[70vh] flex flex-col"
        >
            <div class="p-2 border-b border-gray-100">
                <input
                    ref="inputEl"
                    v-model="query"
                    type="search"
                    placeholder="Search by name..."
                    autocomplete="off"
                    class="w-full px-3 py-2 bg-gray-100 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>
            <div class="overflow-y-auto flex-1 min-h-0">
                <div v-if="loading" class="flex justify-center py-6">
                    <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <div v-else-if="query.trim() && results.length === 0" class="py-6 text-center text-gray-500 text-sm">
                    No profiles found.
                </div>
                <div v-else-if="!query.trim()" class="py-6 text-center text-gray-500 text-sm">
                    Type to search by display name or full name.
                </div>
                <div v-else class="py-1">
                <button
                    type="button"
                    class="w-full text-left px-3 py-2.5 text-sm font-semibold text-blue-600 hover:bg-gray-50"
                    @click="router.visit(`/search?q=${encodeURIComponent(query.trim())}`); showDropdown = false"
                >
                    See all results for "{{ query.trim() }}"
                </button>
                <ul class="border-t border-gray-100">
                    <li
                        v-for="user in results"
                        :key="user.id"
                        class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer transition-colors"
                        @click="goToProfile(user)"
                    >
                        <div
                            class="w-9 h-9 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0 flex items-center justify-center"
                        >
                            <img
                                v-if="user.profile_picture"
                                :src="profilePictureSrc(user.profile_picture)"
                                :alt="displayName(user)"
                                class="w-full h-full object-cover"
                            />
                            <span v-else class="text-blue-600 font-bold text-sm">
                                {{ displayName(user).charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ displayName(user) }}</p>
                            <p v-if="user.fullname && user.display_name" class="text-xs text-gray-500 truncate">
                                {{ user.fullname }}
                            </p>
                        </div>
                        <button
                            type="button"
                            :disabled="followLoadingId === user.id"
                            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-colors disabled:opacity-50"
                            :class="user.is_following ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700'"
                            @click="(e) => toggleFollow(user, e)"
                        >
                            {{ followLoadingId === user.id ? '...' : (user.is_following ? 'Following' : 'Follow') }}
                        </button>
                    </li>
                </ul>
                </div>
            </div>
        </div>
    </div>
</template>
