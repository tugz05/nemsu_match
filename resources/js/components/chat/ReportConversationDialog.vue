<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Flag } from 'lucide-vue-next';

interface OtherUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
}

const props = defineProps<{
    open: boolean;
    user: OtherUser | null;
    submitting: boolean;
}>();

const emit = defineEmits<{
    close: [];
    submit: [payload: { reason: string }];
}>();

const selectedReason = ref('inappropriate');
const additionalDetails = ref('');

watch(
    () => props.open,
    (open) => {
        if (open) {
            selectedReason.value = 'inappropriate';
            additionalDetails.value = '';
        }
    }
);

function displayName(u: OtherUser | null): string {
    return u?.display_name || u?.fullname || 'User';
}

function handleClose() {
    emit('close');
}

function handleSubmit() {
    const reason = additionalDetails.value.trim() 
        ? `${selectedReason.value}: ${additionalDetails.value}` 
        : selectedReason.value;
    emit('submit', { reason });
}

const reasons = [
    { value: 'inappropriate', label: 'Inappropriate Content', sub: 'Offensive or explicit messages' },
    { value: 'harassment', label: 'Harassment or Bullying', sub: 'Threatening or abusive behavior' },
    { value: 'spam', label: 'Spam', sub: 'Unwanted or repetitive messages' },
    { value: 'scam', label: 'Scam or Fraud', sub: 'Suspicious or fraudulent activity' },
    { value: 'impersonation', label: 'Impersonation', sub: 'Pretending to be someone else' },
    { value: 'other', label: 'Other', sub: 'Something else' },
];
</script>

<template>
    <div
        v-if="open && user"
        class="fixed inset-0 bg-black/50 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="handleClose"
    >
        <div class="bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl animate-slide-up flex flex-col max-h-[90vh] sm:max-h-[85vh]">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <Flag class="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Report Conversation</h3>
                        <p class="text-xs text-gray-500">with {{ displayName(user) }}</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="handleClose" 
                    :disabled="submitting"
                    class="p-2 hover:bg-gray-100 rounded-full transition-colors disabled:opacity-50"
                >
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-sm text-gray-600 mb-4">
                    Help us understand what's wrong with this conversation. Your report is anonymous and will be reviewed by our team.
                </p>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-900 mb-3">Select a reason</label>
                    <div class="space-y-2">
                        <label
                            v-for="opt in reasons"
                            :key="opt.value"
                            class="flex items-start gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                            :class="selectedReason === opt.value ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input 
                                type="radio" 
                                v-model="selectedReason" 
                                :value="opt.value" 
                                class="w-4 h-4 text-blue-600 mt-0.5 shrink-0" 
                            />
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 text-sm">{{ opt.label }}</div>
                                <div class="text-xs text-gray-500">{{ opt.sub }}</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Additional Details (Optional)</label>
                    <textarea
                        v-model="additionalDetails"
                        placeholder="Provide more context about why you're reporting this conversation..."
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors resize-none"
                        rows="3"
                        maxlength="500"
                        :disabled="submitting"
                    />
                    <div class="text-right text-xs text-gray-500 mt-1">{{ additionalDetails.length }}/500</div>
                </div>
                
                <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="submitting"
                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-3 rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <Flag class="w-5 h-5" />
                    <span v-if="submitting">Submitting Report...</span>
                    <span v-else>Submit Report</span>
                </button>
                
                <p class="text-xs text-center text-gray-500 mt-3">
                    Reports are reviewed by our moderation team and kept confidential. False reports may result in account restrictions.
                </p>
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
