<script setup lang="ts">
import { Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance } = useAppearance();

/** Only light theme is used; dark and system are excluded. */
const tabs = [{ value: 'light' as const, Icon: Sun, label: 'Light' }];
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                appearance === value
                    ? 'bg-white shadow-xs text-neutral-900'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ label }}</span>
        </button>
    </div>
</template>
