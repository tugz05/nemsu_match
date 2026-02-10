<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';

interface NotificationPreview {
    id: number;
    type: string;
    from_user_id: number;
    from_user: { display_name?: string; fullname?: string; profile_picture?: string | null };
    read_at: string | null;
    notifiable_type?: string;
    notifiable_id?: number | null;
    data?: { excerpt?: string; is_reply?: boolean; is_reply_to_you?: boolean; post_id?: number; comment_id?: number; conversation_id?: number };
}

/** Default route for the full notifications page (See all). */
const NOTIFICATIONS_PAGE_ROUTE = '/notifications';

const props = withDefaults(
    defineProps<{
        open: boolean;
        unreadCount: number;
        /** Route when notification has no specific target (e.g. /browse when Home is disabled) */
        fallbackRoute?: string;
        /** Route for "See all" link to the full notifications page */
        notificationsRoute?: string;
    }>(),
    { fallbackRoute: '/home', notificationsRoute: NOTIFICATIONS_PAGE_ROUTE }
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:unreadCount': [value: number];
}>();

const list = ref<NotificationPreview[]>([]);
const loading = ref(false);
const getCsrfToken = useCsrfToken();

function message(n: NotificationPreview): string {
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

async function fetchPreview() {
    loading.value = true;
    try {
        const res = await fetch('/api/notifications?per_page=15', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            const all = data.data ?? [];
            list.value = all.filter((n: NotificationPreview) => !n.read_at).slice(0, 8);
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

watch(() => props.open, (open) => {
    if (open) fetchPreview();
});

async function onItemClick(n: NotificationPreview) {
    emit('update:open', false);
    if (n.type === 'follow') {
        router.visit(`/profile/${n.from_user_id}`);
    } else if (n.type === 'match_dating' || n.type === 'match_friend' || n.type === 'match_study_buddy') {
        router.visit('/like-you?tab=match_back');
    } else if (n.type === 'mutual_match') {
        router.visit(`/like-you?tab=matches&show_match=${n.from_user_id}`);
    } else if (n.type === 'high_compatibility_match') {
        router.visit('/like-you?tab=discover');
    } else if (n.type === 'message' || n.type === 'message_request' || n.type === 'message_request_accepted') {
        const conversationId = n.notifiable_type === 'conversation' ? n.notifiable_id : n.data?.conversation_id;
        if (conversationId) router.visit(`/chat?conversation=${conversationId}`);
        else router.visit('/chat');
    } else {
        const postId = n.notifiable_type === 'post' ? n.notifiable_id : n.data?.post_id;
        const commentId = n.data?.comment_id;
        if (postId) {
            const url = commentId ? `/post/${postId}#comment-${commentId}` : `/post/${postId}`;
            router.visit(url);
        } else {
            router.visit(props.fallbackRoute);
        }
    }
    if (!n.read_at) {
        emit('update:unreadCount', Math.max(0, props.unreadCount - 1));
        await fetch(`/api/notifications/${n.id}/read`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        }).catch(() => {});
    }
}

function goToAll() {
    emit('update:open', false);
    router.visit(props.notificationsRoute);
}
</script>

<template>
    <div class="relative">
        <slot name="trigger" :open="open" :unread-count="unreadCount" :toggle="() => emit('update:open', !open)" />
        <div
            v-if="open"
            class="absolute right-0 top-full mt-2 w-[320px] max-h-[70vh] overflow-hidden bg-white rounded-2xl shadow-xl border border-gray-200 z-50 flex flex-col"
        >
            <div class="p-3 border-b border-gray-100 flex items-center justify-between">
                <span class="font-semibold text-gray-900">Notifications</span>
                <button type="button" @click="goToAll" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                    See all
                </button>
            </div>
            <div class="overflow-y-auto flex-1 min-h-0">
                <div v-if="loading" class="flex justify-center py-8">
                    <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <template v-else-if="list.length === 0">
                    <div class="py-8 text-center text-gray-500 text-sm">No unread notifications</div>
                </template>
                <template v-else>
                    <button
                        v-for="n in list"
                        :key="n.id"
                        type="button"
                        @click="onItemClick(n)"
                        class="w-full flex gap-3 p-3 text-left hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0"
                    >
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                            <img
                                v-if="n.from_user?.profile_picture"
                                :src="profilePictureSrc(n.from_user?.profile_picture)"
                                :alt="n.from_user.display_name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                {{ (n.from_user?.display_name || n.from_user?.fullname || '?').charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium leading-snug">{{ message(n) }}</p>
                            <p v-if="n.data?.excerpt" class="text-xs text-gray-500 mt-0.5 truncate">{{ n.data.excerpt }}</p>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
