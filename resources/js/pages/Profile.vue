<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { User, MapPin, GraduationCap, Calendar, Heart, Book, Target, Trophy, ChevronLeft, MessageCircle, Users, Star, X } from 'lucide-vue-next';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';
import { FullscreenImageViewer } from '@/components/feed';

const props = defineProps<{
    profileUser: {
        id: number;
        display_name: string;
        fullname: string;
        campus: string | null;
        academic_program: string | null;
        year_level: string | null;
        profile_picture: string | null;
        bio: string;
        date_of_birth: string | null;
        gender: string;
        relationship_status?: string | null;
        looking_for?: string | null;
        preferred_gender?: string | null;
        preferred_age_min?: number | null;
        preferred_age_max?: number | null;
        courses?: string[] | unknown;
        research_interests?: string[] | unknown;
        extracurricular_activities?: string[] | unknown;
        academic_goals?: string[] | unknown;
        interests?: string[] | unknown;
        preferred_campuses?: string[] | unknown;
        ideal_match_qualities?: string[] | unknown;
        preferred_courses?: string[] | unknown;
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

const interests = computed(() => ensureStringArray(props.profileUser.interests));
const preferredCampuses = computed(() => ensureStringArray(props.profileUser.preferred_campuses));
const idealMatchQualities = computed(() => ensureStringArray(props.profileUser.ideal_match_qualities));
const preferredCourses = computed(() => ensureStringArray(props.profileUser.preferred_courses));

const isFollowed = ref(props.is_followed_by_user);
const followLoading = ref(false);
const displayInterests = computed(() => interests.value.slice(0, 8));

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

const page = usePage();
const fromChat = computed(() => {
    const url = String(page.url || '');
    const queryIndex = url.indexOf('?');
    if (queryIndex === -1) return false;
    const search = url.slice(queryIndex);
    const params = new URLSearchParams(search);
    return params.get('from_chat') === '1';
});

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

const goBack = () => router.visit('/browse');
const navigateToHome = () => router.visit('/home');
const navigateToDiscover = () => router.visit('/dashboard');
const navigateToAccount = () => router.visit('/account');
const openChat = () => router.visit(`/chat?user=${props.profileUser.id}`);

const showFullBio = ref(false);
const bioText = computed(() => {
    const bio = props.profileUser.bio || '';
    if (showFullBio.value || bio.length <= 140) {
        return bio;
    }
    return bio.slice(0, 140) + '…';
});
const canToggleBio = computed(() => (props.profileUser.bio || '').length > 140);
const toggleBio = () => {
    if (canToggleBio.value) {
        showFullBio.value = !showFullBio.value;
    }
};

type GalleryPhoto = { id: number; path: string; url: string; created_at: string };
const galleryPhotos = ref<GalleryPhoto[]>([]);
const galleryLoading = ref(false);

// Fullscreen gallery viewing
const showFullscreenImage = ref(false);
const fullscreenIndex = ref(0);
const fullscreenImages = computed(() => galleryPhotos.value.map((p) => p.path));

function openFullscreenGallery(index: number) {
    fullscreenIndex.value = Math.max(0, Math.min(index, fullscreenImages.value.length - 1));
    showFullscreenImage.value = true;
}

const fetchGallery = async () => {
    galleryLoading.value = true;
    try {
        const res = await fetch(`/api/gallery?user_id=${encodeURIComponent(String(props.profileUser.id))}`, {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            galleryPhotos.value = data.data ?? [];
        }
    } catch (e) {
        console.error('Failed to load gallery', e);
    } finally {
        galleryLoading.value = false;
    }
};

// Preload gallery on mount so images are available when user scrolls
fetchGallery();

const showActionsMenu = ref(false);
const showReportModal = ref(false);
const reportingUser = ref(false);
const reportReason = ref<'spam' | 'harassment' | 'inappropriate' | 'misleading' | 'other'>('spam');
const reportDescription = ref('');

function openReportUser() {
    showActionsMenu.value = false;
    reportReason.value = 'spam';
    reportDescription.value = '';
    showReportModal.value = true;
}

async function submitUserReport() {
    if (reportingUser.value) return;
    reportingUser.value = true;
    try {
        const res = await fetch(`/api/users/${props.profileUser.id}/report`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                reason: reportReason.value,
                description: reportDescription.value?.trim() ? reportDescription.value.trim() : null,
            }),
        });

        const data = await res.json().catch(() => ({}));
        if (res.ok) {
            showReportModal.value = false;
            alert(data.message ?? 'User reported successfully.');
        } else {
            alert(data.message ?? 'Failed to submit report.');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to submit report.');
    } finally {
        reportingUser.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-white via-blue-50/30 to-white pb-36 relative overflow-hidden flex flex-col">
        <Head :title="`${profileUser.display_name} - NEMSU Match`" />

        <!-- Top controls (teleported so fixed positioning is truly viewport-based) -->
        <Teleport to="body">
            <button
                type="button"
                @click="goBack"
                class="fixed top-[calc(env(safe-area-inset-top)+16px)] left-4 z-[90] w-9 h-9 rounded-full bg-white/95 flex items-center justify-center shadow-md hover:bg-white transition-colors"
                aria-label="Back"
            >
                <ChevronLeft class="w-5 h-5 text-gray-800" />
            </button>

            <div class="fixed top-[calc(env(safe-area-inset-top)+16px)] right-4 z-[90]">
                <div class="relative">
                    <button
                        type="button"
                        @click.stop="showActionsMenu = !showActionsMenu"
                        class="w-9 h-9 rounded-full bg-white/95 flex items-center justify-center shadow-md hover:bg-white transition-colors"
                        aria-label="More options"
                    >
                        <span class="flex items-center gap-0.5">
                            <span class="block w-1 h-1 rounded-full bg-gray-700"></span>
                            <span class="block w-1 h-1 rounded-full bg-gray-700"></span>
                            <span class="block w-1 h-1 rounded-full bg-gray-700"></span>
                        </span>
                    </button>

                    <div v-if="showActionsMenu" class="fixed inset-0 z-[85]" @click="showActionsMenu = false"></div>
                    <div
                        v-if="showActionsMenu"
                        class="absolute right-0 mt-2 w-44 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden z-[90]"
                    >
                        <button
                            type="button"
                            @click="openReportUser"
                            class="w-full px-4 py-3 text-left text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors"
                        >
                            Report user
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Hero image (background layer) -->
        <div class="absolute inset-x-0 top-0 z-0 bg-black">
            <div class="h-[72vh] min-h-[420px] max-h-[660px] w-full bg-gray-900">
                <img
                    v-if="galleryPhotos.length ? galleryPhotos[0].url : profileUser.profile_picture"
                    :src="galleryPhotos.length ? galleryPhotos[0].url : profilePictureSrc(profileUser.profile_picture as string)"
                    :alt="profileUser.display_name"
                    class="w-full h-full object-cover object-center"
                    @click="galleryPhotos.length ? openFullscreenGallery(0) : undefined"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-blue-600 to-cyan-500" />
            </div>
            <!-- Fade into white content below -->
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-64 bg-gradient-to-t from-white via-white/70 to-transparent" />
            <!-- Subtle vignette for focus -->
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/10 via-transparent to-black/15" />
        </div>

        <!-- Content (foreground layer) -->
        <div class="relative z-10 flex-1 px-4 pt-[52vh] min-h-[560px]">
            <div class="w-full max-w-md mx-auto">
                <!-- Main profile card -->
                <div class="profile-sheet bg-white/95 backdrop-blur rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden pb-5 border border-white/60">
                    <!-- Name / status -->
                    <div class="px-6 pt-5 pb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-gray-900 truncate">
                                {{ profileUser.fullname || profileUser.display_name || 'NEMSU Student' }}
                                <span v-if="age" class="font-medium text-gray-700">, {{ age }}</span>
                            </h2>
                            <p class="text-sm text-gray-500 truncate">
                                <span v-if="profileUser.display_name">@{{ profileUser.display_name }}</span>
                                <span v-else>—</span>
                            </p>
                            <p v-if="profileUser.campus" class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                <MapPin class="w-3 h-3" />
                                <span class="truncate">{{ profileUser.campus }}</span>
                            </p>

                            <!-- Quick facts -->
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-if="profileUser.academic_program"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100"
                                >
                                    <GraduationCap class="w-3 h-3 mr-1" />
                                    {{ profileUser.academic_program }}
                                </span>
                                <span
                                    v-if="profileUser.year_level"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-100"
                                >
                                    {{ profileUser.year_level }}
                                </span>
                                <span
                                    v-if="profileUser.gender"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-50 text-gray-700 border border-gray-100"
                                >
                                    {{ profileUser.gender }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span
                                v-if="profileUser.relationship_status"
                                class="inline-flex items-center shrink-0 whitespace-nowrap px-3 py-1 rounded-full text-[11px] leading-none font-semibold bg-pink-50 text-pink-700 border border-pink-200 shadow-sm"
                            >
                                {{ profileUser.relationship_status }}
                            </span>
                        </div>
                    </div>

                    <!-- About me -->
                    <div class="px-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">About me</h3>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            {{ bioText || 'No bio yet.' }}
                            <button
                                v-if="canToggleBio"
                                type="button"
                                @click.stop="toggleBio"
                                class="ml-1 text-xs font-semibold text-blue-600 hover:text-blue-700"
                            >
                                {{ showFullBio ? 'Show less' : 'See more' }}
                            </button>
                        </p>
                    </div>

                    <!-- Interests -->
                    <div class="px-6 mt-3">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Interests</h3>
                        <div v-if="displayInterests.length" class="flex flex-wrap gap-2">
                            <span
                                v-for="(interest, i) in displayInterests"
                                :key="i"
                                class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                            >
                                {{ interest }}
                            </span>
                        </div>
                        <p v-else class="text-xs text-gray-500">No interests listed yet.</p>
                    </div>
                </div>

                <!-- Preferences card -->
                <div class="mt-4 bg-white/95 backdrop-blur rounded-3xl shadow-lg p-5 space-y-3 border border-white/60">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                            <Heart class="w-4 h-4 text-blue-600" />
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900">Preferences</h3>
                    </div>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold flex items-center gap-1">
                                <Users class="w-3 h-3 text-blue-500" />
                                Looking for
                            </span>
                            <span class="text-gray-800 font-medium">
                                {{ profileUser.looking_for || 'Not set' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold flex items-center gap-1">
                                <Heart class="w-3 h-3 text-pink-500" />
                                Interested in
                            </span>
                            <span class="text-gray-800 font-medium">
                                {{ profileUser.preferred_gender || 'No preference' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold flex items-center gap-1">
                                <Calendar class="w-3 h-3 text-cyan-500" />
                                Preferred age
                            </span>
                            <span class="text-gray-800 font-medium">
                                <template v-if="profileUser.preferred_age_min != null || profileUser.preferred_age_max != null">
                                    {{ profileUser.preferred_age_min ?? '—' }} – {{ profileUser.preferred_age_max ?? '—' }} years
                                </template>
                                <template v-else>
                                    No preference
                                </template>
                            </span>
                        </div>
                    </div>

                    <div v-if="preferredCampuses.length" class="text-xs text-gray-600 pt-1">
                        <p class="font-semibold mb-1 flex items-center gap-1">
                            <MapPin class="w-3 h-3 text-emerald-500" />
                            Preferred campuses
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="(c, i) in preferredCampuses"
                                :key="i"
                                class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium"
                            >
                                {{ c }}
                            </span>
                        </div>
                    </div>

                    <div v-if="idealMatchQualities.length" class="text-xs text-gray-600 pt-1">
                        <p class="font-semibold mb-1 flex items-center gap-1">
                            <Star class="w-3 h-3 text-amber-500" />
                            Ideal match
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="(q, i) in idealMatchQualities"
                                :key="i"
                                class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-medium"
                            >
                                {{ q }}
                            </span>
                        </div>
                    </div>

                    <div v-if="preferredCourses.length" class="text-xs text-gray-600 pt-1">
                        <p class="font-semibold mb-1 flex items-center gap-1">
                            <GraduationCap class="w-3 h-3 text-indigo-500" />
                            Preferred courses
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="(course, i) in preferredCourses"
                                :key="i"
                                class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium"
                            >
                                {{ course }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Gallery -->
                <div class="mt-4 bg-white/95 backdrop-blur rounded-3xl shadow-lg p-4 border border-white/60">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Gallery</h3>
                        <span v-if="galleryPhotos.length" class="text-[11px] text-gray-500">
                            {{ galleryPhotos.length }} photo{{ galleryPhotos.length === 1 ? '' : 's' }}
                        </span>
                    </div>
                    <div v-if="galleryLoading" class="flex justify-center py-6">
                        <div class="w-7 h-7 border-3 border-blue-600 border-t-transparent rounded-full animate-spin" />
                    </div>
                    <div v-else-if="galleryPhotos.length" class="grid grid-cols-3 gap-1">
                        <div
                            v-for="photo in galleryPhotos"
                            :key="photo.id"
                            class="aspect-square rounded-lg overflow-hidden bg-gray-200"
                        >
                            <button
                                type="button"
                                class="w-full h-full"
                                @click="openFullscreenGallery(galleryPhotos.findIndex((p) => p.id === photo.id))"
                                aria-label="View photo"
                            >
                                <img :src="photo.url" alt="Gallery photo" class="w-full h-full object-cover" />
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-500 text-center py-4">No photos in gallery yet.</p>
                </div>
            </div>
        </div>

        <!-- Fullscreen viewer for gallery -->
        <FullscreenImageViewer
            v-model="showFullscreenImage"
            v-model:index="fullscreenIndex"
            :images="fullscreenImages"
        />

        <!-- Floating Chat Now (hidden when coming from chat conversation) -->
        <div v-if="!fromChat" class="fixed left-0 right-0 z-50 bottom-[calc(env(safe-area-inset-bottom)+104px)]">
            <div class="max-w-md mx-auto px-4">
                <button
                    type="button"
                    @click="openChat"
                    class="profile-chat-cta w-full py-3.5 rounded-full text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-500 shadow-xl hover:shadow-2xl hover:from-blue-700 hover:to-cyan-600 transition-all"
                >
                    Chat now
                </button>
            </div>
        </div>

        <!-- Report User Modal -->
        <div
            v-if="showReportModal"
            class="fixed inset-0 bg-black/50 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4"
            @click.self="showReportModal = false"
        >
            <div class="bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl animate-slide-up flex flex-col mb-20 sm:mb-0">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Report User</h3>
                    <button type="button" @click="showReportModal = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <X class="w-5 h-5 text-gray-500" />
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Help us keep NEMSU Match safe. Your report is confidential.
                    </p>

                    <div class="space-y-3 mb-4">
                        <label
                            v-for="opt in [
                                { value: 'spam', label: 'Spam', sub: 'Fake, repetitive, or promotional activity' },
                                { value: 'harassment', label: 'Harassment', sub: 'Bullying, threats, or hate speech' },
                                { value: 'inappropriate', label: 'Inappropriate', sub: 'Explicit or offensive behavior' },
                                { value: 'misleading', label: 'Misleading', sub: 'Impersonation or false info' },
                                { value: 'other', label: 'Other', sub: 'Something else' },
                            ]"
                            :key="opt.value"
                            class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                            :class="reportReason === opt.value ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input type="radio" v-model="reportReason" :value="opt.value" class="w-4 h-4 text-blue-600" />
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">{{ opt.label }}</div>
                                <div class="text-xs text-gray-500">{{ opt.sub }}</div>
                            </div>
                        </label>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Additional Details (Optional)</label>
                        <textarea
                            v-model="reportDescription"
                            placeholder="Provide more context..."
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors resize-none"
                            rows="3"
                            maxlength="500"
                        />
                        <div class="text-right text-xs text-gray-500 mt-1">{{ reportDescription.length }}/500</div>
                    </div>

                    <button
                        type="button"
                        @click="submitUserReport"
                        :disabled="reportingUser"
                        class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-3 rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span v-if="reportingUser">Submitting...</span>
                        <span v-else>Submit Report</span>
                    </button>
                    <p class="text-xs text-center text-gray-500 mt-3">Reports are reviewed by our team.</p>
                </div>
            </div>
        </div>

        <BottomNav />
    </div>
</template>

<style scoped>
@keyframes profile-sheet-in {
    from {
        opacity: 0;
        transform: translateY(14px);
        filter: blur(2px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }
}

.profile-sheet {
    animation: profile-sheet-in 520ms cubic-bezier(.2,.8,.2,1) both;
    will-change: transform, opacity;
}

@keyframes chat-cta-breathe {
    0%,
    100% {
        transform: translateY(0) scale(1);
        filter: saturate(1);
    }
    50% {
        transform: translateY(-2px) scale(1.01);
        filter: saturate(1.05);
    }
}

.profile-chat-cta {
    animation: chat-cta-breathe 2.6s ease-in-out infinite;
    will-change: transform;
}

@keyframes slide-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

@media (prefers-reduced-motion: reduce) {
    .profile-sheet {
        animation: none;
    }
    .profile-chat-cta {
        animation: none;
    }
}
</style>
