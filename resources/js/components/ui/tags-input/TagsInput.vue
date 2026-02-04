<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: string[];
    placeholder?: string;
    autocompleteUrl?: string;
    category?: string;
    maxTags?: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const inputValue = ref('');
const suggestions = ref<string[]>([]);
const showSuggestions = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

const tags = computed({
    get: () => props.modelValue || [],
    set: (value) => emit('update:modelValue', value),
});

// Fetch autocomplete suggestions
const fetchSuggestions = async (query: string) => {
    if (!props.autocompleteUrl || query.length < 1) {
        suggestions.value = [];
        return;
    }

    try {
        const url = new URL(props.autocompleteUrl, window.location.origin);
        url.searchParams.append('q', query);
        if (props.category) {
            url.searchParams.append('category', props.category);
        }

        const response = await fetch(url.toString(), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        const data = await response.json();

        // Filter out already selected tags
        suggestions.value = data.filter((item: string) =>
            !tags.value.includes(item) &&
            item.toLowerCase().includes(query.toLowerCase())
        );
        showSuggestions.value = suggestions.value.length > 0;
    } catch (error) {
        console.error('Failed to fetch suggestions:', error);
        suggestions.value = [];
    }
};

watch(inputValue, (newValue) => {
    if (newValue) {
        fetchSuggestions(newValue);
    } else {
        suggestions.value = [];
        showSuggestions.value = false;
    }
});

const addTag = (tag: string) => {
    const trimmedTag = tag.trim();
    if (trimmedTag && !tags.value.includes(trimmedTag)) {
        if (!props.maxTags || tags.value.length < props.maxTags) {
            tags.value = [...tags.value, trimmedTag];
            inputValue.value = '';
            suggestions.value = [];
            showSuggestions.value = false;
        }
    }
};

const removeTag = (index: number) => {
    tags.value = tags.value.filter((_, i) => i !== index);
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        if (inputValue.value) {
            addTag(inputValue.value);
        }
    } else if (e.key === 'Backspace' && !inputValue.value && tags.value.length > 0) {
        removeTag(tags.value.length - 1);
    }
};

const selectSuggestion = (suggestion: string) => {
    addTag(suggestion);
    inputRef.value?.focus();
};
</script>

<template>
    <div class="relative">
        <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-xl bg-white min-h-[42px] focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
            <div
                v-for="(tag, index) in tags"
                :key="index"
                class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-sm rounded-full hover:from-blue-600 hover:to-cyan-600 transition-all animate-scale-in"
            >
                <span>{{ tag }}</span>
                <button
                    type="button"
                    @click="removeTag(index)"
                    class="hover:bg-white/20 rounded-full p-0.5 transition-colors"
                >
                    <X class="w-3 h-3" />
                </button>
            </div>

            <input
                ref="inputRef"
                v-model="inputValue"
                type="text"
                :placeholder="tags.length === 0 ? placeholder : ''"
                @keydown="handleKeyDown"
                @blur="() => setTimeout(() => showSuggestions = false, 200)"
                @focus="() => inputValue && fetchSuggestions(inputValue)"
                class="flex-1 min-w-[120px] outline-none bg-transparent text-sm"
            />
        </div>

        <!-- Autocomplete Suggestions -->
        <div
            v-if="showSuggestions && suggestions.length > 0"
            class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto animate-fade-in"
        >
            <button
                v-for="(suggestion, index) in suggestions"
                :key="index"
                type="button"
                @click="selectSuggestion(suggestion)"
                class="w-full px-4 py-2.5 text-left text-sm hover:bg-blue-50 transition-colors first:rounded-t-xl last:rounded-b-xl flex items-center gap-2 group"
            >
                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                {{ suggestion }}
            </button>
        </div>

        <p v-if="maxTags" class="text-xs text-gray-500 mt-1.5">
            {{ tags.length }}/{{ maxTags }} tags
        </p>
    </div>
</template>

<style scoped>
@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.2s ease-out;
}
</style>
