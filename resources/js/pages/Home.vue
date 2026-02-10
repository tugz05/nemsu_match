<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Heart, MessageCircle, Bell } from 'lucide-vue-next';
import PostSkeleton from '@/components/PostSkeleton.vue';
import type { Post, PostComment } from '@/types';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { useRealtimeNotifications } from '@/composables/useRealtimeNotifications';
import {
    BottomNav,
    FullscreenImageViewer,
    PostCard,
    CreatePostModal,
    EditPostModal,
    CommentsModal,
    DeletePostConfirmModal,
    ReportPostModal,
    NotificationsDropdown,
    ProfileSearch,
} from '@/components/feed';

const { user } = defineProps<{
    user?: {
        id: number;
        display_name?: string;
        profile_picture?: string;
    };
}>();

const getCsrfToken = useCsrfToken();

const posts = ref<Post[]>([]);
const currentUserId = ref<number | null>(null);
const loading = ref(false);
const loadingMore = ref(false);
const nextPageUrl = ref<string | null>(null);
const loadMoreSentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const showCreatePost = ref(false);
const creatingPost = ref(false);
const postCreated = ref(false);
let postCreatedTimer: number | null = null;

// Comments
const showComments = ref(false);
const selectedPost = ref<Post | null>(null);
const comments = ref<PostComment[]>([]);
const loadingComments = ref(false);
const replyingToComment = ref<{ id: number; user: { display_name: string; fullname: string } } | null>(null);

// Post menu (3 dots)
const showPostMenu = ref<number | null>(null);
const showDeleteConfirm = ref(false);
const showReportModal = ref(false);
const showEditPostModal = ref(false);
const postToDelete = ref<Post | null>(null);
const postToReport = ref<Post | null>(null);
const postToEdit = ref<Post | null>(null);
const savingEdit = ref(false);
const submittingReport = ref(false);
const unreadNotificationsCount = ref(0);
const showNotificationsDropdown = ref(false);

// Fullscreen image
const fullscreenImages = ref<string[]>([]);
const fullscreenIndex = ref(0);
const showFullscreenImage = ref(false);

const openFullscreenImage = (images: string[], index: number) => {
    fullscreenImages.value = images;
    fullscreenIndex.value = index;
    showFullscreenImage.value = true;
};

const closeFullscreenImage = () => {
    showFullscreenImage.value = false;
};

const fullscreenPrev = () => {
    if (fullscreenIndex.value > 0) fullscreenIndex.value--;
};

const fullscreenNext = () => {
    if (fullscreenIndex.value < fullscreenImages.value.length - 1) fullscreenIndex.value++;
};

const onFullscreenKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') closeFullscreenImage();
    if (e.key === 'ArrowLeft') fullscreenPrev();
    if (e.key === 'ArrowRight') fullscreenNext();
};

useRealtimeNotifications(() => {
    unreadNotificationsCount.value += 1;
});

async function fetchUnreadNotificationsCount() {
    try {
        const res = await fetch('/api/notifications/unread-count', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            unreadNotificationsCount.value = data.count ?? 0;
        }
    } catch (e) {
        console.error(e);
    }
}

// Fetch initial posts (page 1)
const fetchPosts = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/posts', {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (!response.ok) return;

        const data = await response.json();
        posts.value = data.data || [];
        nextPageUrl.value = data.next_page_url || null;
        if (data.current_user_id != null) currentUserId.value = data.current_user_id;
    } catch (error) {
        console.error('Failed to fetch posts:', error);
    } finally {
        loading.value = false;
    }
};

// Load more posts (infinite scroll)
const loadMorePosts = async () => {
    if (!nextPageUrl.value || loadingMore.value) return;

    loadingMore.value = true;
    try {
        const response = await fetch(nextPageUrl.value, {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (!response.ok) return;

        const data = await response.json();
        const newPosts = data.data || [];
        posts.value = [...posts.value, ...newPosts];
        nextPageUrl.value = data.next_page_url || null;
        if (data.current_user_id != null) currentUserId.value = data.current_user_id;
    } catch (error) {
        console.error('Failed to load more posts:', error);
    } finally {
        loadingMore.value = false;
    }
};

// Create new post (called from CreatePostModal)
const createPost = async (payload: { content: string; images: File[] }) => {
    const formData = new FormData();
    formData.append('content', payload.content);
    payload.images.forEach((file) => formData.append('images[]', file));

    creatingPost.value = true;

    try {
        const response = await fetch('/api/posts', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            showCreatePost.value = false;
            await fetchPosts();

            postCreated.value = true;
            if (postCreatedTimer) window.clearTimeout(postCreatedTimer);
            postCreatedTimer = window.setTimeout(() => {
                postCreated.value = false;
                postCreatedTimer = null;
            }, 2500);
        } else {
            const error = await response.json();
            console.error('Failed to create post:', error);
        }
    } catch (error) {
        console.error('Failed to create post:', error);
    } finally {
        creatingPost.value = false;
    }
};

// Toggle like
const toggleLike = async (post: Post) => {
    try {
        const response = await fetch(`/api/posts/${post.id}/like`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            post.is_liked_by_user = data.liked;
            post.likes_count = data.likes_count;
        }
    } catch (error) {
        console.error('Failed to toggle like:', error);
    }
};

// Open comments modal
const openComments = async (post: Post) => {
    selectedPost.value = post;
    showComments.value = true;
    replyingToComment.value = null;
    await fetchComments(post.id);
};

// Fetch comments for a post
const fetchComments = async (postId: number) => {
    loadingComments.value = true;
    try {
        const response = await fetch(`/api/posts/${postId}/comments`, {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            comments.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch comments:', error);
    } finally {
        loadingComments.value = false;
    }
};

// Add comment or reply (called from CommentsModal)
const addComment = async (content: string, parentId: number | null) => {
    if (!content.trim() || !selectedPost.value) return;

    try {
        const response = await fetch(`/api/posts/${selectedPost.value.id}/comment`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ content: content.trim(), parent_id: parentId }),
        });

        if (response.ok) {
            const data = await response.json();
            const newC = data.comment;

            if (parentId) {
                let topLevel = comments.value.find((c: PostComment) => c.id === parentId);
                if (!topLevel) {
                    for (const c of comments.value) {
                        const inReplies = (c.replies || []).find((r: PostComment) => r.id === parentId);
                        if (inReplies) {
                            topLevel = c;
                            break;
                        }
                    }
                }
                if (topLevel) {
                    if (!topLevel.replies) topLevel.replies = [];
                    topLevel.replies.push(newC);
                }
            } else {
                comments.value.unshift(newC);
            }

            selectedPost.value.comments_count++;
            const postIndex = posts.value.findIndex(p => p.id === selectedPost.value!.id);
            if (postIndex !== -1) {
                posts.value[postIndex].comments_count++;
            }

            replyingToComment.value = null;
        }
    } catch (error) {
        console.error('Failed to add comment:', error);
    }
};

// Toggle like on a comment (or reply)
const toggleCommentLike = async (comment: any) => {
    if (!selectedPost.value) return;

    try {
        const response = await fetch(
            `/api/posts/${selectedPost.value.id}/comments/${comment.id}/like`,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
            }
        );

        if (response.ok) {
            const data = await response.json();
            comment.is_liked_by_user = data.liked;
            comment.likes_count = data.likes_count;
        }
    } catch (error) {
        console.error('Failed to toggle comment like:', error);
    }
};

const setReplyingTo = (comment: any) => {
    replyingToComment.value = { id: comment.id, user: comment.user };
};

const cancelReply = () => {
    replyingToComment.value = null;
};

// Toggle repost
const toggleRepost = async (post: Post) => {
    try {
        const response = await fetch(`/api/posts/${post.id}/repost`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            post.reposts_count = data.reposts_count;
        }
    } catch (error) {
        console.error('Failed to toggle repost:', error);
    }
};

// Share post
const sharePost = async (post: Post) => {
    const shareText = `Check out this post on NEMSU Match!\n\n${post.content.substring(0, 100)}...`;
    const shareUrl = `${window.location.origin}/post/${post.id}`;

    if (navigator.share) {
        try {
            await navigator.share({
                title: 'NEMSU Match Post',
                text: shareText,
                url: shareUrl,
            });
        } catch (error) {
            console.log('Share cancelled or failed');
        }
    } else {
        // Fallback: Copy to clipboard
        try {
            await navigator.clipboard.writeText(`${shareText}\n\n${shareUrl}`);
            alert('Link copied to clipboard!');
        } catch (error) {
            console.error('Failed to copy:', error);
        }
    }
};

// Toggle post menu
const togglePostMenu = (postId: number) => {
    showPostMenu.value = showPostMenu.value === postId ? null : postId;
};

// Close menu when clicking outside
const closePostMenu = () => {
    showPostMenu.value = null;
};

// Open edit post modal (creator only)
const openEditPost = (post: Post) => {
    postToEdit.value = post;
    showEditPostModal.value = true;
    showPostMenu.value = null;
};

// Save edited post (called from EditPostModal)
const saveEditPost = async (content: string) => {
    if (!postToEdit.value || !content.trim()) return;

    savingEdit.value = true;
    try {
        const response = await fetch(`/api/posts/${postToEdit.value.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ content: content.trim() }),
        });

        if (response.ok) {
            const data = await response.json();
            const updated = data.post;
            const idx = posts.value.findIndex(p => p.id === postToEdit.value!.id);
            if (idx !== -1) {
                posts.value[idx] = { ...posts.value[idx], content: updated.content };
            }
            showEditPostModal.value = false;
            postToEdit.value = null;
        } else {
            const err = await response.json();
            alert(err.message || 'Failed to update post');
        }
    } catch (error) {
        console.error('Failed to update post:', error);
        alert('Failed to update post');
    } finally {
        savingEdit.value = false;
    }
};

// Confirm delete
const confirmDelete = (post: Post) => {
    postToDelete.value = post;
    showDeleteConfirm.value = true;
    showPostMenu.value = null;
};

// Delete post
const deletePost = async () => {
    if (!postToDelete.value) return;

    try {
        const response = await fetch(`/api/posts/${postToDelete.value.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            // Remove post from list
            posts.value = posts.value.filter(p => p.id !== postToDelete.value!.id);
            showDeleteConfirm.value = false;
            postToDelete.value = null;
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to delete post');
        }
    } catch (error) {
        console.error('Failed to delete post:', error);
        alert('Failed to delete post');
    }
};

// Open report modal
const openReportModal = (post: Post) => {
    postToReport.value = post;
    showReportModal.value = true;
    showPostMenu.value = null;
};

// Navigate to user profile (avatar or name click)
const goToProfile = (userId: number) => {
    router.visit(`/profile/${userId}`);
};

// True when the post is from the current user (never show Follow on own posts).
// Prefer server flag is_own_post; fallback to client comparison.
const isOwnPost = (post: Post) => {
    if (post.is_own_post === true) return true;
    const uid = currentUserId.value ?? user?.id;
    if (uid == null) return true;
    return Number(post.user_id) === Number(uid) || Number(post.user?.id) === Number(uid);
};

// Toggle follow on post author
const followLoading = ref<number | null>(null);
const toggleFollow = async (post: Post) => {
    if (isOwnPost(post)) return;

    followLoading.value = post.id;
    try {
        const response = await fetch(`/api/users/${post.user.id}/follow`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            post.is_followed_by_user = data.following;
        }
    } catch (error) {
        console.error('Failed to toggle follow:', error);
    } finally {
        followLoading.value = null;
    }
};

// Submit report (called from ReportPostModal)
const submitReport = async (payload: { reason: string; description: string }) => {
    if (!postToReport.value) return;

    submittingReport.value = true;

    try {
        const response = await fetch(`/api/posts/${postToReport.value.id}/report`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                reason: payload.reason,
                description: payload.description,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            alert(data.message);
            showReportModal.value = false;
            postToReport.value = null;
        } else {
            alert(data.message || 'Failed to report post');
        }
    } catch (error) {
        console.error('Failed to report post:', error);
        alert('Failed to report post');
    } finally {
        submittingReport.value = false;
    }
};

onMounted(() => {
    if (user?.id != null) currentUserId.value = user.id;
    fetchPosts();
    fetchUnreadNotificationsCount();

    // Infinite scroll: when sentinel is visible, load more
    observer = new IntersectionObserver(
        (entries) => {
            const entry = entries[0];
            if (entry?.isIntersecting && nextPageUrl.value && !loading.value && !loadingMore.value) {
                loadMorePosts();
            }
        },
        { root: null, rootMargin: '200px', threshold: 0.1 }
    );

    // Start observing sentinel when it appears in DOM (after initial load)
    const startObserving = () => {
        if (loadMoreSentinel.value && observer) {
            observer.observe(loadMoreSentinel.value);
        }
    };
    watch(loadMoreSentinel, startObserving, { flush: 'post' });
    watch(loading, (isLoading) => {
        if (!isLoading) setTimeout(startObserving, 100);
    }, { flush: 'post' });

    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.post-menu-container')) {
            closePostMenu();
        }
        if (!target.closest('.notifications-dropdown-container')) {
            showNotificationsDropdown.value = false;
        }
    });
});

watch(showFullscreenImage, (isOpen) => {
    if (isOpen) {
        document.addEventListener('keydown', onFullscreenKeydown);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', onFullscreenKeydown);
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    observer?.disconnect();
    observer = null;
    document.removeEventListener('keydown', onFullscreenKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen bg-white pb-20">
        <Head title="NEMSU Match - Home" />

        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3 relative">
                <div class="flex items-center gap-2 shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                        <Heart class="w-6 h-6 text-white fill-white" />
                    </div>
                    <span class="text-xl font-bold text-gray-900 hidden sm:inline">Home</span>
                </div>

                <ProfileSearch class="min-w-0" />

                <div class="flex items-center gap-1 relative notifications-dropdown-container shrink-0">
                    <NotificationsDropdown
                        v-model:open="showNotificationsDropdown"
                        v-model:unread-count="unreadNotificationsCount"
                        class="flex"
                    >
                        <template #trigger="{ toggle }">
                            <button
                                type="button"
                                @click.stop="toggle"
                                class="relative p-2 hover:bg-blue-50 rounded-full transition-colors"
                                aria-label="Notifications"
                            >
                                <Bell class="w-6 h-6 text-gray-700" />
                                <span
                                    v-if="unreadNotificationsCount > 0"
                                    class="absolute top-0.5 right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-blue-600 text-white text-xs font-bold rounded-full"
                                >
                                    {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
                                </span>
                            </button>
                        </template>
                    </NotificationsDropdown>
                    <button
                        type="button"
                        @click="showCreatePost = true"
                        class="p-2 hover:bg-blue-50 rounded-full transition-colors"
                    >
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <Transition name="fade">
                    <div
                        v-if="postCreated"
                        class="absolute inset-x-0 -bottom-9 flex justify-center pointer-events-none"
                    >
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-500 text-white text-xs font-semibold px-3 py-1.5 shadow-lg pointer-events-auto"
                        >
                            <span class="inline-block w-3 h-3 rounded-full bg-white/80" />
                            <span>Post created</span>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Feed -->
        <div class="max-w-2xl mx-auto">
            <!-- Initial loading: skeleton cards with shimmer -->
            <PostSkeleton v-if="loading && posts.length === 0" :count="4" />

            <!-- Posts list -->
            <template v-else-if="posts.length > 0">
                <PostCard
                    v-for="post in posts"
                    :key="post.id"
                    :post="post"
                    :is-own-post="isOwnPost(post)"
                    :is-post-owner="post.user_id === user?.id"
                    :follow-loading="followLoading === post.id"
                    :menu-open="showPostMenu === post.id"
                    :enable-see-more="true"
                    @go-to-profile="goToProfile"
                    @follow="toggleFollow(post)"
                    @menu-toggle="togglePostMenu(post.id)"
                    @edit="openEditPost(post)"
                    @delete="confirmDelete(post)"
                    @report="openReportModal(post)"
                    @open-fullscreen="openFullscreenImage"
                    @like="toggleLike(post)"
                    @open-comments="openComments(post)"
                    @view-post="router.visit('/post/' + post.id)"
                    @repost="toggleRepost(post)"
                    @share="sharePost(post)"
                />

                <!-- Infinite scroll sentinel -->
                <div
                    v-if="nextPageUrl"
                    ref="loadMoreSentinel"
                    class="h-2 w-full"
                    aria-hidden="true"
                />

                <!-- Loading more: skeleton at bottom -->
                <PostSkeleton v-if="loadingMore" :count="2" />
            </template>

            <!-- Empty State -->
            <div v-else-if="!loading" class="text-center py-12 px-4">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Heart class="w-10 h-10 text-blue-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No posts yet</h3>
                <p class="text-gray-600 mb-4">Be the first to share something!</p>
                <button
                    @click="showCreatePost = true"
                    class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-6 py-2.5 rounded-full font-semibold hover:shadow-lg transition-all"
                >
                    Create Post
                </button>
            </div>
        </div>

        <CreatePostModal
            :open="showCreatePost"
            :creating="creatingPost"
            @close="showCreatePost = false"
            @create="createPost"
        />

        <EditPostModal
            :open="showEditPostModal"
            :post="postToEdit"
            v-model:saving="savingEdit"
            @close="showEditPostModal = false; postToEdit = null"
            @save="saveEditPost"
        />

        <FullscreenImageViewer
            v-model="showFullscreenImage"
            v-model:index="fullscreenIndex"
            :images="fullscreenImages"
        />

        <CommentsModal
            :open="showComments"
            :post="selectedPost"
            :comments="comments"
            :loading="loadingComments"
            :replying-to-comment="replyingToComment"
            @close="showComments = false"
            @go-to-profile="(userId) => { goToProfile(userId); showComments = false }"
            @add-comment="addComment"
            @toggle-comment-like="toggleCommentLike"
            @set-replying-to="setReplyingTo"
            @cancel-reply="cancelReply"
        />

        <DeletePostConfirmModal
            :open="!!(showDeleteConfirm && postToDelete)"
            @close="showDeleteConfirm = false"
            @confirm="deletePost"
        />

        <ReportPostModal
            :open="!!(showReportModal && postToReport)"
            :post="postToReport"
            :submitting="submittingReport"
            @close="showReportModal = false; postToReport = null"
            @submit="submitReport"
        />

        <!-- Floating Action Buttons - Vertically Stacked -->
        <Teleport to="body">
            <div class="fixed right-4 bottom-32 z-50 flex flex-col gap-4">
                <!-- Create Post Button -->
                <button
                    @click="showCreatePost = true"
                    class="w-16 h-16 bg-gradient-to-br from-amber-400 via-yellow-500 to-amber-500 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(251,191,36,0.5)] hover:scale-125 transition-all duration-300 group fab-float"
                    title="Create Post"
                >
                    <svg class="w-8 h-8 text-white drop-shadow-lg group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </button>

                <!-- Browse/Discover Button -->
                <button
                    @click="router.visit('/browse')"
                    class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(6,182,212,0.5)] hover:scale-125 transition-all duration-300 group fab-float"
                    title="Browse Matches"
                >
                    <svg class="w-8 h-8 text-white drop-shadow-lg group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </button>

                <!-- Discover Button -->
                <button
                    @click="router.visit('/like-you')"
                    class="w-16 h-16 bg-gradient-to-br from-pink-500 via-red-500 to-rose-600 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(236,72,153,0.5)] hover:scale-125 transition-all duration-300 group fab-float animate-pulse-glow"
                    title="Discover"
                >
                    <Heart class="w-8 h-8 text-white fill-white drop-shadow-lg group-hover:scale-110 transition-transform duration-300" stroke-width="2" />
                </button>
            </div>
        </Teleport>

        <BottomNav active-tab="home" />
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

@keyframes scale-in {
    from {
        transform: scale(0.95);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fab-float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(236, 72, 153, 0.4), 0 0 40px rgba(236, 72, 153, 0.2);
    }
    50% {
        box-shadow: 0 0 30px rgba(236, 72, 153, 0.6), 0 0 60px rgba(236, 72, 153, 0.3);
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}

.fab-float {
    animation: fab-float 3s ease-in-out infinite;
}

.animate-pulse-glow {
    animation: pulse-glow 2s ease-in-out infinite;
}

/* Stagger animation for FABs */
.fab-float:nth-child(1) {
    animation-delay: 0s;
}

.fab-float:nth-child(2) {
    animation-delay: 0.2s;
}

.fab-float:nth-child(3) {
    animation-delay: 0.4s;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

.ml-13 {
    margin-left: 52px;
}

/* Make buttons more tactile on mobile */
@media (hover: none) and (pointer: coarse) {
    button:active {
        transform: scale(0.9) !important;
    }
}
</style>
