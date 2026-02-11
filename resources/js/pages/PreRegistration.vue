<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Heart, Users, BookOpen, Shield, Sparkles, UserCircle } from 'lucide-vue-next';

defineProps<{
    preRegistrationMode: boolean;
    allowRegistration: boolean;
    preRegisteredCount: number;
    preRegisteredFemale: number;
    preRegisteredMale: number;
}>();

function formatNumber(num: number): string {
    return num.toLocaleString();
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-cyan-50 flex items-center justify-center p-4 py-10">
        <Head title="Pre-Registration - NEMSU Match" />

        <!-- Background decoration -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-400/20 rounded-full blur-3xl" />
            <div class="absolute top-1/2 -left-40 w-72 h-72 bg-cyan-400/15 rounded-full blur-3xl" />
            <div class="absolute bottom-20 right-1/4 w-60 h-60 bg-blue-300/10 rounded-full blur-3xl" />
        </div>

        <div class="max-w-2xl w-full relative">
            <div class="bg-white/90 backdrop-blur-sm rounded-[2rem] shadow-xl shadow-blue-900/10 border border-white/80 overflow-hidden">
                <!-- Hero: gradient band + big number -->
                <div class="relative bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-500 px-6 pt-8 pb-10 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIvPjwvZz48L2c+PC9zdmc+')] opacity-60" />
                    <p class="relative text-blue-100 text-sm font-medium mb-1 tracking-wide">
                        NEMSU students already joined
                    </p>
                    <p class="relative text-6xl md:text-7xl font-black text-white leading-none tabular-nums drop-shadow-lg">
                        {{ formatNumber(preRegisteredCount) }}
                    </p>
                    <div class="relative inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-white/20 backdrop-blur-sm rounded-full border border-white/30 shadow-lg">
                        <Users class="w-5 h-5 text-white" />
                        <span class="text-sm font-bold text-white">
                            {{ formatNumber(preRegisteredCount) }} pre‑registered
                        </span>
                        <span class="w-2.5 h-2.5 bg-emerald-300 rounded-full animate-pulse ring-2 ring-white/50" />
                    </div>
                    <!-- Gender breakdown -->
                    <div class="relative flex items-center justify-center gap-4 mt-5">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 backdrop-blur-sm border border-white/25">
                            <UserCircle class="w-4 h-4 text-pink-200" />
                            <span class="text-sm font-semibold text-white">{{ formatNumber(preRegisteredFemale) }} Female</span>
                        </div>
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 backdrop-blur-sm border border-white/25">
                            <UserCircle class="w-4 h-4 text-blue-200" />
                            <span class="text-sm font-semibold text-white">{{ formatNumber(preRegisteredMale) }} Male</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    <!-- What is NEMSU Match -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
                                <BookOpen class="w-4 h-4 text-blue-600" />
                            </span>
                            What is NEMSU Match?
                        </h2>
                        <p class="text-gray-700 leading-relaxed pl-10">
                            <strong class="text-blue-700">NEMSU Match</strong> is the campus-only connection platform for
                            <strong>Northeastern Mindanao State University (NEMSU)</strong> students. A safe, exclusive space to
                            discover peers, find study buddies, build friendships, and connect over shared interests and
                            academic goals—all within your NEMSU community.
                        </p>
                    </div>

                    <!-- Key points -->
                    <div class="grid sm:grid-cols-2 gap-4 mb-6">
                        <div class="flex items-start gap-3 p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-cyan-50/50 border border-blue-100/80">
                            <Heart class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" />
                            <span class="text-gray-700 text-sm leading-snug">Friendships, study groups, and meaningful connections with fellow students.</span>
                        </div>
                        <div class="flex items-start gap-3 p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-cyan-50/50 border border-blue-100/80">
                            <Shield class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-gray-700 text-sm leading-snug">Verified NEMSU accounts only—secure, campus-only environment.</span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="relative rounded-2xl bg-gradient-to-r from-blue-500/10 via-cyan-500/10 to-blue-500/10 border border-blue-200/60 p-5 text-center overflow-hidden">
                        <Sparkles class="absolute top-3 right-4 w-5 h-5 text-amber-400/60" />
                        <p class="relative text-gray-800 text-sm leading-relaxed">
                            <strong class="text-blue-700">Join {{ formatNumber(preRegisteredCount) }} students.</strong>
                            Sign in with your NEMSU account and complete your profile—you’ll be notified when we go live.
                        </p>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-gray-500">
                <span class="font-semibold text-blue-600">NEMSU Match</span>
                <span class="text-gray-400 mx-1">·</span>
                Connecting NEMSU Students
            </p>
        </div>
    </div>
</template>
