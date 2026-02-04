<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, Heart, MessageCircle, Send } from 'lucide-vue-next';
import type { Post, PostComment } from '@/types';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav, FullscreenImageViewer, PostCard } from '@/components/feed';

const props = defineProps<{
    postId: number;
    commentId?: string | null;
}>();

const getCsrfToken = useCsrfToken();
const post = ref<Post | null>(null);
const loading = ref(true);
const loadError = ref(false);
const comments = ref<PostComment[]>([]);
const loadingComments = ref(false);
const newComment = ref('');
const replyingToComment = ref<{ id: number; user: { display_name: string; fullname: string } } | null>(null);
const showPostMenu = ref(false);
const followLoading = ref(false);
const fullscreenImages = ref<string[]>([]);
const fullscreenIndex = ref(0);
const showFullscreenImage = ref(false);

/** Comment ID to scroll to: from props or from hash #comment-123 */
const anchorCommentId = ref<string | null>(null);

function getPostImages(p: Post): string[] {
    if (p.images_list?.length) return p.images_list;
    if (Array.isArray(p.images) && p.images.length) return p.images;
    if (p.image) return [p.image];
    return [];
}

function isOwnPost(p: Post) {
    return !!p.is_own_post || p.user_id === (post.value?.user_id ?? 0);
}

async function fetchPost() {
    loading.value = true;
    loadError.value = false;
    try {
        const res = await fetch(`/api/posts/${props.postId}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            post.value = data.post ?? null;
        } else {
            loadError.value = true;
        }
    } catch (e) {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

async function fetchComments() {
    if (!post.value) return;
    loadingComments.value = true;
    try {
        const res = await fetch(`/api/posts/${post.value.id}/comments`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) comments.value = await res.json();
    } catch (e) {
        console.error(e);
    } finally {
        loadingComments.value = false;
    }
}

function scrollToComment(id: string) {
    nextTick(() => {
        const el = document.getElementById(`comment-${id}`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
            setTimeout(() => el.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2'), 2000);
        }
    });
}

async function addComment(content: string, parentId: number | null) {
    if (!post.value || !content.trim()) return;
    try {
        const res = await fetch(`/api/posts/${post.value.id}/comment`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ content: content.trim(), parent_id: parentId }),
        });
        if (res.ok) {
            const data = await res.json();
            const newC = data.comment;
            if (parentId) {
                const top = comments.value.find((c) => c.id === parentId);
                if (!top) {
                    for (const c of comments.value) {
                        const inReplies = (c.replies || []).find((r) => r.id === parentId);
                        if (inReplies) {
                            (c.replies || []).push(newC);
                            break;
                        }
                    }
                } else {
                    if (!top.replies) top.replies = [];
                    top.replies.push(newC);
                }
            } else {
                comments.value.unshift(newC);
            }
            post.value.comments_count++;
            replyingToComment.value = null;
            newComment.value = '';
        }
    } catch (e) {
        console.error(e);
    }
}

async function toggleCommentLike(comment: PostComment) {
    if (!post.value) return;
    try {
        const res = await fetch(`/api/posts/${post.value.id}/comments/${comment.id}/like`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            comment.is_liked_by_user = data.liked;
            comment.likes_count = data.likes_count;
        }
    } catch (e) {
        console.error(e);
    }
}

async function toggleLike() {
    if (!post.value) return;
    try {
        const res = await fetch(`/api/posts/${post.value.id}/like`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            post.value.is_liked_by_user = data.liked;
            post.value.likes_count = data.likes_count;
        }
    } catch (e) {
        console.error(e);
    }
}

async function toggleFollow() {
    if (!post.value || isOwnPost(post.value)) return;
    followLoading.value = true;
    try {
        const res = await fetch(`/api/users/${post.value.user.id}/follow`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            post.value.is_followed_by_user = data.following;
        }
    } catch (e) {
        console.error(e);
    } finally {
        followLoading.value = false;
    }
}

function openFullscreen(images: string[], index: number) {
    fullscreenImages.value = images;
    fullscreenIndex.value = index;
    showFullscreenImage.value = true;
}

function parseHashCommentId() {
    const hash = window.location.hash.slice(1);
    const m = /^comment-(\d+)$/.exec(hash);
    return m ? m[1] : null;
}

onMounted(async () => {
    await fetchPost();
    if (post.value) await fetchComments();
    anchorCommentId.value = props.commentId || parseHashCommentId();
    if (anchorCommentId.value) {
        await nextTick();
        await nextTick();
        scrollToComment(anchorCommentId.value);
    }
});

watch(
    () => comments.value.length,
    () => {
        if (anchorCommentId.value) {
            nextTick(() => scrollToComment(anchorCommentId.value!));
        }
    }
);

function goBack() {
    router.visit('/home');
}

async function handleDeletePost() {
    if (!post.value) return;
    try {
        const res = await fetch(`/api/posts/${post.value.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) goBack();
    } catch (e) {
        console.error(e);
    }
}
</script>

<template>
    <div class="min-h-screen bg-white pb-20">
        <Head :title="post ? `Post by ${post.user.display_name || post.user.fullname} - NEMSU Match` : 'Post - NEMSU Match'" />

        <!-- Top bar -->
        <div class="sticky top-0 z-40 bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
                <button type="button" @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900">Post</h1>
            </div>
        </div>

        <div class="max-w-2xl mx-auto">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="loadError || !post" class="text-center py-12 text-gray-500">
                <p class="font-medium">Post not found or you don't have access.</p>
                <button type="button" @click="goBack" class="mt-4 text-blue-600 font-semibold">Back to Home</button>
            </div>

            <template v-else>
                <PostCard
                    :post="post"
                    :is-own-post="isOwnPost(post)"
                    :is-post-owner="!!post.is_own_post"
                    :follow-loading="followLoading"
                    :menu-open="showPostMenu"
                    @go-to-profile="(userId) => router.visit(`/profile/${userId}`)"
                    @follow="toggleFollow"
                    @menu-toggle="showPostMenu = !showPostMenu"
                    @edit="() => router.visit('/home')"
                    @delete="handleDeletePost"
                    @report="() => {}"
                    @open-fullscreen="openFullscreen"
                    @like="toggleLike"
                    @open-comments="() => {}"
                    @repost="() => {}"
                    @share="() => {}"
                />

                <!-- Comments section -->
                <section class="border-t border-gray-200 px-4 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Comments ({{ post.comments_count }})</h2>

                    <div v-if="loadingComments" class="flex justify-center py-6">
                        <div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="comments.length === 0" class="py-6 text-center text-gray-500 text-sm">
                        <MessageCircle class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                        <p>No comments yet. Be the first!</p>
                    </div>
                    <div v-else class="space-y-4">
                        <template v-for="comment in comments" :key="comment.id">
                            <div :id="`comment-${comment.id}`" class="flex gap-3 rounded-xl p-2 transition-colors scroll-mt-24">
                                <button
                                    type="button"
                                    @click="router.visit(`/profile/${comment.user.id}`)"
                                    class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0"
                                >
                                    <img
                                        v-if="comment.user.profile_picture"
                                        :src="profilePictureSrc(comment.user?.profile_picture)"
                                        :alt="comment.user.display_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-xs">
                                        {{ comment.user.display_name?.charAt(0).toUpperCase() }}
                                    </div>
                                </button>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-100 rounded-2xl px-3 py-2">
                                        <h5 class="font-semibold text-gray-900 text-xs">{{ comment.user.display_name || comment.user.fullname }}</h5>
                                        <p class="text-gray-800 text-sm mt-0.5">{{ comment.content }}</p>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 px-3">
                                        <span class="text-xs text-gray-500">{{ comment.time_ago }}</span>
                                        <button
                                            type="button"
                                            @click="toggleCommentLike(comment)"
                                            class="flex items-center gap-1 text-xs font-semibold"
                                            :class="comment.is_liked_by_user ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600'"
                                        >
                                            <Heart class="w-3.5 h-3.5" :class="comment.is_liked_by_user ? 'fill-current' : ''" />
                                            {{ comment.likes_count ?? 0 }}
                                        </button>
                                        <button type="button" @click="replyingToComment = { id: comment.id, user: comment.user }" class="text-xs text-gray-500 hover:text-blue-600 font-semibold">
                                            Reply
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-for="reply in (comment.replies || [])"
                                :key="reply.id"
                                :id="`comment-${reply.id}`"
                                class="flex gap-3 ml-11 rounded-xl p-2 transition-colors scroll-mt-24"
                            >
                                <button
                                    type="button"
                                    @click="router.visit(`/profile/${reply.user.id}`)"
                                    class="w-6 h-6 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0"
                                >
                                    <img
                                        v-if="reply.user.profile_picture"
                                        :src="profilePictureSrc(reply.user?.profile_picture)"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-[10px]">
                                        {{ reply.user.display_name?.charAt(0).toUpperCase() }}
                                    </div>
                                </button>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-100 rounded-2xl px-3 py-2">
                                        <h5 class="font-semibold text-gray-900 text-xs">{{ reply.user.display_name || reply.user.fullname }}</h5>
                                        <p class="text-gray-800 text-sm mt-0.5">{{ reply.content }}</p>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1 px-3">
                                        <span class="text-xs text-gray-500">{{ reply.time_ago }}</span>
                                        <button
                                            type="button"
                                            @click="toggleCommentLike(reply)"
                                            class="flex items-center gap-1 text-xs font-semibold"
                                            :class="reply.is_liked_by_user ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600'"
                                        >
                                            <Heart class="w-3.5 h-3.5" :class="reply.is_liked_by_user ? 'fill-current' : ''" />
                                            {{ reply.likes_count ?? 0 }}
                                        </button>
                                        <button type="button" @click="replyingToComment = { id: reply.id, user: reply.user }" class="text-xs text-gray-500 hover:text-blue-600 font-semibold">
                                            Reply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Add comment -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div v-if="replyingToComment" class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">Replying to {{ replyingToComment.user.display_name || replyingToComment.user.fullname }}</span>
                            <button type="button" @click="replyingToComment = null" class="text-xs text-blue-600 hover:underline">Cancel</button>
                        </div>
                        <div class="flex gap-2">
                            <input
                                v-model="newComment"
                                type="text"
                                :placeholder="replyingToComment ? 'Write a reply...' : 'Write a comment...'"
                                class="flex-1 px-4 py-2.5 bg-gray-100 rounded-full text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                @keydown.enter.prevent="addComment(newComment, replyingToComment?.id ?? null)"
                            />
                            <button
                                type="button"
                                @click="addComment(newComment, replyingToComment?.id ?? null)"
                                :disabled="!newComment.trim()"
                                class="p-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-full disabled:opacity-50"
                            >
                                <Send class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        <FullscreenImageViewer v-model="showFullscreenImage" v-model:index="fullscreenIndex" :images="fullscreenImages" />
        <BottomNav />
    </div>
</template>
