<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    status?: string;
}>();

const handleGoogleSignIn = () => {
    // Redirect to Google OAuth with NEMSU workspace domain restriction
    window.location.href = '/oauth/nemsu/redirect';
};
</script>

<template>
    <div class="min-h-screen bg-white flex items-center justify-center p-4 lg:p-8 overflow-hidden relative">
        <Head title="NEMSU Match - Find Your Perfect Match" />

        <div class="w-full max-w-md lg:max-w-6xl relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-16">
                <!-- Left Side: Animated Elements Grid (Hidden on mobile, shown on desktop) -->
                <div class="hidden lg:block lg:flex-1">
                    <div class="relative w-full max-w-lg mx-auto" style="height: 600px">
                        <!-- Heart 1 (Top Left) -->
                        <div class="absolute top-0 left-12 w-32 h-32 animate-float-1">
                            <div class="heart-large"></div>
                        </div>

                        <!-- Ballpen (Top Right) -->
                        <div class="absolute top-0 right-12 w-36 h-36 animate-float-2 flex items-center justify-center">
                            <div class="ballpen"></div>
                        </div>

                        <!-- Heart 2 (Middle Left) -->
                        <div class="absolute top-28 left-0 w-28 h-28 animate-float-3">
                            <div class="heart-medium"></div>
                        </div>

                        <!-- NEMSU Logo Square (Center - Featured) -->
                        <div class="absolute top-32 left-1/2 -translate-x-1/2 w-44 h-44 animate-float-logo z-20">
                            <div class="logo-square">
                                <img src="/storage/logo/nemsu.png" alt="NEMSU Logo" class="w-full h-full object-contain p-4" />
                            </div>
                        </div>

                        <!-- Notebook (Middle Right) -->
                        <div class="absolute top-28 right-0 w-32 h-32 animate-float-4">
                            <div class="notebook"></div>
                        </div>

                        <!-- Heart 3 (Bottom Left) -->
                        <div class="absolute bottom-28 left-8 w-32 h-32 animate-float-5">
                            <div class="heart-large"></div>
                        </div>

                        <!-- Ballpen 2 (Bottom Center) -->
                        <div class="absolute bottom-32 left-1/2 -translate-x-1/2 w-36 h-36 animate-float-6 flex items-center justify-center">
                            <div class="ballpen ballpen-rotated"></div>
                        </div>

                        <!-- Heart 4 (Bottom Right) -->
                        <div class="absolute bottom-28 right-8 w-32 h-32 animate-float-7">
                            <div class="heart-medium"></div>
                        </div>
                    </div>
                </div>

                <!-- Mobile: Compact Animated Elements (Shown only on mobile) -->
                <div class="lg:hidden mb-8">
                    <div class="relative w-full h-56 max-w-sm mx-auto">
                        <!-- Heart (Top Left) -->
                        <div class="absolute top-0 left-8 w-14 h-14 animate-float-1">
                            <div class="heart-small"></div>
                        </div>

                        <!-- Ballpen (Top Right) -->
                        <div class="absolute top-0 right-8 w-16 h-16 animate-float-2 flex items-center justify-center">
                            <div class="ballpen-small"></div>
                        </div>

                        <!-- NEMSU Logo Square (Center - Featured) -->
                        <div class="absolute top-8 left-1/2 -translate-x-1/2 w-28 h-28 animate-float-logo z-10">
                            <div class="logo-square-mobile">
                                <img src="/storage/logo/nemsu.png" alt="NEMSU Logo" class="w-full h-full object-contain p-2" />
                            </div>
                        </div>

                        <!-- Heart (Left) -->
                        <div class="absolute top-20 left-0 w-12 h-12 animate-float-3">
                            <div class="heart-small"></div>
                        </div>

                        <!-- Notebook (Right) -->
                        <div class="absolute top-20 right-0 w-14 h-14 animate-float-4">
                            <div class="notebook-small"></div>
                        </div>

                        <!-- Heart (Bottom Left) -->
                        <div class="absolute bottom-0 left-12 w-14 h-14 animate-float-5">
                            <div class="heart-small"></div>
                        </div>

                        <!-- Ballpen (Bottom Right) -->
                        <div class="absolute bottom-0 right-12 w-16 h-16 animate-float-6 flex items-center justify-center">
                            <div class="ballpen-small"></div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Content -->
                <div class="flex-1 text-center lg:text-left">
                    <!-- Header -->
                    <div class="mb-8 lg:mb-12">
                        <div class="flex items-center justify-center lg:justify-start gap-2 mb-6">
                            <span class="text-2xl lg:text-3xl font-bold text-blue-600">NEMSU</span>
                            <span class="text-2xl lg:text-3xl font-bold text-gray-900">Match</span>
                        </div>

                        <h1 class="text-3xl lg:text-5xl xl:text-6xl font-bold text-gray-900 mb-4 lg:mb-6">
                            Find Your
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">
                                First
                            </span>
                            <br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-blue-500">
                                Perfect
                            </span>
                            Matches
                        </h1>

                        <p class="text-sm lg:text-base text-gray-600 max-w-md mx-auto lg:mx-0">
                            Connect with fellow NEMSU students who share your interests
                        </p>
                    </div>

                    <!-- Get Started Button -->
                    <div class="space-y-4 mb-8">
                        <Button
                            @click="handleGoogleSignIn"
                            class="w-full lg:w-auto bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-5 lg:py-6 px-8 lg:px-12 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 text-base lg:text-lg flex items-center justify-center gap-3 group"
                        >
                            <svg class="w-5 h-5 lg:w-6 lg:h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                            </svg>
                            Continue with Google
                            <svg
                                class="w-5 h-5 lg:w-6 lg:h-6 group-hover:translate-x-1 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </Button>

                        <p class="text-xs lg:text-sm text-gray-500 px-4">
                            Use your <span class="font-semibold text-blue-600">@nemsu.edu.ph</span> account to sign in
                        </p>
                    </div>

                    <!-- Status Message -->
                    <div
                        v-if="status"
                        class="mb-6 p-3 bg-green-50 border border-green-200 rounded-xl text-center text-sm font-medium text-green-600"
                    >
                        {{ status }}
                    </div>

                    <!-- NEMSU Branding -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <p class="text-xs font-semibold text-gray-400 tracking-wide">
                            EXCLUSIVELY FOR NEMSU STUDENTS
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
</style>
