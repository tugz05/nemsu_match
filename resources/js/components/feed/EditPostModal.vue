<script setup lang="ts">
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import type { Post } from '@/types';

const props = defineProps<{
    open: boolean;
    post: Post | null;
}>();

const emit = defineEmits<{
    close: [];
    save: [content: string];
}>();

const content = ref('');
const saving = defineModel<boolean>('saving', { default: false });

watch(
    () => [props.open, props.post] as const,
    ([open, post]) => {
        if (open && post) {
            content.value = post.content;
        }
    },
    { immediate: true }
);

function handleClose() {
    emit('close');
}

function handleSave() {
    if (!content.value.trim() || saving.value) return;
    emit('save', content.value.trim());
}
</script>

<template>
    <div
        v-if="open && post"
        class="fixed inset-0 bg-black/50 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="handleClose"
    >
        <div class="bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl animate-slide-up max-h-[90vh] flex flex-col mb-20 sm:mb-0">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Edit Post</h3>
                <button
                    type="button"
                    @click="handleClose"
                    class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                >
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <textarea
                    v-model="content"
                    placeholder="What's happening at NEMSU?"
                    class="w-full min-h-[120px] text-base text-gray-900 placeholder-gray-400 outline-none resize-none"
                    maxlength="1000"
                />
                <p class="mt-2 text-xs text-gray-500">{{ content.length }}/1000</p>
            </div>
            <div class="p-4 border-t border-gray-200">
                <button
                    type="button"
                    @click="handleSave"
                    :disabled="!content.trim() || saving"
                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-3 rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ saving ? 'Saving...' : 'Save changes' }}
                </button>
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
