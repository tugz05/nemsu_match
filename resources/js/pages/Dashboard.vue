<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Heart, X, Star, MapPin, Sparkles } from 'lucide-vue-next';

defineProps<{
    user?: {
        display_name?: string;
        profile_picture?: string;
    };
}>();

// Mock data for demo - will be replaced with real data later
const currentProfileIndex = ref(0);

const profiles = ref([
    {
        id: 1,
        name: 'Maria Santos',
        age: 21,
        campus: 'Tandag',
        program: 'BS Computer Science',
        year: '3rd Year',
        distance: '2 km away',
        photo: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=800',
        bio: 'Tech enthusiast, always on the lookout for new coding challenges and ready to share knowledge. Let\'s build something amazing together! 💻',
        interests: ['Coding', 'Gaming', 'Coffee'],
        commonInterests: 4
    },
    {
        id: 2,
        name: 'Juan Dela Cruz',
        age: 22,
        campus: 'Bislig',
        program: 'BS Business Administration',
        year: '4th Year',
        distance: '5 km away',
        photo: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800',
        bio: 'Entrepreneur at heart, passionate about business innovation and creating meaningful connections. Love coffee talks and brainstorming sessions! ☕',
        interests: ['Business', 'Travel', 'Photography'],
        commonInterests: 3
    },
    {
        id: 3,
        name: 'Angela Reyes',
        age: 20,
        campus: 'Tandag',
        program: 'BS Nursing',
        year: '2nd Year',
        distance: '1 km away',
        photo: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=800',
        bio: 'Future nurse with a passion for helping others. Love to read, paint, and explore nature. Looking for someone who values kindness and adventure! 🌿',
        interests: ['Healthcare', 'Art', 'Hiking'],
        commonInterests: 5
    }
]);

const currentProfile = computed(() => profiles.value[currentProfileIndex.value]);

const handleLike = () => {
    console.log('Liked:', currentProfile.value.name);
    nextProfile();
};

const handlePass = () => {
    console.log('Passed:', currentProfile.value.name);
    nextProfile();
};

const handleSuperLike = () => {
    console.log('Super Liked:', currentProfile.value.name);
    nextProfile();
};

const nextProfile = () => {
    if (currentProfileIndex.value < profiles.value.length - 1) {
        currentProfileIndex.value++;
    } else {
        // Reset to beginning or show "no more profiles" message
        currentProfileIndex.value = 0;
    }
};

const activeTab = ref('home');
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 pb-20">
        <Head title="NEMSU Match - Discover" />

        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
            <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                        <Heart class="w-6 h-6 text-white fill-white" />
                    </div>
                    <span class="text-xl font-bold">
                        <span class="text-blue-600">NEMSU</span>
                        <span class="text-gray-900">Match</span>
                    </span>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-md mx-auto px-4 py-6 pb-32">
            <!-- Profile Card -->
            <div v-if="currentProfile" class="relative z-10">
                <!-- Potential Match Badge -->
                <div class="absolute top-4 left-4 z-10 bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-4 py-2 rounded-full shadow-lg flex items-center gap-2 animate-pulse-slow">
                    <Sparkles class="w-4 h-4" />
                    <span class="text-sm font-semibold">Potential Match</span>
                </div>

                <!-- Card -->
                <div class="relative bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.02]">
                    <!-- Image Container -->
                    <div class="relative aspect-[3/4]">
                        <img
                            :src="currentProfile.photo"
                            :alt="currentProfile.name"
                            class="w-full h-full object-cover"
                        />

                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                        <!-- Info Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                            <!-- Name and Age -->
                            <div class="flex items-end gap-3 mb-2">
                                <h2 class="text-3xl font-bold">{{ currentProfile.name }}</h2>
                                <span class="text-2xl mb-1">{{ currentProfile.age }}</span>
                            </div>

                            <!-- Program and Year -->
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="text-sm font-medium">{{ currentProfile.program }}</span>
                                <span class="text-sm">•</span>
                                <span class="text-sm">{{ currentProfile.year }}</span>
                            </div>

                            <!-- Campus and Distance -->
                            <div class="flex items-center gap-4 mb-3">
                                <div class="flex items-center gap-1.5">
                                    <MapPin class="w-4 h-4" />
                                    <span class="text-sm">{{ currentProfile.campus }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm">{{ currentProfile.distance }}</span>
                                </div>
                            </div>

                            <!-- Common Interests Badge -->
                            <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full">
                                <Sparkles class="w-4 h-4 text-yellow-300" />
                                <span class="text-sm font-semibold">{{ currentProfile.commonInterests }} Common Interests</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bio Section -->
                    <div class="p-6 bg-white">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Bio</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ currentProfile.bio }}</p>

                        <!-- Interests Tags -->
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Interests</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="interest in currentProfile.interests"
                                    :key="interest"
                                    class="px-3 py-1.5 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 text-blue-700 text-sm font-medium rounded-full"
                                >
                                    {{ interest }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons - Floating and Always on Top -->
                <div class="fixed bottom-24 left-1/2 -translate-x-1/2 z-40 max-w-md w-full px-4">
                    <div class="flex items-center justify-center gap-6">
                        <!-- Pass Button -->
                        <button
                            @click="handlePass"
                            class="w-16 h-16 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(0,0,0,0.3)] hover:scale-110 transition-all group active:scale-95"
                        >
                            <X class="w-8 h-8 text-gray-500 group-hover:text-red-500 transition-colors" stroke-width="2.5" />
                        </button>

                        <!-- Super Like Button -->
                        <button
                            @click="handleSuperLike"
                            class="w-14 h-14 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(37,99,235,0.5)] hover:scale-110 transition-all active:scale-95"
                        >
                            <Star class="w-7 h-7 text-white fill-white" />
                        </button>

                        <!-- Like Button -->
                        <button
                            @click="handleLike"
                            class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-full flex items-center justify-center shadow-2xl hover:shadow-[0_20px_50px_rgba(37,99,235,0.5)] hover:scale-110 transition-all group active:scale-95"
                        >
                            <Heart class="w-8 h-8 text-white group-hover:fill-white transition-all" stroke-width="2.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- No More Profiles -->
            <div v-else class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Heart class="w-10 h-10 text-blue-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No More Profiles</h3>
                <p class="text-gray-600">Check back later for new matches!</p>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
            <div class="max-w-md mx-auto px-6 py-3">
                <div class="flex items-center justify-between">
                    <!-- Home -->
                    <button
                        @click="router.visit('/home')"
                        class="flex flex-col items-center gap-1 py-2 px-4 transition-all text-gray-400"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-xs font-semibold">Home</span>
                    </button>

                    <!-- Discover -->
                    <button
                        @click="activeTab = 'discover'"
                        class="flex flex-col items-center gap-1 py-2 px-4 transition-all"
                        :class="activeTab === 'discover' ? 'text-blue-600' : 'text-gray-400'"
                    >
                        <svg class="w-6 h-6" :fill="activeTab === 'discover' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="text-xs font-semibold">Discover</span>
                    </button>

                    <!-- Like You -->
                    <button
                        @click="activeTab = 'likeyou'"
                        class="flex flex-col items-center gap-1 py-2 px-4 transition-all relative"
                        :class="activeTab === 'likeyou' ? 'text-blue-600' : 'text-gray-400'"
                    >
                        <div class="relative">
                            <Heart class="w-6 h-6" :fill="activeTab === 'likeyou' ? 'currentColor' : 'none'" />
                            <span class="absolute -top-1 -right-2 w-5 h-5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                                3
                            </span>
                        </div>
                        <span class="text-xs font-semibold">Like You</span>
                    </button>

                    <!-- Chat -->
                    <button
                        @click="activeTab = 'chat'"
                        class="flex flex-col items-center gap-1 py-2 px-4 transition-all relative"
                        :class="activeTab === 'chat' ? 'text-blue-600' : 'text-gray-400'"
                    >
                        <div class="relative">
                            <svg class="w-6 h-6" :fill="activeTab === 'chat' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span class="absolute -top-1 -right-2 w-5 h-5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                                2
                            </span>
                        </div>
                        <span class="text-xs font-semibold">Chat</span>
                    </button>

                    <!-- Account -->
                    <button
                        @click="router.visit('/account')"
                        class="flex flex-col items-center gap-1 py-2 px-4 transition-all text-gray-400"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs font-semibold">Account</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}
</style>
