<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, MessageCircle, Heart, Send } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import type { Post } from '@/types';
import type { PostComment } from '@/types';

const props = defineProps<{
    open: boolean;
    post: Post | null;
    comments: PostComment[];
    loading: boolean;
    replyingToComment?: { id: number; user: { display_name: string; fullname: string } } | null;
}>();

const emit = defineEmits<{
    close: [];
    goToProfile: [userId: number];
    addComment: [content: string, parentId: number | null];
    toggleCommentLike: [comment: PostComment];
    setReplyingTo: [comment: PostComment];
    cancelReply: [];
}>();

const newComment = ref('');

watch(() => props.open, (open) => {
    if (!open) newComment.value = '';
});

function handleAddComment() {
    if (!newComment.value.trim()) return;
    const parentId = props.replyingToComment?.id ?? null;
    emit('addComment', newComment.value.trim(), parentId);
    newComment.value = '';
    emit('cancelReply');
}
</script>

<template>
    <div
        v-if="open && post"
        class="fixed inset-0 bg-black/50 z-[60] flex items-end sm:items-center justify-center px-3 pb-24 pt-6 sm:p-4 sm:pb-4"
        @click.self="emit('close')"
    >
        <div class="bg-white w-full max-w-xl sm:max-w-lg rounded-3xl shadow-2xl animate-slide-up max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900">Comments</h3>
                <button type="button" @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="emit('goToProfile', post.user.id); emit('close')"
                        class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0 hover:ring-2 hover:ring-blue-300 transition-all focus:outline-none"
                    >
                        <img
                            v-if="post.user.profile_picture"
                            :src="profilePictureSrc(post.user?.profile_picture)"
                            :alt="post.user.display_name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                            {{ post.user.display_name?.charAt(0).toUpperCase() }}
                        </div>
                    </button>
                    <button
                        type="button"
                        @click="emit('goToProfile', post.user.id); emit('close')"
                        class="flex-1 min-w-0 text-left hover:opacity-80 transition-opacity focus:outline-none"
                    >
                        <h4 class="font-semibold text-gray-900 text-sm">{{ post.user.display_name || post.user.fullname }}</h4>
                        <p class="text-gray-900 text-sm mt-1 line-clamp-2">{{ post.content }}</p>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="loading" class="flex justify-center py-8">
                    <div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>
                <div v-else-if="comments.length === 0" class="text-center py-8 text-gray-500">
                    <MessageCircle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm">No comments yet</p>
                    <p class="text-xs">Be the first to comment!</p>
                </div>
                <div v-else class="space-y-4">
                    <template v-for="comment in comments" :key="comment.id">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                                <img
                                    v-if="comment.user.profile_picture"
                                    :src="profilePictureSrc(comment.user?.profile_picture)"
                                    :alt="comment.user.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xs">
                                    {{ comment.user.display_name?.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-gray-100 rounded-2xl px-3 py-2">
                                    <h5 class="font-semibold text-gray-900 text-xs">{{ comment.user.display_name || comment.user.fullname }}</h5>
                                    <p class="text-gray-800 text-sm mt-0.5">{{ comment.content }}</p>
                                </div>
                                <div class="flex items-center gap-4 mt-1 px-3">
                                    <span class="text-xs text-gray-500">{{ comment.time_ago }}</span>
                                    <button
                                        type="button"
                                        @click="emit('toggleCommentLike', comment)"
                                        class="flex items-center gap-1 text-xs font-semibold transition-colors"
                                        :class="comment.is_liked_by_user ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600'"
                                    >
                                        <Heart class="w-3.5 h-3.5" :class="comment.is_liked_by_user ? 'fill-current' : ''" />
                                        <span>{{ comment.likes_count ?? 0 }}</span>
                                    </button>
                                    <button type="button" @click="emit('setReplyingTo', comment)" class="text-xs text-gray-500 hover:text-blue-600 font-semibold">
                                        Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-for="reply in (comment.replies || [])"
                            :key="reply.id"
                            class="flex gap-3 ml-11"
                        >
                            <div class="w-6 h-6 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0">
                                <img
                                    v-if="reply.user.profile_picture"
                                    :src="profilePictureSrc(reply.user?.profile_picture)"
                                    :alt="reply.user.display_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-[10px]">
                                    {{ reply.user.display_name?.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-gray-100 rounded-2xl px-3 py-2">
                                    <h5 class="font-semibold text-gray-900 text-xs">{{ reply.user.display_name || reply.user.fullname }}</h5>
                                    <p class="text-gray-800 text-sm mt-0.5">{{ reply.content }}</p>
                                </div>
                                <div class="flex items-center gap-4 mt-1 px-3">
                                    <span class="text-xs text-gray-500">{{ reply.time_ago }}</span>
                                    <button
                                        type="button"
                                        @click="emit('toggleCommentLike', reply)"
                                        class="flex items-center gap-1 text-xs font-semibold transition-colors"
                                        :class="reply.is_liked_by_user ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600'"
                                    >
                                        <Heart class="w-3.5 h-3.5" :class="reply.is_liked_by_user ? 'fill-current' : ''" />
                                        <span>{{ reply.likes_count ?? 0 }}</span>
                                    </button>
                                    <button type="button" @click="emit('setReplyingTo', reply)" class="text-xs text-gray-500 hover:text-blue-600 font-semibold">
                                        Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200 flex-shrink-0">
                <div v-if="props.replyingToComment" class="flex items-center justify-between mb-2 px-1">
                    <span class="text-xs text-gray-500">
                        Replying to <strong>{{ props.replyingToComment.user.display_name || props.replyingToComment.user.fullname }}</strong>
                    </span>
                    <button type="button" @click="emit('cancelReply')" class="text-xs text-blue-600 hover:underline">Cancel</button>
                </div>
                <div class="flex gap-2">
                    <input
                        v-model="newComment"
                        type="text"
                        :placeholder="props.replyingToComment ? 'Write a reply...' : 'Write a comment...'"
                        class="flex-1 px-4 py-2.5 bg-gray-100 rounded-full text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        @keydown.enter.prevent="handleAddComment"
                    />
                    <button
                        type="button"
                        @click="handleAddComment"
                        :disabled="!newComment.trim()"
                        class="p-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white rounded-full transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Send class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slide-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}
</style>
