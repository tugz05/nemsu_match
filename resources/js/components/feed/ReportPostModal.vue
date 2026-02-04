<script setup lang="ts">
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import type { Post } from '@/types';

const props = defineProps<{
    open: boolean;
    post: Post | null;
    submitting: boolean;
}>();

const emit = defineEmits<{
    close: [];
    submit: [payload: { reason: string; description: string }];
}>();

const reason = ref('spam');
const description = ref('');

watch(
    () => props.open,
    (open) => {
        if (open) {
            reason.value = 'spam';
            description.value = '';
        }
    }
);

function handleClose() {
    emit('close');
}

function handleSubmit() {
    emit('submit', { reason: reason.value, description: description.value });
}
</script>

<template>
    <div
        v-if="open && post"
        class="fixed inset-0 bg-black/50 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="handleClose"
    >
        <div class="bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl animate-slide-up flex flex-col mb-20 sm:mb-0">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Report Post</h3>
                <button type="button" @click="handleClose" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Help us understand what's wrong with this post. Your report is anonymous.
                </p>
                <div class="space-y-3 mb-4">
                    <label
                        v-for="opt in [
                            { value: 'spam', label: 'Spam', sub: 'Unwanted or repetitive content' },
                            { value: 'harassment', label: 'Harassment or Bullying', sub: 'Targeting or attacking someone' },
                            { value: 'inappropriate', label: 'Inappropriate Content', sub: 'Offensive or explicit material' },
                            { value: 'misleading', label: 'False or Misleading', sub: 'Misinformation or fake content' },
                            { value: 'other', label: 'Other', sub: 'Something else' },
                        ]"
                        :key="opt.value"
                        class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                        :class="reason === opt.value ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <input type="radio" v-model="reason" :value="opt.value" class="w-4 h-4 text-blue-600" />
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">{{ opt.label }}</div>
                            <div class="text-xs text-gray-500">{{ opt.sub }}</div>
                        </div>
                    </label>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Additional Details (Optional)</label>
                    <textarea
                        v-model="description"
                        placeholder="Provide more context..."
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors resize-none"
                        rows="3"
                        maxlength="500"
                    />
                    <div class="text-right text-xs text-gray-500 mt-1">{{ description.length }}/500</div>
                </div>
                <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="submitting"
                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-3 rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <span v-if="submitting">Submitting...</span>
                    <span v-else>Submit Report</span>
                </button>
                <p class="text-xs text-center text-gray-500 mt-3">Reports are reviewed by our team and kept confidential.</p>
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
