<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Bell, ChevronLeft, MessageCircle, Heart, UserPlus } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';
import { useRealtimeNotifications } from '@/composables/useRealtimeNotifications';
import type { RealtimeNotificationPayload } from '@/composables/useRealtimeNotifications';

interface FromUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
}

interface NotificationItem {
    id: number;
    type: string;
    from_user_id: number;
    from_user: FromUser;
    notifiable_type: string;
    notifiable_id: number | null;
    data: {
        comment_id?: number;
        post_id?: number;
        conversation_id?: number;
        excerpt?: string;
        is_reply?: boolean;
        is_reply_to_you?: boolean;
    } | null;
    read_at: string | null;
    created_at: string;
}

const notifications = ref<NotificationItem[]>([]);
const loading = ref(true);
const loadingMore = ref(false);
const nextPageUrl = ref<string | null>(null);
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function toNotificationItem(p: RealtimeNotificationPayload): NotificationItem {
    return {
        id: p.id,
        type: p.type,
        from_user_id: p.from_user_id,
        from_user: p.from_user ?? { id: p.from_user_id, display_name: '', fullname: '', profile_picture: null },
        notifiable_type: p.notifiable_type,
        notifiable_id: p.notifiable_id,
        data: p.data,
        read_at: p.read_at,
        created_at: p.created_at,
    };
}

useRealtimeNotifications((payload) => {
    notifications.value = [toNotificationItem(payload), ...notifications.value];
});

/** Fetch first page: full history (read + unread), newest first */
async function fetchNotifications() {
    loading.value = true;
    nextPageUrl.value = null;
    try {
        const res = await fetch('/api/notifications?per_page=25', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            notifications.value = data.data ?? [];
            nextPageUrl.value = data.next_page_url ?? null;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

/** Load more history (next page) */
async function loadMore() {
    if (!nextPageUrl.value || loadingMore.value) return;
    loadingMore.value = true;
    try {
        const res = await fetch(nextPageUrl.value, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            const next = data.data ?? [];
            notifications.value = [...notifications.value, ...next];
            nextPageUrl.value = data.next_page_url ?? null;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingMore.value = false;
    }
}

function timeAgo(dateStr: string): string {
    const d = new Date(dateStr);
    const now = new Date();
    const sec = Math.floor((now.getTime() - d.getTime()) / 1000);
    if (sec < 60) return 'just now';
    if (sec < 3600) return `${Math.floor(sec / 60)}m ago`;
    if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
    if (sec < 604800) return `${Math.floor(sec / 86400)}d ago`;
    return d.toLocaleDateString();
}

async function markAsRead(n: NotificationItem) {
    if (n.read_at) return;
    try {
        await fetch(`/api/notifications/${n.id}/read`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        n.read_at = new Date().toISOString();
    } catch (e) {
        console.error(e);
    }
}

async function markAllAsRead() {
    try {
        await fetch('/api/notifications/read-all', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        notifications.value.forEach((n) => { n.read_at = n.read_at || new Date().toISOString(); });
    } catch (e) {
        console.error(e);
    }
}

function goToNotification(n: NotificationItem) {
    markAsRead(n);
    if (n.type === 'follow') {
        router.visit(`/profile/${n.from_user_id}`);
        return;
    }
    if (n.type === 'message' || n.type === 'message_request' || n.type === 'message_request_accepted') {
        const conversationId = n.notifiable_type === 'conversation' ? n.notifiable_id : n.data?.conversation_id;
        if (conversationId) router.visit(`/chat?conversation=${conversationId}`);
        else router.visit('/chat');
        return;
    }
    const postId = n.notifiable_type === 'post' ? n.notifiable_id : n.data?.post_id;
    const commentId = n.data?.comment_id;
    if (postId) {
        const url = commentId ? `/post/${postId}#comment-${commentId}` : `/post/${postId}`;
        router.visit(url);
    } else {
        router.visit('/home');
    }
}

function message(n: NotificationItem): string {
    const name = n.from_user?.display_name || n.from_user?.fullname || 'Someone';
    const d = n.data ?? {};
    switch (n.type) {
        case 'comment':
            if (d.is_reply_to_you) return `${name} replied to your comment`;
            if (d.is_reply) return `${name} replied to a comment on your post`;
            return `${name} commented on your post`;
        case 'like':
            return `${name} liked your post`;
        case 'follow':
            return `${name} started following you`;
        case 'comment_like':
            return `${name} liked your comment`;
        case 'message':
            return `${name} sent you a message`;
        case 'message_request':
            return `${name} wants to message you`;
        case 'message_request_accepted':
            return `${name} accepted your message request`;
        default:
            return `${name} interacted with you`;
    }
}

/** Short specification label (e.g. "Comment" / "Reply" / "Like") for consistency */
function specLabel(n: NotificationItem): string {
    const d = n.data ?? {};
    switch (n.type) {
        case 'comment':
            if (d.is_reply_to_you) return 'Reply to you';
            if (d.is_reply) return 'Reply';
            return 'Comment';
        case 'like':
            return 'Post like';
        case 'follow':
            return 'New follower';
        case 'comment_like':
            return 'Comment like';
        case 'message':
            return 'Message';
        case 'message_request':
            return 'Request';
        case 'message_request_accepted':
            return 'Accepted';
        default:
            return 'Activity';
    }
}

function icon(n: NotificationItem) {
    switch (n.type) {
        case 'comment':
        case 'comment_like':
            return MessageCircle;
        case 'like':
            return Heart;
        case 'follow':
            return UserPlus;
        case 'message':
        case 'message_request':
        case 'message_request_accepted':
            return MessageCircle;
        default:
            return Bell;
    }
}

const goBack = () => router.visit('/home');

onMounted(fetchNotifications);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head title="Notifications - NEMSU Match" />

        <!-- Top bar -->
        <div class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900">Notifications</h1>
                <button
                    v-if="notifications.some((n) => !n.read_at)"
                    @click="markAllAsRead"
                    class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                >
                    Mark all read
                </button>
                <span v-else class="w-16" />
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="notifications.length === 0" class="text-center py-12 text-gray-500">
                <Bell class="w-14 h-14 mx-auto mb-3 text-gray-300" />
                <p class="font-medium">No notifications yet</p>
                <p class="text-sm">Comments, likes, and new followers will show here.</p>
            </div>

            <template v-else>
                <p class="text-xs text-gray-500 mb-2 px-1">All activity — read and unread</p>
                <div class="space-y-1">
                <button
                    v-for="n in notifications"
                    :key="n.id"
                    type="button"
                    @click="goToNotification(n)"
                    class="w-full flex gap-3 p-4 rounded-2xl text-left transition-colors"
                    :class="n.read_at ? 'bg-white/60 hover:bg-white' : 'bg-blue-50/80 hover:bg-blue-50'"
                >
                    <div class="w-11 h-11 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                        <img
                            v-if="n.from_user?.profile_picture"
                            :src="profilePictureSrc(n.from_user.profile_picture)"
                            :alt="n.from_user.display_name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold">
                            {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 font-medium">{{ message(n) }}</p>
                        <span class="inline-block mt-1 text-[10px] font-medium uppercase tracking-wide rounded px-1.5 py-0.5"
                            :class="n.type === 'like' || n.type === 'comment_like' ? 'text-pink-600 bg-pink-50' : n.type === 'follow' ? 'text-blue-600 bg-blue-50' : 'text-cyan-600 bg-cyan-50'">
                            {{ specLabel(n) }}
                        </span>
                        <p v-if="n.data?.excerpt" class="text-xs text-gray-500 mt-1 truncate">"{{ n.data.excerpt }}"</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(n.created_at) }}</p>
                    </div>
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="n.type === 'like' || n.type === 'comment_like' ? 'bg-pink-100 text-pink-600' : n.type === 'follow' ? 'bg-blue-100 text-blue-600' : 'bg-cyan-100 text-cyan-600'">
                        <component :is="icon(n)" class="w-5 h-5" />
                    </div>
                </button>
            </div>

                <!-- Load more history -->
                <div v-if="nextPageUrl && !loading" class="py-6 flex justify-center">
                    <button
                        type="button"
                        @click="loadMore"
                        :disabled="loadingMore"
                        class="px-6 py-2.5 rounded-full border-2 border-blue-600 text-blue-600 font-semibold text-sm hover:bg-blue-50 disabled:opacity-50 transition-colors"
                    >
                        {{ loadingMore ? 'Loading...' : 'Load more' }}
                    </button>
                </div>
            </template>
        </div>

        <BottomNav />
    </div>
</template>
