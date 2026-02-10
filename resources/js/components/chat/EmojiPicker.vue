<script setup lang="ts">
import { ref, computed } from 'vue';
import { Search, Smile, Heart, ThumbsUp, Sparkles, Coffee, Sun } from 'lucide-vue-next';

const emit = defineEmits<{
    select: [emoji: string];
    close: [];
}>();

defineProps<{
    show: boolean;
}>();

const searchQuery = ref('');
const activeCategory = ref<string>('smileys');

interface EmojiCategory {
    name: string;
    label: string;
    icon: any;
    emojis: string[];
}

const emojiCategories: EmojiCategory[] = [
    {
        name: 'smileys',
        label: 'Smileys & People',
        icon: Smile,
        emojis: [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
            '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
            '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪',
            '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨',
            '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥',
            '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕',
            '🤢', '🤮', '🤧', '🥵', '🥶', '😵', '🤯', '🤠',
            '🥳', '😎', '🤓', '🧐', '😕', '😟', '🙁', '😮',
            '😯', '😲', '😳', '🥺', '😦', '😧', '😨', '😰',
            '😥', '😢', '😭', '😱', '😖', '😣', '😞', '😓',
            '😩', '😫', '🥱', '😤', '😡', '😠', '🤬', '😈',
            '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻',
            '👽', '👾', '🤖', '😺', '😸', '😹', '😻', '😼',
        ],
    },
    {
        name: 'gestures',
        label: 'Gestures',
        icon: ThumbsUp,
        emojis: [
            '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤏', '✌️',
            '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕',
            '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜',
            '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅',
            '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻',
            '👃', '🧠', '🦷', '🦴', '👀', '👁', '👅', '👄',
        ],
    },
    {
        name: 'hearts',
        label: 'Hearts & Love',
        icon: Heart,
        emojis: [
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
            '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
            '💘', '💝', '💟', '💌', '💋', '💑', '👩‍❤️‍👨', '💏',
            '👩‍❤️‍💋‍👨', '🥰', '😍', '😘', '😻', '💒', '💐', '🌹',
        ],
    },
    {
        name: 'activities',
        label: 'Activities & Sports',
        icon: Sparkles,
        emojis: [
            '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉',
            '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍',
            '🏏', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋',
            '🎽', '🛹', '🛼', '⛸️', '🥌', '🎿', '⛷️', '🏂',
            '🪂', '🏋️', '🤼', '🤸', '🤺', '⛹️', '🤾', '🏌️',
            '🏇', '🧘', '🏄', '🏊', '🤽', '🚣', '🧗', '🚴',
            '🚵', '🎪', '🎭', '🎨', '🎬', '🎤', '🎧', '🎼',
            '🎹', '🥁', '🎷', '🎺', '🎸', '🪕', '🎻', '🎲',
            '♟️', '🎯', '🎳', '🎮', '🎰', '🧩', '🎉', '🎊',
        ],
    },
    {
        name: 'food',
        label: 'Food & Drink',
        icon: Coffee,
        emojis: [
            '🍇', '🍈', '🍉', '🍊', '🍋', '🍌', '🍍', '🥭',
            '🍎', '🍏', '🍐', '🍑', '🍒', '🍓', '🥝', '🍅',
            '🥥', '🥑', '🍆', '🥔', '🥕', '🌽', '🌶️', '🥒',
            '🥬', '🥦', '🧄', '🧅', '🍄', '🥜', '🌰', '🍞',
            '🥐', '🥖', '🥨', '🥯', '🥞', '🧇', '🧀', '🍖',
            '🍗', '🥩', '🥓', '🍔', '🍟', '🍕', '🌭', '🥪',
            '🌮', '🌯', '🥙', '🧆', '🥚', '🍳', '🥘', '🍲',
            '🥣', '🥗', '🍿', '🧈', '🧂', '🥫', '🍱', '🍘',
            '🍙', '🍚', '🍛', '🍜', '🍝', '🍠', '🍢', '🍣',
            '🍤', '🍥', '🥮', '🍡', '🥟', '🥠', '🥡', '🦀',
            '🦞', '🦐', '🦑', '🦪', '🍦', '🍧', '🍨', '🍩',
            '🍪', '🎂', '🍰', '🧁', '🥧', '🍫', '🍬', '🍭',
            '🍮', '🍯', '🍼', '🥛', '☕', '🫖', '🍵', '🍶',
            '🍾', '🍷', '🍸', '🍹', '🍺', '🍻', '🥂', '🥃',
            '🥤', '🧃', '🧉', '🧊', '🥢', '🍽️', '🍴', '🥄',
        ],
    },
    {
        name: 'nature',
        label: 'Animals & Nature',
        icon: Sun,
        emojis: [
            '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼',
            '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔',
            '🐧', '🐦', '🐤', '🦆', '🦅', '🦉', '🦇', '🐺',
            '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞',
            '🐜', '🦗', '🕷️', '🦂', '🐢', '🐍', '🦎', '🦖',
            '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠',
            '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', '🐆',
            '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪', '🐫',
            '🦒', '🦘', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏',
            '🐑', '🦙', '🐐', '🦌', '🐕', '🐩', '🦮', '🐈',
            '🐓', '🦃', '🦚', '🦜', '🦢', '🦩', '🕊️', '🐇',
            '🦝', '🦨', '🦡', '🦦', '🦥', '🐁', '🐀', '🐿️',
            '🦔', '💐', '🌸', '💮', '🏵️', '🌹', '🥀', '🌺',
            '🌻', '🌼', '🌷', '🌱', '🌲', '🌳', '🌴', '🌵',
            '🎋', '🎍', '🌾', '🌿', '☘️', '🍀', '🍁', '🍂',
            '🍃', '🌍', '🌎', '🌏', '🌕', '🌖', '🌗', '🌘',
            '🌑', '🌒', '🌓', '🌔', '🌚', '🌝', '🌞', '⭐',
            '🌟', '✨', '⚡', '☄️', '💫', '🔥', '💧', '🌊',
        ],
    },
];

const filteredEmojis = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) {
        return emojiCategories.find(cat => cat.name === activeCategory.value)?.emojis || [];
    }
    
    // Search across all categories
    const allEmojis = emojiCategories.flatMap(cat => cat.emojis);
    return allEmojis;
});

function selectEmoji(emoji: string) {
    emit('select', emoji);
    searchQuery.value = '';
}

function setCategory(categoryName: string) {
    activeCategory.value = categoryName;
    searchQuery.value = '';
}
</script>

<template>
    <Transition name="emoji-picker">
        <div
            v-if="show"
            class="absolute bottom-full left-0 mb-2 bg-white rounded-2xl shadow-2xl border border-gray-200 w-80 h-96 flex flex-col animate-slide-up z-50"
            @click.stop
        >
            <!-- Search Header -->
            <div class="p-3 border-b border-gray-200">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search emojis..."
                        class="w-full pl-9 pr-3 py-2 bg-gray-50 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    />
                </div>
            </div>

            <!-- Emoji Grid -->
            <div class="flex-1 overflow-y-auto p-3">
                <div class="grid grid-cols-8 gap-1">
                    <button
                        v-for="emoji in filteredEmojis"
                        :key="emoji"
                        type="button"
                        @click="selectEmoji(emoji)"
                        class="w-9 h-9 flex items-center justify-center text-2xl hover:bg-gray-100 rounded-lg transition-colors"
                        :title="emoji"
                    >
                        {{ emoji }}
                    </button>
                </div>
                <div v-if="filteredEmojis.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    No emojis found
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="border-t border-gray-200 px-2 py-2 flex items-center justify-between gap-1 bg-gray-50 rounded-b-2xl">
                <button
                    v-for="category in emojiCategories"
                    :key="category.name"
                    type="button"
                    @click="setCategory(category.name)"
                    :class="activeCategory === category.name ? 'bg-blue-100 text-blue-600' : 'text-gray-500 hover:bg-gray-100'"
                    class="p-2 rounded-lg transition-colors flex-1 flex items-center justify-center"
                    :title="category.label"
                >
                    <component :is="category.icon" class="w-5 h-5" />
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes slide-up {
    from {
        transform: translateY(10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.2s ease-out;
}

.emoji-picker-enter-active,
.emoji-picker-leave-active {
    transition: all 0.2s ease;
}

.emoji-picker-enter-from,
.emoji-picker-leave-to {
    transform: translateY(10px);
    opacity: 0;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
