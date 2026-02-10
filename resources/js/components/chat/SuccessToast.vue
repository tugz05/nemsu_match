<script setup lang="ts">
import { CheckCircle, X } from 'lucide-vue-next';
import { watch, onMounted } from 'vue';

const props = defineProps<{
    show: boolean;
    message: string;
    duration?: number;
}>();

const emit = defineEmits<{
    close: [];
}>();

watch(
    () => props.show,
    (show) => {
        if (show && props.duration) {
            setTimeout(() => {
                emit('close');
            }, props.duration);
        }
    }
);
</script>

<template>
    <Transition name="toast">
        <div
            v-if="show"
            class="fixed top-4 left-1/2 -translate-x-1/2 z-[80] max-w-md w-full mx-4"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 flex items-start gap-3 animate-slide-down">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                    <CheckCircle class="w-6 h-6 text-blue-600" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 mb-0.5">Success</p>
                    <p class="text-sm text-gray-600">{{ message }}</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1 hover:bg-gray-100 rounded-full transition-colors shrink-0"
                >
                    <X class="w-4 h-4 text-gray-500" />
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes slide-down {
    from {
        transform: translate(-50%, -100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}

.animate-slide-down {
    animation: slide-down 0.3s ease-out;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    transform: translate(-50%, -100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translate(-50%, -100%);
    opacity: 0;
}
</style>
