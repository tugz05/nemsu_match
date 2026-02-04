<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

defineProps<{
    open: boolean;
}>();
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 animate-scale-in">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <AlertTriangle class="w-8 h-8 text-red-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Post?</h3>
                <p class="text-gray-600 text-sm mb-6">
                    Are you sure you want to delete this post? This action cannot be undone.
                </p>
                <div class="flex gap-3 w-full">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-full transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="emit('confirm')"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-full transition-colors"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
