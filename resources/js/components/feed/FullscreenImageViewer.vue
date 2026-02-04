<script setup lang="ts">
import { watch, onUnmounted } from 'vue';
import { X, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    images: string[];
}>();

const open = defineModel<boolean>({ default: false });
const index = defineModel<number>('index', { default: 0 });

function close() {
    open.value = false;
}

function prev() {
    if (index.value > 0) index.value--;
}

function next() {
    if (index.value < props.images.length - 1) index.value++;
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next();
}

watch(open, (isOpen) => {
    if (isOpen) {
        document.addEventListener('keydown', onKeydown);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', onKeydown);
        document.body.style.overflow = '';
    }
}, { immediate: true });

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open && images.length"
            class="fixed inset-0 z-[70] bg-black/95 flex items-center justify-center"
            @click.self="close"
        >
            <button
                type="button"
                @click.stop="close"
                class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
                aria-label="Close"
            >
                <X class="w-6 h-6" />
            </button>
            <img
                :src="`/storage/${images[index]}`"
                :alt="`Image ${index + 1}`"
                class="max-w-full max-h-full object-contain select-none"
                @click.stop
            />
            <button
                v-if="images.length > 1 && index > 0"
                type="button"
                @click.stop="prev"
                class="absolute left-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
                aria-label="Previous"
            >
                <ChevronLeft class="w-8 h-8" />
            </button>
            <button
                v-if="images.length > 1 && index < images.length - 1"
                type="button"
                @click.stop="next"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
                aria-label="Next"
            >
                <ChevronRight class="w-8 h-8" />
            </button>
            <span
                v-if="images.length > 1"
                class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/80 text-sm"
            >
                {{ index + 1 }} / {{ images.length }}
            </span>
        </div>
    </Teleport>
</template>
