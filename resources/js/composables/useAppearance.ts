import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

/** Always light theme; never applies dark class. */
export function updateTheme(_value?: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }
    document.documentElement.classList.remove('dark');
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }
    document.documentElement.classList.remove('dark');
    localStorage.setItem('appearance', 'light');
}

const appearance = ref<Appearance>('light');

const resolvedAppearance = computed<ResolvedAppearance>(() => 'light');

export function useAppearance(): UseAppearanceReturn {
    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
