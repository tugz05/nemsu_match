<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, IdCard, AlertCircle, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps<{
    user: {
        id: number;
        email: string;
        display_name?: string;
    };
}>();

const form = useForm({
    part1: '',
    part2: '',
});

const errorMessage = ref<string | null>(null);

const isComplete = computed(() => form.part1.length === 2 && form.part2.length === 5);

function goBack() {
    router.visit('/nemsu/logout');
}

function handleInput(e: Event, part: 'part1' | 'part2', maxLength: number) {
    const target = e.target as HTMLInputElement;
    let value = target.value.replace(/[^0-9]/g, '');
    if (value.length > maxLength) value = value.slice(0, maxLength);
    form[part] = value;
}

async function submit() {
    errorMessage.value = null;
    if (!isComplete.value) {
        errorMessage.value = 'Please enter your full NEMSU ID in the format 00-00000.';
        return;
    }

    const studentId = `${form.part1}-${form.part2}`;
    form.transform(() => ({ student_id: studentId })).post('/student-id', {
        preserveScroll: true,
        onError: (errors) => {
            errorMessage.value = (errors.student_id as string) || 'Unable to save your ID. Please try again.';
        },
    });
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-blue-50 via-sky-50 to-white flex flex-col">
        <Head title="Verify your NEMSU ID" />

        <!-- Header -->
        <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-blue-100">
            <div class="max-w-md mx-auto px-4 py-3 flex items-center gap-3">
                <button
                    type="button"
                    @click="goBack"
                    class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors"
                    aria-label="Back"
                >
                    <ChevronLeft class="w-5 h-5 text-gray-700" />
                </button>
                <div class="flex items-center gap-2">
                    <IdCard class="w-5 h-5 text-blue-600" />
                    <h1 class="font-semibold text-gray-900 text-sm">Verify your NEMSU ID</h1>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 flex items-center">
            <div class="w-full max-w-md mx-auto px-4 py-8">
                <div class="bg-white rounded-3xl shadow-xl border border-blue-50 px-6 py-7 space-y-6">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-widest">Step 1 of 2</p>
                        <h2 class="text-xl font-bold text-gray-900">
                            Link your NEMSU Student ID
                        </h2>
                        <p class="text-sm text-gray-600">
                            You signed in with
                            <span class="font-semibold text-gray-900">{{ props.user.email }}</span>.
                            To keep NEMSU Match safe, we need to confirm your official Student ID number.
                        </p>
                    </div>

                    <!-- OTP-style ID input -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-gray-800">
                            NEMSU ID Number
                            <span class="ml-1 text-xs font-normal text-gray-500">(format: 00-00000)</span>
                        </label>
                        <div class="flex items-center justify-center gap-3">
                            <input
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="2"
                                class="w-16 h-12 rounded-xl border-2 border-gray-200 text-center text-lg font-semibold tracking-[0.5em] focus:border-blue-500 focus:outline-none"
                                :value="form.part1"
                                @input="(e) => handleInput(e, 'part1', 2)"
                            />
                            <span class="text-lg font-semibold text-gray-500">-</span>
                            <input
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="5"
                                class="w-32 h-12 rounded-xl border-2 border-gray-200 text-center text-lg font-semibold tracking-[0.5em] focus:border-blue-500 focus:outline-none"
                                :value="form.part2"
                                @input="(e) => handleInput(e, 'part2', 5)"
                            />
                        </div>
                        <p class="text-xs text-gray-500 text-center">
                            First two digits are the last two digits of your enrollment year.
                        </p>
                    </div>

                    <!-- Info / error -->
                    <div v-if="errorMessage" class="flex items-start gap-2 rounded-2xl bg-red-50 border border-red-100 px-3 py-2.5">
                        <AlertCircle class="w-4 h-4 mt-0.5 text-red-500" />
                        <p class="text-xs text-red-700">{{ errorMessage }}</p>
                    </div>
                    <div v-else class="flex items-start gap-2 rounded-2xl bg-blue-50 border border-blue-100 px-3 py-2.5">
                        <CheckCircle2 class="w-4 h-4 mt-0.5 text-blue-500" />
                        <p class="text-xs text-blue-700">
                            We only use your Student ID to keep accounts unique and prevent spam. It isn’t shown publicly.
                        </p>
                    </div>

                    <!-- Actions -->
                    <button
                        type="button"
                        class="w-full py-3.5 rounded-2xl text-sm font-semibold flex items-center justify-center gap-2 text-white transition-colors"
                        :class="isComplete ? 'bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600' : 'bg-gray-300 cursor-not-allowed'"
                        :disabled="!isComplete || form.processing"
                        @click="submit"
                    >
                        Save & continue
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

