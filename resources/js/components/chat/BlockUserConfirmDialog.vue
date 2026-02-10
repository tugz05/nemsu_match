<script setup lang="ts">
import { Ban } from 'lucide-vue-next';

interface OtherUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
}

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

const props = defineProps<{
    open: boolean;
    user: OtherUser | null;
    blocking: boolean;
}>();

function displayName(u: OtherUser | null): string {
    return u?.display_name || u?.fullname || 'User';
}
</script>

<template>
    <div
        v-if="open && user"
        class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4"
        @click.self="emit('close')"
    >
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <Ban class="w-8 h-8 text-blue-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Block {{ displayName(user) }}?</h3>
                <p class="text-gray-600 text-sm mb-6">
                    Are you sure you want to block this user? This will:
                </p>
                <div class="bg-blue-50 rounded-xl p-4 mb-6 w-full">
                    <ul class="text-left text-sm text-gray-700 space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold mt-0.5">•</span>
                            <span>Hide all conversations with this user</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold mt-0.5">•</span>
                            <span>Prevent them from contacting you</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold mt-0.5">•</span>
                            <span>Remove them from your matches</span>
                        </li>
                    </ul>
                </div>
                <div class="flex gap-3 w-full">
                    <button
                        type="button"
                        @click="emit('close')"
                        :disabled="blocking"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="emit('confirm')"
                        :disabled="blocking"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                    >
                        <span v-if="blocking">Blocking...</span>
                        <span v-else>Block</span>
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
