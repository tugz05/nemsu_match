<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { User, MapPin, GraduationCap, Calendar, Heart, Book, Target, Trophy, ChevronLeft, MessageCircle } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';

const props = defineProps<{
    profileUser: {
        id: number;
        display_name: string;
        fullname: string;
        campus: string;
        academic_program: string;
        year_level: string;
        profile_picture: string | null;
        bio: string;
        date_of_birth: string | null;
        gender: string;
        courses?: string[] | unknown;
        research_interests?: string[] | unknown;
        extracurricular_activities?: string[] | unknown;
        academic_goals?: string[] | unknown;
        interests?: string[] | unknown;
        following_count?: number;
        followers_count?: number;
        posts_count?: number;
        member_since?: string | null;
    };
    is_followed_by_user: boolean;
}>();

function ensureStringArray(value: unknown): string[] {
    if (value == null) return [];
    if (Array.isArray(value)) return value.filter((item): item is string => typeof item === 'string');
    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed.filter((item: unknown): item is string => typeof item === 'string') : [];
        } catch {
            return [];
        }
    }
    return [];
}

const courses = computed(() => ensureStringArray(props.profileUser.courses));
const researchInterests = computed(() => ensureStringArray(props.profileUser.research_interests));
const extracurricularActivities = computed(() => ensureStringArray(props.profileUser.extracurricular_activities));
const academicGoals = computed(() => ensureStringArray(props.profileUser.academic_goals));
const interests = computed(() => ensureStringArray(props.profileUser.interests));

const isFollowed = ref(props.is_followed_by_user);
const followLoading = ref(false);
const activeTab = ref<'about' | 'posts' | 'achievements'>('about');

const age = computed(() => {
    if (!props.profileUser.date_of_birth) return null;
    const birthDate = new Date(props.profileUser.date_of_birth);
    const today = new Date();
    let a = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) a--;
    return a;
});

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const toggleFollow = async () => {
    followLoading.value = true;
    try {
        const response = await fetch(`/api/users/${props.profileUser.id}/follow`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        });
        if (response.ok) {
            const data = await response.json();
            isFollowed.value = data.following;
        }
    } catch (e) {
        console.error('Follow failed', e);
    } finally {
        followLoading.value = false;
    }
};

const goBack = () => router.visit('/home');
const navigateToHome = () => router.visit('/home');
const navigateToDiscover = () => router.visit('/dashboard');
const navigateToAccount = () => router.visit('/account');
const openChat = () => router.visit(`/chat?user=${props.profileUser.id}`);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head :title="`${profileUser.display_name} - NEMSU Match`" />

        <!-- Top bar: back | username | Follow -->
        <div class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900 truncate flex-1 text-center mx-2">{{ profileUser.display_name || 'Profile' }}</h1>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="openChat"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-full bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors"
                        aria-label="Message"
                    >
                        <MessageCircle class="w-4 h-4 shrink-0" />
                        <span>Message</span>
                    </button>
                    <button
                        @click="toggleFollow"
                        :disabled="followLoading"
                        class="px-4 py-2 rounded-full text-sm font-semibold transition-colors disabled:opacity-50"
                        :class="isFollowed ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700'"
                    >
                        {{ followLoading ? '...' : (isFollowed ? 'Following' : 'Follow') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <!-- Profile header: avatar (left) + name & member info (right) -->
            <div class="flex gap-4 items-start mb-4">
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 rounded-full border-4 border-white shadow-lg bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                        <img
                            v-if="profileUser.profile_picture"
                            :src="profilePictureSrc(profileUser.profile_picture)"
                            :alt="profileUser.display_name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 text-3xl font-bold">
                            {{ profileUser.display_name?.charAt(0).toUpperCase() }}
                        </div>
                    </div>
                </div>
                <div class="flex-1 min-w-0 pt-1">
                    <h2 class="text-xl font-bold text-gray-900">{{ profileUser.fullname }}</h2>
                    <p class="text-sm text-gray-500">{{ profileUser.display_name }}</p>
                    <p v-if="profileUser.member_since" class="text-xs text-gray-500 mt-0.5">Member for {{ profileUser.member_since }}</p>
                </div>
            </div>

            <!-- Stats row: Following | Followers | Posts -->
            <div class="grid grid-cols-3 gap-4 py-4 border-y border-gray-200 mb-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ profileUser.following_count ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Following</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ profileUser.followers_count ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Followers</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ profileUser.posts_count ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Posts</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-4">
                <button
                    @click="activeTab = 'about'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :class="activeTab === 'about' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    About
                </button>
                <button
                    @click="activeTab = 'posts'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :class="activeTab === 'posts' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Posts
                </button>
                <button
                    @click="activeTab = 'achievements'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :class="activeTab === 'achievements' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Achievements
                </button>
            </div>

            <!-- Tab content: About -->
            <div v-show="activeTab === 'about'" class="space-y-6">
                <!-- Bio -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Bio</h3>
                    <p class="text-gray-700 leading-relaxed">{{ profileUser.bio || 'No bio yet.' }}</p>
                </div>

                <!-- Personal & Academic -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <User class="w-5 h-5 text-blue-600" />
                        Personal & Academic
                    </h3>
                    <div class="space-y-4">
                        <div v-if="profileUser.campus" class="flex items-center gap-3">
                            <MapPin class="w-5 h-5 text-gray-400" />
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Campus</p>
                                <p class="text-gray-900">{{ profileUser.campus }}</p>
                            </div>
                        </div>
                        <div v-if="profileUser.academic_program">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Academic Program</p>
                            <p class="text-gray-900">{{ profileUser.academic_program }}</p>
                        </div>
                        <div v-if="profileUser.year_level">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Year Level</p>
                            <p class="text-gray-900">{{ profileUser.year_level }}</p>
                        </div>
                        <div v-if="profileUser.date_of_birth" class="flex items-center gap-3">
                            <Calendar class="w-5 h-5 text-gray-400" />
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Date of Birth</p>
                                <p class="text-gray-600">{{ new Date(profileUser.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                            </div>
                        </div>
                        <p v-if="age || profileUser.gender" class="text-sm text-gray-500">
                            {{ age ? `${age} years old` : '' }}{{ age && profileUser.gender ? ' • ' : '' }}{{ profileUser.gender || '' }}
                        </p>
                    </div>
                </div>

                <!-- Tags -->
                <div class="bg-white rounded-3xl shadow-lg p-6 space-y-6">
                    <div v-if="courses.length > 0">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2"><Book class="w-4 h-4 text-blue-600" /> Courses</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(c, i) in courses" :key="i" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm">{{ c }}</span>
                        </div>
                    </div>
                    <div v-if="researchInterests.length > 0">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2"><Target class="w-4 h-4 text-cyan-600" /> Research Interests</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(r, i) in researchInterests" :key="i" class="px-3 py-1.5 bg-cyan-100 text-cyan-700 rounded-full text-sm">{{ r }}</span>
                        </div>
                    </div>
                    <div v-if="extracurricularActivities.length > 0">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2"><Trophy class="w-4 h-4 text-purple-600" /> Extracurricular</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(e, i) in extracurricularActivities" :key="i" class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm">{{ e }}</span>
                        </div>
                    </div>
                    <div v-if="interests.length > 0">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2"><Heart class="w-4 h-4 text-pink-600" /> Hobbies & Interests</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(h, i) in interests" :key="i" class="px-3 py-1.5 bg-pink-100 text-pink-700 rounded-full text-sm">{{ h }}</span>
                        </div>
                    </div>
                    <div v-if="academicGoals.length > 0">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2"><GraduationCap class="w-4 h-4 text-green-600" /> Academic Goals</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(g, i) in academicGoals" :key="i" class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-sm">{{ g }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab content: Posts -->
            <div v-show="activeTab === 'posts'" class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <p class="text-gray-500 mb-4">{{ profileUser.display_name }}'s posts appear on the home feed.</p>
                <button
                    @click="navigateToHome"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-full font-semibold text-sm"
                >
                    View feed
                </button>
            </div>

            <!-- Tab content: Achievements -->
            <div v-show="activeTab === 'achievements'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                            <Heart class="w-8 h-8 text-blue-600" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Connector</span>
                        <span class="text-xs text-gray-500">Community badge</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-cyan-100 flex items-center justify-center mb-2">
                            <GraduationCap class="w-8 h-8 text-cyan-600" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Scholar</span>
                        <span class="text-xs text-gray-500">Profile complete</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                            <Trophy class="w-8 h-8 text-blue-600" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Active</span>
                        <span class="text-xs text-gray-500">Engagement</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-2 opacity-60">
                            <Target class="w-8 h-8 text-gray-400" />
                        </div>
                        <span class="text-sm font-semibold text-gray-500">More</span>
                        <span class="text-xs text-gray-400">Coming soon</span>
                    </div>
                </div>
            </div>
        </div>

        <BottomNav />
    </div>
</template>
