<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Heart, Megaphone } from 'lucide-vue-next';
import { useCsrfToken } from '@/composables/useCsrfToken';

/** Which tab is active: 'home' | 'announcements' | 'likeyou' | 'chat' | 'account' | null (none, e.g. on Notifications page) */
const props = defineProps<{
    activeTab?: 'home' | 'announcements' | 'likeyou' | 'chat' | 'account' | null;
    likeYouBadge?: number;
    /** Override chat badge; if not provided, fetches unread message count from API */
    chatBadge?: number;
    /** When true (e.g. on Home page), Home/LikeYou/Chat emit update:activeTab instead of navigating */
    interactive?: boolean;
}>();

const getCsrfToken = useCsrfToken();
const fetchedUnreadChatCount = ref(0);

async function fetchUnreadChatCount() {
    try {
        const res = await fetch('/api/conversations/unread-count', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            fetchedUnreadChatCount.value = data.count ?? 0;
        }
    } catch {
        // ignore
    }
}

/** Chat badge: use prop if provided, else fetched unread count */
const chatBadgeCount = computed(() => props.chatBadge ?? fetchedUnreadChatCount.value);

onMounted(() => fetchUnreadChatCount());

const emit = defineEmits<{
    'update:activeTab': [value: 'home' | 'announcements' | 'likeyou' | 'chat'];
}>();

function goHome() {
    if (props.interactive) emit('update:activeTab', 'home');
    else router.visit('/home');
}

function setActiveAndStay(tab: 'likeyou' | 'chat') {
    if (tab === 'chat') {
        router.visit('/chat');
        return;
    }
    if (tab === 'likeyou') {
        if (props.interactive) emit('update:activeTab', tab);
        else router.visit('/like-you');
        return;
    }
}

function goAnnouncements() {
    router.visit('/announcements');
}
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
        <div class="max-w-2xl mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <!-- Home -->
                <button
                    type="button"
                    @click="goHome"
                    class="flex flex-col items-center gap-1 py-2 px-4 transition-all"
                    :class="activeTab === 'home' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600'"
                >
                    <svg class="w-6 h-6" :fill="activeTab === 'home' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-xs font-semibold">Home</span>
                </button>

                <!-- Announcements -->
                <button
                    type="button"
                    @click="goAnnouncements"
                    class="flex flex-col items-center gap-1 py-2 px-4 transition-all"
                    :class="activeTab === 'announcements' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600'"
                >
                    <Megaphone class="w-6 h-6" />
                    <span class="text-xs font-semibold">News</span>
                </button>

                <!-- Discover (Matchmaking) -->
                <button
                    type="button"
                    @click="setActiveAndStay('likeyou')"
                    class="flex flex-col items-center gap-1 py-2 px-4 transition-all relative"
                    :class="activeTab === 'likeyou' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600'"
                >
                    <div class="relative">
                        <Heart class="w-6 h-6" :fill="activeTab === 'likeyou' ? 'currentColor' : 'none'" />
                        <span
                            v-if="(likeYouBadge ?? 3) > 0"
                            class="absolute -top-1 -right-2 w-5 h-5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse"
                        >
                            {{ (likeYouBadge ?? 3) > 99 ? '99+' : (likeYouBadge ?? 3) }}
                        </span>
                    </div>
                    <span class="text-xs font-semibold">Discover</span>
                </button>

                <!-- Chat -->
                <button
                    type="button"
                    @click="setActiveAndStay('chat')"
                    class="flex flex-col items-center gap-1 py-2 px-4 transition-all relative"
                    :class="activeTab === 'chat' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600'"
                >
                    <div class="relative">
                        <svg class="w-6 h-6" :fill="activeTab === 'chat' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span
                            v-if="chatBadgeCount > 0"
                            class="absolute -top-1 -right-2 w-5 h-5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse"
                        >
                            {{ chatBadgeCount > 99 ? '99+' : chatBadgeCount }}
                        </span>
                    </div>
                    <span class="text-xs font-semibold">Chat</span>
                </button>

                <!-- Account -->
                <button
                    type="button"
                    @click="router.visit('/account')"
                    class="flex flex-col items-center gap-1 py-2 px-4 transition-all"
                    :class="activeTab === 'account' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600'"
                >
                    <svg class="w-6 h-6" :fill="activeTab === 'account' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-xs font-semibold">Account</span>
                </button>
            </div>
        </div>
    </nav>
</template>
