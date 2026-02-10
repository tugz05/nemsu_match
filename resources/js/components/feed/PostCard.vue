<script setup lang="ts">
import { computed, ref } from 'vue';
import { Heart, MessageCircle, Repeat2, Send, MoreHorizontal, Pencil, Trash2, Flag } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import type { Post } from '@/types';

const props = defineProps<{
    post: Post;
    isOwnPost: boolean;
    isPostOwner: boolean;
    followLoading: boolean;
    menuOpen: boolean;
    /** When true, long captions are truncated with a “See more” toggle. */
    enableSeeMore?: boolean;
}>();

const emit = defineEmits<{
    goToProfile: [userId: number];
    follow: [];
    menuToggle: [];
    edit: [];
    delete: [];
    report: [];
    openFullscreen: [images: string[], index: number];
    like: [];
    openComments: [];
    repost: [];
    share: [];
    viewPost: [];
}>();

function getPostImages(post: Post): string[] {
    if (post.images_list?.length) return post.images_list;
    if (Array.isArray(post.images) && post.images.length) return post.images;
    if (post.image) return [post.image];
    return [];
}

const images = computed(() => getPostImages(props.post));

const MAX_CAPTION_CHARS = 220;
const showFullCaption = ref(false);

const isLongCaption = computed(
    () => (props.post.content?.length || 0) > MAX_CAPTION_CHARS,
);

const previewCaption = computed(() => {
    const text = props.post.content || '';
    if (!props.enableSeeMore || !isLongCaption.value || showFullCaption.value) {
        return text;
    }
    return `${text.slice(0, MAX_CAPTION_CHARS).trimEnd()}…`;
});
</script>

<template>
    <article class="border-b border-gray-200 hover:bg-gray-50/50 transition-colors">
        <div class="p-4">
            <div class="flex items-start gap-3 mb-3">
                <button
                    type="button"
                    @click.stop="emit('goToProfile', post.user.id)"
                    class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-cyan-100 flex-shrink-0 hover:ring-2 hover:ring-blue-300 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <img
                        v-if="post.user.profile_picture"
                        :src="profilePictureSrc(post.user?.profile_picture)"
                        :alt="post.user.display_name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold">
                        {{ post.user.display_name?.charAt(0).toUpperCase() }}
                    </div>
                </button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <button
                            type="button"
                            @click.stop="emit('goToProfile', post.user.id)"
                            class="text-left hover:opacity-80 transition-opacity focus:outline-none min-w-0"
                        >
                            <h3 class="font-semibold text-gray-900 text-sm">{{ post.user.display_name || post.user.fullname }}</h3>
                            <p class="text-xs text-gray-500">{{ post.user.academic_program }} • {{ post.time_ago }}</p>
                        </button>
                        <div class="flex items-center gap-2 ml-auto">
                            <button
                                v-if="!isOwnPost"
                                type="button"
                                @click="emit('follow')"
                                :disabled="followLoading"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-colors shrink-0"
                                :class="post.is_followed_by_user ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700'"
                            >
                                {{ followLoading ? '...' : (post.is_followed_by_user ? 'Following' : 'Follow') }}
                            </button>
                            <div class="relative post-menu-container">
                                <button
                                    type="button"
                                    @click.stop="emit('menuToggle')"
                                    class="p-1 hover:bg-gray-200 rounded-full transition-colors"
                                >
                                    <MoreHorizontal class="w-5 h-5 text-gray-500" />
                                </button>
                                <div
                                    v-if="menuOpen"
                                    class="absolute right-0 top-8 bg-white rounded-xl shadow-2xl border border-gray-200 py-2 min-w-[180px] z-50 animate-scale-in"
                                >
                                    <button
                                        v-if="isPostOwner"
                                        type="button"
                                        @click="emit('edit')"
                                        class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                        <span class="font-medium">Edit Post</span>
                                    </button>
                                    <button
                                        v-if="isPostOwner"
                                        type="button"
                                        @click="emit('delete')"
                                        class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-3 transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                        <span class="font-medium">Delete Post</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="emit('report')"
                                        class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 transition-colors"
                                    >
                                        <Flag class="w-4 h-4" />
                                        <span class="font-medium">Report Post</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ml-13">
                <div
                    role="button"
                    tabindex="0"
                    class="cursor-pointer rounded-xl -mx-1 px-1 py-0.5 hover:bg-gray-100/80 transition-colors outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"
                    @click="emit('viewPost')"
                    @keydown.enter.space.prevent="emit('viewPost')"
                >
                    <p class="text-gray-900 text-sm leading-relaxed mb-3 whitespace-pre-wrap">
                        {{ previewCaption }}
                        <button
                            v-if="enableSeeMore && isLongCaption && !showFullCaption"
                            type="button"
                            class="ml-1 text-blue-600 text-xs font-semibold underline"
                            @click.stop="showFullCaption = true"
                        >
                            See more
                        </button>
                        <button
                            v-else-if="enableSeeMore && isLongCaption && showFullCaption"
                            type="button"
                            class="ml-1 text-blue-600 text-xs font-semibold underline"
                            @click.stop="showFullCaption = false"
                        >
                            See less
                        </button>
                    </p>
                    <div v-if="images.length" class="mb-3 rounded-2xl overflow-hidden">
                        <div :class="images.length > 1 ? 'flex flex-row overflow-x-auto gap-2 snap-x snap-mandatory' : ''">
                            <img
                                v-for="(img, idx) in images"
                                :key="idx"
                                :src="`/storage/${img}`"
                                :alt="`Post image ${idx + 1}`"
                                :class="images.length > 1 ? 'flex-shrink-0 w-[85%] max-w-[320px] h-auto rounded-xl object-cover snap-start cursor-pointer' : 'w-full h-auto rounded-xl object-cover cursor-pointer'"
                                @click.stop="emit('openFullscreen', images, idx)"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-6 pt-2">
                    <button
                        type="button"
                        @click.stop="emit('like')"
                        class="flex items-center gap-1.5 group transition-all"
                        :class="post.is_liked_by_user ? 'text-blue-600' : 'text-gray-500'"
                    >
                        <Heart
                            class="w-5 h-5 group-hover:scale-110 transition-transform"
                            :class="post.is_liked_by_user ? 'fill-blue-600' : ''"
                        />
                        <span class="text-xs font-medium">{{ post.likes_count }}</span>
                    </button>
                    <button
                        type="button"
                        @click.stop="emit('openComments')"
                        class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 group transition-colors"
                    >
                        <MessageCircle class="w-5 h-5 group-hover:scale-110 transition-transform" />
                        <span class="text-xs font-medium">{{ post.comments_count }}</span>
                    </button>
                    <button
                        type="button"
                        @click.stop="emit('repost')"
                        class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 group transition-colors"
                    >
                        <Repeat2 class="w-5 h-5 group-hover:scale-110 transition-transform" />
                        <span class="text-xs font-medium">{{ post.reposts_count }}</span>
                    </button>
                    <button
                        type="button"
                        @click.stop="emit('share')"
                        class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 group transition-colors ml-auto"
                    >
                        <Send class="w-5 h-5 group-hover:scale-110 transition-transform" />
                    </button>
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>
.ml-13 {
    margin-left: 52px;
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
.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}
</style>
