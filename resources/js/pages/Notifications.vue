<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Bell, ChevronLeft, MoreVertical } from 'lucide-vue-next';
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
        intent?: string;
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
    if (sec < 3600) return `${Math.floor(sec / 60)} mins ago`;
    if (sec < 86400) return `${Math.floor(sec / 3600)} hours ago`;
    if (sec < 86400 * 2) return '1 day ago';
    if (sec < 86400 * 7) return `${Math.floor(sec / 86400)} days ago`;
    return `${Math.floor(sec / 86400)} days ago`;
}

/** Date as DD/MM/YYYY */
function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function isToday(dateStr: string): boolean {
    const d = new Date(dateStr);
    const now = new Date();
    return d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
}

const showOptionsMenu = ref(false);

/** Action line with @username (e.g. "@aanya followed you") */
function actionLine(n: NotificationItem): string {
    const handle = n.from_user?.display_name || n.from_user?.fullname || 'someone';
    const atName = handle.startsWith('@') ? handle : `@${handle}`;
    const d = n.data ?? {};
    switch (n.type) {
        case 'comment':
            if (d.is_reply_to_you) return `${atName} replied to your comment`;
            if (d.is_reply) return `${atName} replied to a comment`;
            return `${atName} commented on`;
        case 'like':
            return `${atName} loved your photo`;
        case 'follow':
            return `${atName} followed you`;
        case 'comment_like':
            return `${atName} liked your comment`;
        case 'message_request':
            return `${atName} requested you`;
        case 'message_request_accepted':
            return `${atName} accepted your request`;
        case 'match_dating':
            return `${atName} sent you a heart match`;
        case 'match_friend':
            return `${atName} sent you a smile match`;
        case 'match_study_buddy':
            return `${atName} wants to be your study buddy`;
        case 'mutual_match':
            return `You and ${atName} matched!`;
        case 'high_compatibility_match': {
            const score = (d as { compatibility_score?: number }).compatibility_score;
            const pct = score != null ? `${score}%` : '70%+';
            return `${atName} has you as a ${pct} match!`;
        }
        default:
            return `${atName} interacted with you`;
    }
}

const todayNotifications = computed(() => notifications.value.filter((n) => isToday(n.created_at)));
const previousNotifications = computed(() => notifications.value.filter((n) => !isToday(n.created_at)));

async function acceptMessageRequest(n: NotificationItem) {
    const id = n.notifiable_type === 'message_request' ? n.notifiable_id : null;
    if (id == null) return;
    try {
        const res = await fetch(`/api/message-requests/${id}/accept`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            markAsRead(n);
            const convId = data.conversation?.id;
            if (convId) router.visit(`/chat?conversation=${convId}`);
            else router.visit('/chat');
        }
    } catch (e) {
        console.error(e);
    }
}

async function declineMessageRequest(n: NotificationItem) {
    const id = n.notifiable_type === 'message_request' ? n.notifiable_id : null;
    if (id == null) return;
    try {
        await fetch(`/api/message-requests/${id}/decline`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        markAsRead(n);
    } catch (e) {
        console.error(e);
    }
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
    if (n.type === 'match_dating' || n.type === 'match_friend' || n.type === 'match_study_buddy') {
        router.visit('/like-you?tab=match_back');
        return;
    }
    if (n.type === 'mutual_match') {
        router.visit(`/like-you?tab=matches&show_match=${n.from_user_id}`);
        return;
    }
    if (n.type === 'high_compatibility_match') {
        router.visit('/like-you?tab=discover');
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
        router.visit('/browse');
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
        case 'match_dating':
            return `${name} sent you a heart match`;
        case 'match_friend':
            return `${name} sent you a smile match`;
        case 'match_study_buddy':
            return `${name} wants to be your study buddy`;
        case 'mutual_match':
            return `It's a match! You and ${name} matched.`;
        case 'high_compatibility_match': {
            const score = (d as { compatibility_score?: number }).compatibility_score;
            const pct = score != null ? `${score}%` : '70%+';
            return `${name} has you as a ${pct} match!`;
        }
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
        case 'match_dating':
            return 'Heart match';
        case 'match_friend':
            return 'Smile match';
        case 'match_study_buddy':
            return 'Study buddy';
        case 'mutual_match':
            return 'New match';
        case 'high_compatibility_match':
            return 'High match';
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
        case 'match_dating':
            return Heart;
        case 'match_friend':
            return Smile;
        case 'match_study_buddy':
            return BookOpen;
        case 'mutual_match':
        case 'high_compatibility_match':
            return Heart;
        case 'message':
        case 'message_request':
        case 'message_request_accepted':
            return MessageCircle;
        default:
            return Bell;
    }
}

const goBack = () => router.visit('/browse');

function onDocumentClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('.notification-options-menu')) showOptionsMenu.value = false;
}
onMounted(() => {
    fetchNotifications();
    document.addEventListener('click', onDocumentClick);
});
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div class="min-h-screen bg-gray-100 pb-20">
        <Head title="Notification - NEMSU Match" />

        <!-- Header: back | Notification | options -->
        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900">Notification</h1>
                <div class="relative notification-options-menu">
                    <button
                        type="button"
                        @click.stop="showOptionsMenu = !showOptionsMenu"
                        class="p-2 rounded-full hover:bg-gray-100 transition-colors"
                        aria-label="Options"
                    >
                        <MoreVertical class="w-6 h-6 text-gray-700" />
                    </button>
                    <div
                        v-if="showOptionsMenu"
                        class="absolute right-0 top-full mt-1 py-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200 z-50"
                    >
                        <button
                            v-if="notifications.some((n) => !n.read_at)"
                            type="button"
                            @click="markAllAsRead(); showOptionsMenu = false"
                            class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Mark all read
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="showOptionsMenu = false"
                            class="w-full px-4 py-2.5 text-left text-sm text-gray-500"
                        >
                            All caught up
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="notifications.length === 0" class="text-center py-12 text-gray-500">
                <Bell class="w-14 h-14 mx-auto mb-3 text-gray-300" />
                <p class="font-medium">No notifications yet</p>
                <p class="text-sm">Match requests, likes, and follows will show here.</p>
            </div>

            <template v-else>
                <!-- Today -->
                <section v-if="todayNotifications.length" class="mb-6">
                    <h2 class="text-sm font-bold text-gray-900 mb-2">Today</h2>
                    <div class="space-y-2">
                        <template v-for="n in todayNotifications" :key="n.id">
                            <!-- Request card: dark background + Decline / Accept -->
                            <div
                                v-if="n.type === 'message_request'"
                                class="flex gap-3 p-4 rounded-2xl bg-gray-800 text-white"
                            >
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-700 flex-shrink-0">
                                    <img
                                        v-if="n.from_user?.profile_picture"
                                        :src="profilePictureSrc(n.from_user.profile_picture)"
                                        :alt="n.from_user.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-lg">
                                        {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium">{{ actionLine(n) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(n.created_at) }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ timeAgo(n.created_at) }}</p>
                                    <div class="flex gap-2 mt-3">
                                        <button
                                            type="button"
                                            @click="declineMessageRequest(n)"
                                            class="px-4 py-2 rounded-xl bg-gray-600 text-white text-sm font-semibold hover:bg-gray-500"
                                        >
                                            Decline
                                        </button>
                                        <button
                                            type="button"
                                            @click="acceptMessageRequest(n)"
                                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-white text-sm font-semibold hover:opacity-95"
                                        >
                                            Accept
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Regular card -->
                            <button
                                v-else
                                type="button"
                                @click="goToNotification(n)"
                                class="w-full flex gap-3 p-4 rounded-2xl bg-white border border-gray-200 shadow-sm text-left hover:bg-gray-50 transition-colors"
                            >
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img
                                        v-if="n.from_user?.profile_picture"
                                        :src="profilePictureSrc(n.from_user.profile_picture)"
                                        :alt="n.from_user.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-500 font-bold text-lg">
                                        {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ actionLine(n) }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(n.created_at) }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span
                                        class="w-2.5 h-2.5 rounded-full"
                                        :class="n.read_at ? 'bg-gray-300' : 'bg-green-500'"
                                    />
                                    <span class="text-xs text-gray-500">{{ timeAgo(n.created_at) }}</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </section>

                <!-- Previous -->
                <section v-if="previousNotifications.length">
                    <h2 class="text-sm font-bold text-gray-900 mb-2">Previous</h2>
                    <div class="space-y-2">
                        <template v-for="n in previousNotifications" :key="n.id">
                            <div
                                v-if="n.type === 'message_request'"
                                class="flex gap-3 p-4 rounded-2xl bg-gray-800 text-white"
                            >
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-700 flex-shrink-0">
                                    <img
                                        v-if="n.from_user?.profile_picture"
                                        :src="profilePictureSrc(n.from_user.profile_picture)"
                                        :alt="n.from_user.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 font-bold text-lg">
                                        {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium">{{ actionLine(n) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(n.created_at) }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ timeAgo(n.created_at) }}</p>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" @click="declineMessageRequest(n)" class="px-4 py-2 rounded-xl bg-gray-600 text-white text-sm font-semibold hover:bg-gray-500">Decline</button>
                                        <button type="button" @click="acceptMessageRequest(n)" class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-white text-sm font-semibold hover:opacity-95">Accept</button>
                                    </div>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="goToNotification(n)"
                                class="w-full flex gap-3 p-4 rounded-2xl bg-white border border-gray-200 shadow-sm text-left hover:bg-gray-50 transition-colors"
                            >
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img
                                        v-if="n.from_user?.profile_picture"
                                        :src="profilePictureSrc(n.from_user.profile_picture)"
                                        :alt="n.from_user.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-500 font-bold text-lg">
                                        {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ actionLine(n) }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(n.created_at) }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="n.read_at ? 'bg-gray-300' : 'bg-green-500'" />
                                    <span class="text-xs text-gray-500">{{ timeAgo(n.created_at) }}</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </section>

                <div v-if="nextPageUrl && !loading" class="py-6 flex justify-center">
                    <button
                        type="button"
                        @click="loadMore"
                        :disabled="loadingMore"
                        class="px-6 py-2.5 rounded-full border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-50 disabled:opacity-50"
                    >
                        {{ loadingMore ? 'Loading...' : 'Load more' }}
                    </button>
                </div>
            </template>
        </div>

        <BottomNav />
    </div>
</template>
