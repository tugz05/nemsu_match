<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Lightbulb } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        storageKey: string;
        title: string;
        body?: string;
        /** Which side of the dialog shows an arrow pointing to the target (e.g. "bottom" = arrow points down to content below) */
        arrowSide?: 'top' | 'right' | 'bottom' | 'left' | 'none';
        /** When false, dialog never shows (e.g. wait until target section is visible) */
        active?: boolean;
    }>(),
    { body: '', arrowSide: 'bottom', active: true }
);

const dismissed = ref(false);

const visible = computed(() => {
    if (!props.active || dismissed.value) return false;
    if (typeof window === 'undefined') return false;
    try {
        return localStorage.getItem(props.storageKey) !== '1';
    } catch {
        return false;
    }
});

function dismiss() {
    try {
        localStorage.setItem(props.storageKey, '1');
    } catch {
        // ignore
    }
    dismissed.value = true;
}

onMounted(() => {
    if (typeof window !== 'undefined' && localStorage.getItem(props.storageKey) === '1') {
        dismissed.value = true;
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="tutorial-prompt-overlay"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="'tutorial-title-' + storageKey"
            :aria-describedby="'tutorial-body-' + storageKey"
        >
            <div
                class="tutorial-prompt-dialog"
                @click.stop
            >
                <div class="flex gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <Lightbulb class="w-5 h-5 text-amber-600" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2
                            :id="'tutorial-title-' + storageKey"
                            class="font-bold text-gray-900 text-base"
                        >
                            {{ title }}
                        </h2>
                        <p
                            :id="'tutorial-body-' + storageKey"
                            class="text-sm text-gray-700 mt-1.5 leading-snug"
                        >
                            <slot name="body">{{ body }}</slot>
                        </p>
                        <button
                            type="button"
                            class="mt-4 w-full py-2.5 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-95 transition-opacity focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            @click="dismiss"
                        >
                            Got it
                        </button>
                    </div>
                </div>
                <!-- Arrow pointing to target (CSS triangle) -->
                <div
                    v-if="arrowSide !== 'none'"
                    class="tutorial-prompt-arrow"
                    :class="'tutorial-prompt-arrow-' + arrowSide"
                    aria-hidden="true"
                />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Below PermissionPrompt (z-[9999]) so permission modal stays on top */
.tutorial-prompt-overlay {
    position: fixed;
    inset: 0;
    z-index: 9990;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 1rem;
    padding-top: 14vh;
    background-color: rgba(0, 0, 0, 0.45);
    pointer-events: auto;
}

.tutorial-prompt-dialog {
    position: relative;
    max-width: 22rem;
    width: 100%;
    padding: 1.25rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(0, 0, 0, 0.06);
}

/* Arrow: 12px triangle pointing to target */
.tutorial-prompt-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border: 10px solid transparent;
}

.tutorial-prompt-arrow-bottom {
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    border-top-color: white;
    border-bottom: none;
}

.tutorial-prompt-arrow-top {
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    border-bottom-color: white;
    border-top: none;
}

.tutorial-prompt-arrow-left {
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    border-right-color: white;
    border-left: none;
}

.tutorial-prompt-arrow-right {
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    border-left-color: white;
    border-right: none;
}
</style>
