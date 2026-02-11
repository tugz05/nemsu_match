<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Sparkles } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const page = usePage();
const appLogoUrl = page.props.branding?.app_logo_url ?? null;
const nemsuLogoUrl = '/storage/logo/nemsu.png';

const handleGoogleSignIn = () => {
    window.location.href = '/oauth/nemsu/redirect';
};
</script>

<template>
    <div class="login-page min-h-screen flex items-center justify-center p-4 lg:p-8 overflow-hidden relative">
        <Head title="NEMSU Match - Find Your Perfect Match" />

        <!-- Animated gradient orbs (background) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="orb orb-1" />
            <div class="orb orb-2" />
            <div class="orb orb-3" />
            <div class="gradient-mesh" />
        </div>

        <div class="w-full max-w-md lg:max-w-6xl relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-16">
                <!-- Left: Animated scene (desktop) -->
                <div class="hidden lg:block lg:flex-1">
                    <div class="relative w-full max-w-lg mx-auto login-scene">
                        <div class="absolute top-0 left-12 w-32 h-32 animate-float-1">
                            <div class="heart-large" />
                        </div>
                        <div class="absolute top-0 right-12 w-36 h-36 animate-float-2 flex items-center justify-center">
                            <div class="ballpen" />
                        </div>
                        <div class="absolute top-28 left-0 w-28 h-28 animate-float-3">
                            <div class="heart-medium" />
                        </div>
                        <div class="absolute top-32 left-1/2 -translate-x-1/2 w-44 h-44 animate-float-logo z-20">
                            <div class="logo-square">
                                <img :src="nemsuLogoUrl" alt="NEMSU Logo" class="w-full h-full object-contain p-4" />
                            </div>
                        </div>
                        <div class="absolute top-28 right-0 w-32 h-32 animate-float-4">
                            <div class="notebook" />
                        </div>
                        <div class="absolute bottom-28 left-8 w-32 h-32 animate-float-5">
                            <div class="heart-large" />
                        </div>
                        <div class="absolute bottom-32 left-1/2 -translate-x-1/2 w-36 h-36 animate-float-6 flex items-center justify-center">
                            <div class="ballpen ballpen-rotated" />
                        </div>
                        <div class="absolute bottom-28 right-8 w-32 h-32 animate-float-7">
                            <div class="heart-medium" />
                        </div>
                    </div>
                </div>

                <!-- Mobile: compact scene -->
                <div class="lg:hidden mb-6">
                    <div class="relative w-full h-52 max-w-sm mx-auto">
                        <div class="absolute top-0 left-8 w-14 h-14 animate-float-1">
                            <div class="heart-small" />
                        </div>
                        <div class="absolute top-0 right-8 w-16 h-16 animate-float-2 flex items-center justify-center">
                            <div class="ballpen-small" />
                        </div>
                        <div class="absolute top-6 left-1/2 -translate-x-1/2 w-28 h-28 animate-float-logo z-10">
                            <div class="logo-square-mobile">
                                <img :src="nemsuLogoUrl" alt="NEMSU Logo" class="w-full h-full object-contain p-2" />
                            </div>
                        </div>
                        <div class="absolute top-20 left-0 w-12 h-12 animate-float-3">
                            <div class="heart-small" />
                        </div>
                        <div class="absolute top-20 right-0 w-14 h-14 animate-float-4">
                            <div class="notebook-small" />
                        </div>
                        <div class="absolute bottom-0 left-12 w-14 h-14 animate-float-5">
                            <div class="heart-small" />
                        </div>
                        <div class="absolute bottom-0 right-12 w-16 h-16 animate-float-6 flex items-center justify-center">
                            <div class="ballpen-small" />
                        </div>
                    </div>
                </div>

                <!-- Right: Content card -->
                <div class="flex-1">
                    <div class="content-card text-center lg:text-left">
                        <!-- Header with optional app logo -->
                        <div class="mb-8 lg:mb-10">
                            <div class="flex items-center justify-center lg:justify-start gap-3 mb-6">
                                <div
                                    v-if="appLogoUrl"
                                    class="w-12 h-12 rounded-2xl overflow-hidden bg-white/80 shadow-md flex items-center justify-center shrink-0 ring-2 ring-blue-200/50"
                                >
                                    <img :src="appLogoUrl" alt="Logo" class="w-9 h-9 object-contain" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl lg:text-3xl font-bold text-blue-600">NEMSU</span>
                                    <span class="text-2xl lg:text-3xl font-bold text-gray-900">Match</span>
                                </div>
                            </div>

                            <h1 class="text-3xl lg:text-5xl xl:text-6xl font-bold text-gray-900 mb-4 lg:mb-5 leading-tight">
                                Find your
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">first</span>
                                <br />
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-blue-600">perfect</span>
                                matches
                            </h1>

                            <p class="text-sm lg:text-base text-gray-600 max-w-md mx-auto lg:mx-0 flex items-center justify-center lg:justify-start gap-2">
                                <Sparkles class="w-4 h-4 text-amber-500 shrink-0" />
                                Connect with fellow NEMSU students who get you
                            </p>
                        </div>

                        <!-- CTA -->
                        <div class="space-y-4 mb-6">
                            <Button
                                @click="handleGoogleSignIn"
                                class="cta-button w-full lg:w-auto font-semibold py-5 lg:py-6 px-8 lg:px-10 rounded-2xl text-base lg:text-lg flex items-center justify-center gap-3 group"
                            >
                                <svg class="w-5 h-5 lg:w-6 lg:h-6 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                                </svg>
                                Continue with Google
                                <svg
                                    class="w-5 h-5 lg:w-6 lg:h-6 group-hover:translate-x-1 transition-transform shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Button>
                            <p class="text-xs lg:text-sm text-gray-500/90 px-2">
                                Use your <span class="font-semibold text-blue-600">@nemsu.edu.ph</span> account — quick & secure
                            </p>
                        </div>

                        <div
                            v-if="status"
                            class="mb-6 p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200/80 text-sm font-medium text-emerald-700 flex items-center justify-center gap-2"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                            {{ status }}
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200/80">
                            <p class="text-xs font-semibold text-gray-400 tracking-widest uppercase">
                                Exclusively for NEMSU students
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Page background & orbs */
.login-page {
    background: linear-gradient(160deg, #f8fafc 0%, #f1f5f9 35%, #e0f2fe 70%, #f0f9ff 100%);
}

.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: orb-float 12s ease-in-out infinite;
}

.orb-1 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.35) 0%, rgba(6, 182, 212, 0.25) 100%);
    top: -10%;
    left: -5%;
}

.orb-2 {
    width: 320px;
    height: 320px;
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.3) 0%, rgba(59, 130, 246, 0.2) 100%);
    bottom: -5%;
    right: -5%;
    animation-delay: -4s;
}

.orb-3 {
    width: 240px;
    height: 240px;
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(236, 72, 153, 0.1) 100%);
    top: 50%;
    left: 40%;
    animation-delay: -8s;
}

@keyframes orb-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(20px, -30px) scale(1.05); }
    66% { transform: translate(-15px, 20px) scale(0.95); }
}

.gradient-mesh {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 50% at 20% 40%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse 60% 40% at 80% 60%, rgba(6, 182, 212, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.content-card {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 2rem;
    padding: 2rem 1.5rem;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.04),
        0 0 0 1px rgba(59, 130, 246, 0.06);
}

@media (min-width: 1024px) {
    .content-card {
        padding: 2.5rem 2rem;
    }
}

.cta-button {
    background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);
    color: white;
    box-shadow: 0 10px 30px -5px rgba(37, 99, 235, 0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 40px -5px rgba(37, 99, 235, 0.45);
}

.login-scene {
    height: 600px;
}

/* Heart Shapes */
.heart-large,
.heart-medium,
.heart-small {
    position: relative;
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 10px 20px rgba(59, 130, 246, 0.3));
}

.heart-large::before,
.heart-large::after,
.heart-medium::before,
.heart-medium::after,
.heart-small::before,
.heart-small::after {
    content: "";
    position: absolute;
    background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
    border-radius: 50px 50px 0 0;
}

.heart-large::before,
.heart-large::after {
    width: 64px;
    height: 100px;
}

.heart-medium::before,
.heart-medium::after {
    width: 56px;
    height: 88px;
}

.heart-small::before,
.heart-small::after {
    width: 28px;
    height: 44px;
}

.heart-large::before,
.heart-medium::before,
.heart-small::before {
    left: 50%;
    transform: rotate(-45deg);
    transform-origin: 0 100%;
}

.heart-large::after,
.heart-medium::after,
.heart-small::after {
    left: 0;
    transform: rotate(45deg);
    transform-origin: 100% 100%;
}

/* Ballpen */
.ballpen,
.ballpen-small {
    position: relative;
    width: 80px;
    height: 8px;
    background: linear-gradient(90deg, #1e40af 0%, #3b82f6 70%, #60a5fa 100%);
    border-radius: 4px;
    filter: drop-shadow(0 4px 12px rgba(30, 64, 175, 0.4));
}

.ballpen::before,
.ballpen-small::before {
    content: "";
    position: absolute;
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(30, 64, 175, 0.5);
}

.ballpen::after,
.ballpen-small::after {
    content: "";
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-top: 4px solid transparent;
    border-bottom: 4px solid transparent;
    border-right: 8px solid #1e40af;
}

.ballpen-small {
    width: 50px;
    height: 6px;
}

.ballpen-rotated {
    transform: rotate(-45deg);
}

/* Notebook */
.notebook,
.notebook-small {
    position: relative;
    width: 90px;
    height: 110px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 8px;
    border: 3px solid #3b82f6;
    filter: drop-shadow(0 8px 16px rgba(59, 130, 246, 0.3));
}

.notebook::before,
.notebook-small::before {
    content: "";
    position: absolute;
    top: 8px;
    left: 8px;
    right: 8px;
    height: 2px;
    background: linear-gradient(90deg, #93c5fd 0%, #3b82f6 100%);
}

.notebook::after,
.notebook-small::after {
    content: "";
    position: absolute;
    top: 20px;
    left: 8px;
    right: 8px;
    height: 2px;
    background: linear-gradient(90deg, #93c5fd 0%, #3b82f6 100%);
}

.notebook-small {
    width: 55px;
    height: 65px;
}

.notebook-small::before {
    top: 6px;
    left: 6px;
    right: 6px;
}

.notebook-small::after {
    top: 14px;
    left: 6px;
    right: 6px;
}

/* NEMSU Logo Square */
.logo-square {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
    border-radius: 20px;
    border: 4px solid #3b82f6;
    box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4),
                0 0 0 8px rgba(59, 130, 246, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.logo-square::before {
    content: "";
    position: absolute;
    inset: -50%;
    background: conic-gradient(
        from 0deg,
        transparent 0%,
        rgba(59, 130, 246, 0.1) 25%,
        transparent 50%,
        rgba(6, 182, 212, 0.1) 75%,
        transparent 100%
    );
    animation: rotate-gradient 8s linear infinite;
}

.logo-square-mobile {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
    border-radius: 12px;
    border: 3px solid #3b82f6;
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4),
                0 0 0 4px rgba(59, 130, 246, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Animations */
@keyframes float-up-down {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-15px) rotate(5deg);
    }
}

@keyframes float-up-down-rotate {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(-10deg);
    }
}

@keyframes float-scale {
    0%, 100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-12px) scale(1.05);
    }
}

@keyframes pulse-glow {
    0%, 100% {
        transform: translateY(0) scale(1);
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4),
                    0 0 0 8px rgba(59, 130, 246, 0.1);
    }
    50% {
        transform: translateY(-10px) scale(1.03);
        box-shadow: 0 25px 50px rgba(59, 130, 246, 0.5),
                    0 0 0 12px rgba(59, 130, 246, 0.15),
                    0 0 60px rgba(6, 182, 212, 0.3);
    }
}

@keyframes rotate-gradient {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Apply animations */
.animate-float-1 {
    animation: float-up-down 4s ease-in-out infinite;
}

.animate-float-2 {
    animation: float-up-down-rotate 5s ease-in-out infinite;
    animation-delay: 0.5s;
}

.animate-float-3 {
    animation: float-scale 4.5s ease-in-out infinite;
    animation-delay: 1s;
}

.animate-float-4 {
    animation: float-up-down-rotate 5.5s ease-in-out infinite;
    animation-delay: 1.5s;
}

.animate-float-5 {
    animation: float-up-down 4.8s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-float-6 {
    animation: float-scale 5.2s ease-in-out infinite;
    animation-delay: 2.5s;
}

.animate-float-7 {
    animation: float-up-down-rotate 4.3s ease-in-out infinite;
    animation-delay: 3s;
}

.animate-float-logo {
    animation: pulse-glow 6s ease-in-out infinite;
}

/* Additional emphasis effects */
.heart-large,
.heart-medium,
.heart-small {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.ballpen,
.ballpen-small {
    animation: wiggle 3s ease-in-out infinite;
}

@keyframes wiggle {
    0%, 100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-2deg);
    }
    75% {
        transform: rotate(2deg);
    }
}

.notebook,
.notebook-small {
    animation: flip 5s ease-in-out infinite;
}

@keyframes flip {
    0%, 100% {
        transform: perspective(400px) rotateY(0deg);
    }
    50% {
        transform: perspective(400px) rotateY(10deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .orb {
        animation: none;
    }
}
</style>
