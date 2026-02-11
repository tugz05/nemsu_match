<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, Heart, Sparkles, Zap, Eye, Filter } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    plus_monthly_price: number;
    freemium_enabled: boolean;
}>();
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
        <Head title="NEMSU Match Plus" />
        <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-gray-100 px-4 py-3 flex items-center gap-2">
            <button type="button" @click="router.visit('/like-you')" class="p-2 -ml-2 rounded-full hover:bg-gray-100">
                <ChevronLeft class="w-6 h-6 text-gray-700" />
            </button>
            <h1 class="text-lg font-bold text-gray-900">NEMSU Match Plus</h1>
        </header>
        <main class="max-w-md mx-auto px-4 py-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 text-white shadow-xl mb-4">
                    <Heart class="w-10 h-10 fill-white" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Unlock more connections</h2>
                <p class="text-gray-600 mt-2">Unlimited likes, see who liked you, boost your profile, and more.</p>
            </div>
            <ul class="space-y-4 mb-8">
                <li class="flex items-center gap-3 p-3 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <Zap class="w-5 h-5 text-amber-500 shrink-0" />
                    <span><strong>Unlimited likes</strong> — Swipe without a daily limit.</span>
                </li>
                <li class="flex items-center gap-3 p-3 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <Eye class="w-5 h-5 text-blue-500 shrink-0" />
                    <span><strong>See who liked you</strong> — Match back with everyone who likes you.</span>
                </li>
                <li class="flex items-center gap-3 p-3 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <Sparkles class="w-5 h-5 text-pink-500 shrink-0" />
                    <span><strong>Boost</strong> — Get more visibility for a period.</span>
                </li>
                <li class="flex items-center gap-3 p-3 rounded-xl bg-white border border-gray-100 shadow-sm">
                    <Filter class="w-5 h-5 text-teal-500 shrink-0" />
                    <span><strong>Extra filters</strong> — Campus, program, and year.</span>
                </li>
            </ul>
            <div class="rounded-2xl border-2 border-blue-200 bg-blue-50/50 p-6 text-center">
                <p class="text-3xl font-bold text-gray-900">₱{{ plus_monthly_price }}<span class="text-base font-normal text-gray-500">/month</span></p>
                <p class="text-sm text-gray-600 mt-1">Cancel anytime</p>
                <Button class="mt-4 w-full py-6 rounded-xl text-base font-semibold bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600" disabled>
                    Subscribe — Coming soon
                </Button>
                <p class="text-xs text-gray-500 mt-3">Payment integration coming soon. Contact admin to get Plus access.</p>
            </div>
        </main>
    </div>
</template>
