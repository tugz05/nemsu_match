<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Megaphone, Plus, Pin } from 'lucide-vue-next';
import { BottomNav } from '@/components/feed';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';

type Announcement = {
    id: number;
    title: string;
    body: string;
    is_pinned: boolean;
    published_at: string | null;
    created_at: string | null;
    creator: null | { id: number; display_name: string; fullname: string; profile_picture: string | null };
};

const getCsrfToken = useCsrfToken();
const page = usePage();
const authUser = computed(() => (page.props as any)?.auth?.user ?? null);
const isAdmin = computed(() => Boolean(authUser.value?.is_admin));

const loading = ref(true);
const items = ref<Announcement[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);

// Admin create modal (simple scaffold so admins can create later)
const showCreate = ref(false);
const saving = ref(false);
const form = ref({ title: '', body: '', is_pinned: false, publish_now: true });

function formatDate(iso: string | null) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '';
    }
}

async function fetchAnnouncements(pageNum = 1) {
    loading.value = pageNum === 1;
    const res = await fetch(`/api/announcements?page=${pageNum}`, {
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
    });
    if (!res.ok) {
        loading.value = false;
        return;
    }
    const data = await res.json();
    const list = (data.data ?? []) as Announcement[];
    if (pageNum === 1) items.value = list;
    else items.value = [...items.value, ...list];
    currentPage.value = data.current_page ?? pageNum;
    lastPage.value = data.last_page ?? 1;
    loading.value = false;
}

async function loadMore() {
    if (currentPage.value >= lastPage.value) return;
    await fetchAnnouncements(currentPage.value + 1);
}

async function createAnnouncement() {
    if (!form.value.title.trim() || !form.value.body.trim() || saving.value) return;
    saving.value = true;
    try {
        const res = await fetch('/api/announcements', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify(form.value),
        });
        if (res.ok) {
            showCreate.value = false;
            form.value = { title: '', body: '', is_pinned: false, publish_now: true };
            await fetchAnnouncements(1);
        }
    } finally {
        saving.value = false;
    }
}

onMounted(() => fetchAnnouncements(1));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head title="Announcements - NEMSU Match" />

        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Megaphone class="w-6 h-6 text-blue-600" />
                    <h1 class="text-lg font-bold text-gray-900">Announcements</h1>
                </div>
                <button
                    v-if="isAdmin"
                    type="button"
                    @click="showCreate = true"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold text-sm hover:from-blue-700 hover:to-cyan-600 transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    New
                </button>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 py-4 space-y-3">
            <div v-if="loading" class="flex items-center justify-center py-16">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="items.length === 0" class="bg-white rounded-2xl shadow-lg p-8 text-center text-gray-600">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center mx-auto mb-3">
                    <Megaphone class="w-8 h-8 text-blue-600" />
                </div>
                <p class="font-semibold text-gray-900">No announcements yet</p>
                <p class="text-sm mt-1">Admins can post announcements here soon.</p>
            </div>

            <article
                v-for="a in items"
                :key="a.id"
                class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden"
            >
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Pin v-if="a.is_pinned" class="w-4 h-4 text-amber-500" />
                                <h2 class="font-bold text-gray-900 truncate">{{ a.title }}</h2>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ a.published_at ? formatDate(a.published_at) : (isAdmin ? 'Draft' : '') }}
                            </p>
                        </div>
                        <div v-if="a.creator" class="flex items-center gap-2 shrink-0">
                            <div class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100">
                                <img
                                    v-if="a.creator.profile_picture"
                                    :src="profilePictureSrc(a.creator.profile_picture)"
                                    :alt="a.creator.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xs">
                                    {{ (a.creator.display_name || '?').charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">{{ a.creator.display_name }}</span>
                        </div>
                    </div>

                    <p class="text-sm text-gray-700 mt-3 whitespace-pre-line">{{ a.body }}</p>
                </div>
            </article>

            <div v-if="items.length > 0 && currentPage < lastPage" class="flex justify-center pt-2">
                <button
                    type="button"
                    @click="loadMore"
                    class="px-4 py-2 rounded-xl bg-white/80 border border-gray-200 text-gray-700 font-semibold text-sm hover:bg-white"
                >
                    Load more
                </button>
            </div>
        </main>

        <!-- Create modal (admin only) -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showCreate = false">
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900">New announcement</h3>
                        <button type="button" class="text-gray-500 hover:text-gray-700" @click="showCreate = false">✕</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Title"
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 outline-none"
                        />
                        <textarea
                            v-model="form.body"
                            rows="5"
                            placeholder="Write your announcement..."
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-500 outline-none resize-none"
                        />
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_pinned" type="checkbox" class="rounded border-gray-300" />
                            Pin this announcement
                        </label>
                    </div>
                    <div class="p-5 border-t border-gray-100 flex gap-3 justify-end">
                        <button type="button" class="px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold" @click="showCreate = false">Cancel</button>
                        <button
                            type="button"
                            :disabled="saving"
                            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold disabled:opacity-50"
                            @click="createAnnouncement"
                        >
                            {{ saving ? 'Posting…' : 'Post' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <BottomNav active-tab="announcements" />
    </div>
</template>

