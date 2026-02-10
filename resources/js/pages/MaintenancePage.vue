<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Wrench, Clock, Shield, RefreshCw } from 'lucide-vue-next';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 flex items-center justify-center p-4">
        <Head title="Under Maintenance - NEMSU Match" />

        <div class="max-w-2xl w-full">
            <!-- Main Card -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 border border-gray-200 relative overflow-hidden">
                <!-- Decorative Background -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full blur-3xl opacity-30 -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-to-tr from-cyan-100 to-blue-100 rounded-full blur-3xl opacity-30 -ml-32 -mb-32"></div>

                <div class="relative z-10">
                    <!-- Icon -->
                    <div class="flex justify-center mb-8">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-full blur-xl opacity-50 animate-pulse"></div>
                            <div class="relative w-24 h-24 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-full flex items-center justify-center shadow-2xl">
                                <Wrench class="w-12 h-12 text-white animate-wiggle" />
                            </div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                            Under Maintenance
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 mb-2">
                            We're making things better for you!
                        </p>
                        <p class="text-base text-gray-500">
                            NEMSU Match is currently undergoing scheduled maintenance to improve your experience.
                        </p>
                    </div>

                    <!-- Info Cards -->
                    <div class="grid md:grid-cols-2 gap-4 mb-8">
                        <!-- Expected Time -->
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-200 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <Clock class="w-6 h-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-blue-900 mb-1">Expected Duration</h3>
                                    <p class="text-sm text-blue-700">
                                        We'll be back shortly. Please check again in a few minutes.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- What's Happening -->
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-200 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <Shield class="w-6 h-6 text-purple-600" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-purple-900 mb-1">What's Happening</h3>
                                    <p class="text-sm text-purple-700">
                                        System updates, performance improvements, and new features.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Being Updated -->
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <RefreshCw class="w-5 h-5 text-blue-600" />
                            What We're Working On
                        </h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-blue-600 rounded-full mt-2 flex-shrink-0"></div>
                                <p class="text-gray-700">Enhancing system performance and speed</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-cyan-600 rounded-full mt-2 flex-shrink-0"></div>
                                <p class="text-gray-700">Adding new features and improvements</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-blue-600 rounded-full mt-2 flex-shrink-0"></div>
                                <p class="text-gray-700">Optimizing database and server infrastructure</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-cyan-600 rounded-full mt-2 flex-shrink-0"></div>
                                <p class="text-gray-700">Ensuring security and data protection</p>
                            </li>
                        </ul>
                    </div>

                    <!-- Refresh Button -->
                    <div class="text-center">
                        <button
                            @click="$inertia.reload()"
                            class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all duration-300"
                        >
                            <RefreshCw class="w-5 h-5" />
                            Check Status
                        </button>
                        <p class="text-sm text-gray-500 mt-3">
                            Click to check if we're back online
                        </p>
                    </div>

                    <!-- Contact Info -->
                    <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                        <p class="text-sm text-gray-600">
                            Need urgent assistance? Contact our support team at
                            <a href="mailto:support@nemsu.edu.ph" class="text-blue-600 hover:text-blue-700 font-semibold">
                                support@nemsu.edu.ph
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- NEMSU Branding -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    <span class="font-bold text-blue-600">NEMSU Match</span> - Connecting NEMSU Students
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes wiggle {
    0%, 100% {
        transform: rotate(-3deg);
    }
    50% {
        transform: rotate(3deg);
    }
}

.animate-wiggle {
    animation: wiggle 2s ease-in-out infinite;
}
</style>
