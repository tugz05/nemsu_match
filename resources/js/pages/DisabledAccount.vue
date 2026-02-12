<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Send, LogOut } from 'lucide-vue-next';

const props = defineProps<{
    user: {
        id: number;
        display_name: string;
        email: string;
        is_disabled: boolean;
        disabled_reason: string | null;
        disabled_at: string | null;
    };
    latestAppeal: {
        id: number;
        message: string;
        status: string;
        review_notes: string | null;
        created_at: string | null;
    } | null;
}>();

const message = ref('');
const loading = ref(false);
const feedback = ref('');

async function submitAppeal() {
    if (loading.value) return;
    loading.value = true;
    feedback.value = '';
    try {
        const res = await fetch('/api/account-disabled/appeal', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ message: message.value }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            feedback.value = data.message || 'Unable to submit appeal.';
            return;
        }
        feedback.value = data.message || 'Appeal submitted.';
        message.value = '';
    } catch (e) {
        console.error(e);
        feedback.value = 'Unable to submit appeal.';
    } finally {
        loading.value = false;
    }
}

function logout() {
    router.post('/nemsu/logout');
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <Head title="Account Disabled" />
        <div class="w-full max-w-2xl bg-white rounded-3xl border border-gray-200 shadow-xl p-6 md:p-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <AlertTriangle class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Your account is disabled</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        You currently cannot access the <span class="font-semibold">Browse</span> page.
                        If you believe this was a mistake, submit an appeal below.
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-gray-50 border border-gray-200 p-4 text-sm">
                <p><span class="font-semibold text-gray-900">Account:</span> {{ user.display_name }} ({{ user.email }})</p>
                <p class="mt-1"><span class="font-semibold text-gray-900">Disabled at:</span> {{ user.disabled_at ? new Date(user.disabled_at).toLocaleString() : 'N/A' }}</p>
                <p class="mt-1"><span class="font-semibold text-gray-900">Reason:</span> {{ user.disabled_reason || 'Policy violation after moderation review.' }}</p>
            </div>

            <div v-if="latestAppeal" class="mt-4 rounded-2xl bg-blue-50 border border-blue-200 p-4 text-sm">
                <p class="font-semibold text-blue-700 capitalize">Latest appeal: {{ latestAppeal.status }}</p>
                <p class="text-blue-800 mt-1">{{ latestAppeal.message }}</p>
                <p v-if="latestAppeal.review_notes" class="text-blue-700 mt-2"><span class="font-semibold">Review notes:</span> {{ latestAppeal.review_notes }}</p>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Submit an appeal</label>
                <textarea
                    v-model="message"
                    rows="5"
                    placeholder="Explain your side clearly and respectfully (minimum 20 characters)."
                    class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <div class="mt-3 flex flex-wrap gap-2 items-center justify-between">
                    <p class="text-xs text-gray-500">Your appeal will be reviewed by the superadmin team.</p>
                    <button :disabled="loading || message.trim().length < 20" @click="submitAppeal" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold disabled:opacity-50">
                        <Send class="w-4 h-4" />
                        {{ loading ? 'Submitting...' : 'Submit Appeal' }}
                    </button>
                </div>
                <p v-if="feedback" class="mt-3 text-sm text-gray-700">{{ feedback }}</p>
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <button @click="logout" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-600 font-semibold text-sm hover:bg-red-100">
                    <LogOut class="w-4 h-4" />
                    Logout
                </button>
            </div>
        </div>
    </div>
</template>

