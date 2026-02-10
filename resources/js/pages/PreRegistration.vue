<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { UserPlus, Clock, Users } from 'lucide-vue-next';

defineProps<{
    preRegistrationMode: boolean;
    allowRegistration: boolean;
    preRegisteredCount: number;
}>();

function formatNumber(num: number): string {
    return num.toLocaleString();
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 flex items-center justify-center p-4">
        <Head title="Pre-Registration - NEMSU Match" />

        <div class="max-w-md w-full">
            <!-- Single card: info only (no manual form) -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-10 flex flex-col justify-center relative overflow-hidden">
                <!-- subtle background accent -->
                <div class="pointer-events-none absolute -right-16 -top-16 w-40 h-40 bg-blue-200/40 blur-3xl rounded-full" />
                <div class="pointer-events-none absolute -left-10 bottom-0 w-32 h-32 bg-cyan-200/40 blur-3xl rounded-full" />

                <!-- Static info; blue pill shows total pre-registered -->
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
                        <UserPlus class="w-8 h-8 text-blue-600" />
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        Get Early Access
                    </h2>
                    <p class="text-gray-600 mb-4">
                        Login with your NEMSU account and complete your profile. We'll notify you when NEMSU Match goes live.
                    </p>

                    <!-- Blue pill counter -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-100 to-cyan-100 border border-blue-200 rounded-full shadow-sm mb-8">
                        <Users class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-bold text-blue-900">
                            {{ formatNumber(preRegisteredCount) }} NEMSU students already pre‑registered
                        </span>
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                    </div>

                    <!-- Info / launch status -->
                    <div class="mt-2 pt-6 border-t border-gray-200 text-left">
                        <div class="flex items-start gap-3 text-sm text-gray-600">
                            <Clock class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <p>
                                <span class="font-semibold text-gray-900">Launch Status:</span>
                                We're currently in the final stages of preparation, with
                                <span class="font-semibold text-blue-600">
                                    {{ formatNumber(preRegisteredCount) }} NEMSU students
                                </span>
                                already pre‑registered. Login and complete your profile so you're ready on day one.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    <span class="font-bold text-blue-600">NEMSU Match</span> - Connecting NEMSU Students
                </p>
            </div>
        </div>
    </div>
</template>
