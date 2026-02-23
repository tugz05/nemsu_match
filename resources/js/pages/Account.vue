<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { User, Mail, MapPin, GraduationCap, Calendar, Heart, Book, Target, Trophy, Camera, Edit2, LogOut, ChevronRight, ChevronLeft, Settings, Plus, Trash2, MapPinned, Bell } from 'lucide-vue-next';
import TagsInput from '@/components/ui/tags-input/TagsInput.vue';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { useBrowserNotifications } from '@/composables/useBrowserNotifications';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { BottomNav, FullscreenImageViewer } from '@/components/feed';

type GalleryPhoto = { id: number; path: string; url: string; created_at: string };

const props = defineProps<{
    user: {
        id: number;
        display_name: string;
        fullname: string;
        email: string;
        campus: string;
        academic_program: string;
        year_level: string;
        profile_picture: string | null;
        bio: string;
        date_of_birth: string;
        gender: string;
        courses?: string[] | unknown;
        research_interests?: string[] | unknown;
        extracurricular_activities?: string[] | unknown;
        academic_goals?: string[] | unknown;
        interests?: string[] | unknown;
        relationship_status?: string | null;
        looking_for?: string | null;
        preferred_gender?: string | null;
        preferred_age_min?: number | null;
        preferred_age_max?: number | null;
        preferred_campuses?: string[] | unknown;
        ideal_match_qualities?: string[] | unknown;
        preferred_courses?: string[] | unknown;
        following_count?: number;
        followers_count?: number;
        posts_count?: number;
        member_since?: string | null;
        nearby_match_enabled?: boolean;
        nearby_match_radius_m?: number;
    };
}>();

// Normalize array fields so they always display (handle string/array/undefined from backend)
function ensureStringArray(value: unknown): string[] {
    if (value == null) return [];
    if (Array.isArray(value)) {
        return value.filter((item): item is string => typeof item === 'string');
    }
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

const courses = computed(() => ensureStringArray(props.user.courses));
const researchInterests = computed(() => ensureStringArray(props.user.research_interests));
const extracurricularActivities = computed(() => ensureStringArray(props.user.extracurricular_activities));
const academicGoals = computed(() => ensureStringArray(props.user.academic_goals));
const interests = computed(() => ensureStringArray(props.user.interests));
const preferredCampuses = computed(() => ensureStringArray(props.user.preferred_campuses));
const idealMatchQualities = computed(() => ensureStringArray(props.user.ideal_match_qualities));
const preferredCourses = computed(() => ensureStringArray(props.user.preferred_courses));

const campusList = ['Tandag', 'Bislig', 'Tagbina', 'Lianga', 'Cagwait', 'San Miguel', 'Marihatag Offsite', 'Cantilan'];
const relationshipStatusOptions = ['Single', 'In a Relationship', "It's Complicated"];
const lookingForOptions = ['Friendship', 'Relationship', 'Casual Date'];
const genderOptions = ['Male', 'Female', 'Lesbian', 'Gay'];
const preferredGenderOptions = [
    { value: '', label: 'No preference' },
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
    { value: 'Lesbian', label: 'Lesbian' },
    { value: 'Gay', label: 'Gay' },
];

const isEditing = ref(false);
const profilePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const activeTab = ref<'gallery' | 'about'>('about');
const showSettingsMenu = ref(false);
const showRequirePictureDialog = ref(false);

// Browser notifications (native Notification API)
const browserNotif = useBrowserNotifications();

async function toggleBrowserNotifications() {
    if (browserNotif.isEnabled) {
        browserNotif.setEnabled(false);
        return;
    }
    const result = await browserNotif.requestPermission();
    if (result !== 'granted') {
        browserNotif.refreshPermission();
    }
}

// Nearby match: preferences (synced from props, updated via API)
const nearbyMatchEnabled = ref(!!props.user.nearby_match_enabled);
const nearbyMatchRadiusM = ref(props.user.nearby_match_radius_m ?? 1000);
const nearbyMatchSaving = ref(false);
const locationError = ref<string | null>(null);
const locationUpdating = ref(false);

const radiusOptions = [
    { value: 500, label: '500 m' },
    { value: 1000, label: '1 km' },
    { value: 1500, label: '1.5 km' },
    { value: 2000, label: '2 km' },
];

async function saveNearbyMatchSettings() {
    nearbyMatchSaving.value = true;
    locationError.value = null;
    try {
        const res = await fetch('/api/account/nearby-match-settings', {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                nearby_match_enabled: nearbyMatchEnabled.value,
                nearby_match_radius_m: nearbyMatchRadiusM.value,
            }),
        });
        if (res.ok && nearbyMatchEnabled.value) {
            await reportLocation();
        }
    } catch (e) {
        console.error(e);
    } finally {
        nearbyMatchSaving.value = false;
    }
}

function onNearbyMatchToggle() {
    nearbyMatchEnabled.value = !nearbyMatchEnabled.value;
    saveNearbyMatchSettings();
}

async function reportLocation() {
    if (!navigator.geolocation) {
        locationError.value = 'Location is not supported by your device.';
        return;
    }
    locationUpdating.value = true;
    locationError.value = null;
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            try {
                const res = await fetch('/api/account/location', {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                    }),
                });
                if (!res.ok) locationError.value = 'Could not update location.';
            } catch (e) {
                locationError.value = 'Could not update location.';
            } finally {
                locationUpdating.value = false;
            }
        },
        () => {
            locationError.value = 'Location permission denied or unavailable.';
            locationUpdating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
    );
}

// Gallery (Create / Delete, Instagram-style grid)
const getCsrfToken = useCsrfToken();
const galleryPhotos = ref<GalleryPhoto[]>([]);
const galleryLoading = ref(false);
const galleryUploading = ref(false);
const galleryDeletingId = ref<number | null>(null);
const galleryInputRef = ref<HTMLInputElement | null>(null);

// Fullscreen gallery viewing
const showFullscreenImage = ref(false);
const fullscreenIndex = ref(0);
const fullscreenImages = computed(() => galleryPhotos.value.map((p) => p.path));

function openFullscreenGallery(index: number) {
    fullscreenIndex.value = Math.max(0, Math.min(index, fullscreenImages.value.length - 1));
    showFullscreenImage.value = true;
}

async function fetchGallery() {
    galleryLoading.value = true;
    try {
        const res = await fetch('/api/gallery', {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            galleryPhotos.value = data.data ?? [];
        }
    } finally {
        galleryLoading.value = false;
    }
}

function openGalleryUpload() {
    galleryInputRef.value?.click();
}

async function onGalleryFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files).filter((f) => f.type.startsWith('image/')) : [];
    if (files.length === 0) return;
    galleryUploading.value = true;
    try {
        const formData = new FormData();
        files.forEach((file) => formData.append('photos[]', file));
        const res = await fetch('/api/gallery', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
            body: formData,
        });
        if (res.ok) {
            const data = await res.json();
            const created = Array.isArray(data.data) ? data.data : (data.data ? [data.data] : []);
            if (created.length) galleryPhotos.value = [...created, ...galleryPhotos.value];
        }
    } finally {
        galleryUploading.value = false;
        input.value = '';
    }
}

async function deleteGalleryPhoto(id: number) {
    galleryDeletingId.value = id;
    try {
        const res = await fetch(`/api/gallery/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
        });
        if (res.ok) {
            galleryPhotos.value = galleryPhotos.value.filter((p) => p.id !== id);
        }
    } finally {
        galleryDeletingId.value = null;
    }
}

watch(activeTab, (tab) => {
    if (tab === 'gallery') fetchGallery();
});

// Form for editing (include array fields for interests, courses, preferences, etc.)
const form = useForm({
    display_name: props.user.display_name,
    fullname: props.user.fullname,
    campus: props.user.campus,
    academic_program: props.user.academic_program,
    year_level: props.user.year_level,
    bio: props.user.bio,
    date_of_birth: props.user.date_of_birth,
    gender: props.user.gender,
    profile_picture: null as File | null,
    courses: ensureStringArray(props.user.courses),
    research_interests: ensureStringArray(props.user.research_interests),
    extracurricular_activities: ensureStringArray(props.user.extracurricular_activities),
    academic_goals: ensureStringArray(props.user.academic_goals),
    interests: ensureStringArray(props.user.interests),
    relationship_status: props.user.relationship_status ?? '',
    looking_for: props.user.looking_for ?? '',
    preferred_gender: props.user.preferred_gender ?? '',
    preferred_age_min: props.user.preferred_age_min ?? null as number | null,
    preferred_age_max: props.user.preferred_age_max ?? null as number | null,
    preferred_campuses: ensureStringArray(props.user.preferred_campuses),
    ideal_match_qualities: ensureStringArray(props.user.ideal_match_qualities),
    preferred_courses: ensureStringArray(props.user.preferred_courses),
});

// Calculate age
const age = computed(() => {
    if (!props.user.date_of_birth) return null;
    const birthDate = new Date(props.user.date_of_birth);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
});

// Handle profile picture upload
const handleProfilePicture = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.profile_picture = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            profilePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

function startRequiredPictureUpload() {
    showRequirePictureDialog.value = false;
    if (!isEditing.value) {
        toggleEdit();
    }
    fileInput.value?.click();
}

// Toggle edit mode - when entering edit, sync form arrays from current props
const toggleEdit = () => {
    if (isEditing.value) {
        form.reset();
        profilePreview.value = null;
    } else {
        // Sync array fields from current display data when entering edit mode
        form.courses = [...courses.value];
        form.research_interests = [...researchInterests.value];
        form.extracurricular_activities = [...extracurricularActivities.value];
        form.academic_goals = [...academicGoals.value];
        form.interests = [...interests.value];
        form.relationship_status = props.user.relationship_status ?? '';
        form.looking_for = props.user.looking_for ?? '';
        form.preferred_gender = props.user.preferred_gender ?? '';
        form.preferred_age_min = props.user.preferred_age_min ?? null;
        form.preferred_age_max = props.user.preferred_age_max ?? null;
        form.preferred_campuses = [...preferredCampuses.value];
        form.ideal_match_qualities = [...idealMatchQualities.value];
        form.preferred_courses = [...preferredCourses.value];
    }
    isEditing.value = !isEditing.value;
};

function togglePreferredCampus(campus: string) {
    const idx = form.preferred_campuses.indexOf(campus);
    if (idx === -1) {
        form.preferred_campuses = [...form.preferred_campuses, campus];
    } else {
        form.preferred_campuses = form.preferred_campuses.filter((c) => c !== campus);
    }
}

// Save changes (normalize optional numbers for backend)
const saveChanges = () => {
    form.transform((data) => ({
        ...data,
        preferred_age_min: data.preferred_age_min != null && data.preferred_age_min !== '' ? Number(data.preferred_age_min) : null,
        preferred_age_max: data.preferred_age_max != null && data.preferred_age_max !== '' ? Number(data.preferred_age_max) : null,
    })).post('/api/account/update', {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            profilePreview.value = null;
        },
    });
};

// Logout
const logout = () => {
    router.post('/nemsu/logout');
};

const navigateToHome = () => router.visit('/browse');
const navigateToDiscover = () => router.visit('/like-you');
const goBack = () => router.visit('/browse');

function onDocumentClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('.settings-menu-container')) showSettingsMenu.value = false;
}
onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));

watch(
    () => [props.user.nearby_match_enabled, props.user.nearby_match_radius_m],
    ([enabled, radius]) => {
        nearbyMatchEnabled.value = !!enabled;
        nearbyMatchRadiusM.value = (radius as number) ?? 1000;
    }
);

onMounted(() => {
    browserNotif.refreshPermission();
    const params = new URLSearchParams(window.location.search);
    const requiresPicture = params.get('require_profile_picture') === '1';
    if (requiresPicture && !props.user.profile_picture) {
        showRequirePictureDialog.value = true;
    }
    if (props.user.nearby_match_enabled) {
        reportLocation();
    }
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-50 pb-20">
        <Head title="NEMSU Match - Account" />

        <!-- Top bar: back | username | settings -->
        <div class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
                <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Back">
                    <ChevronLeft class="w-6 h-6 text-gray-700" />
                </button>
                <h1 class="text-lg font-bold text-gray-900 truncate flex-1 text-center mx-2">{{ user.display_name || 'Account' }}</h1>
                <div class="relative settings-menu-container">
                    <button
                        @click.stop="showSettingsMenu = !showSettingsMenu"
                        class="p-2 rounded-full hover:bg-gray-100 transition-colors"
                        aria-label="Settings"
                    >
                        <Settings class="w-6 h-6 text-gray-700" />
                    </button>
                    <div
                        v-if="showSettingsMenu"
                        class="absolute right-0 top-full mt-1 py-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200 z-50"
                    >
                        <button @click="toggleEdit(); showSettingsMenu = false" class="w-full px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <Edit2 class="w-4 h-4" />
                            Edit profile
                        </button>
                        <button @click="logout(); showSettingsMenu = false" class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                            <LogOut class="w-4 h-4" />
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-4">
            <!-- Profile header: avatar (left) + name & actions (right) -->
            <div class="flex gap-4 items-start mb-4">
                <div class="relative flex-shrink-0">
                    <div class="w-24 h-24 rounded-full border-4 border-white shadow-lg bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                        <img
                            v-if="profilePreview || user.profile_picture"
                            :src="profilePreview || profilePictureSrc(user.profile_picture)"
                            :alt="user.display_name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 text-3xl font-bold">
                            {{ user.display_name?.charAt(0).toUpperCase() }}
                        </div>
                    </div>
                    <button
                        v-if="isEditing"
                        @click="fileInput?.click()"
                        type="button"
                        class="absolute bottom-0 right-0 w-8 h-8 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow transition-colors"
                    >
                        <Camera class="w-4 h-4" />
                    </button>
                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleProfilePicture" />
                </div>
                <div class="flex-1 min-w-0 pt-1">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-1.5">
                        <span>{{ user.fullname }}</span>
                        <span
                            v-if="user.is_workspace_verified"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-[11px] font-semibold px-2.5 py-0.5 shadow-sm shrink-0"
                        >
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2a1 1 0 0 1 .6.2l6 4.5a1 1 0 0 1 .4.8v6a5.5 5.5 0 0 1-5.3 5.5H10.3A5.5 5.5 0 0 1 5 13.5v-6a1 1 0 0 1 .4-.8l6-4.5A1 1 0 0 1 12 2zm-1.1 11.9 3.6-3.6a.75.75 0 1 0-1.06-1.06L11 11.78l-1.44-1.44a.75.75 0 1 0-1.06 1.06l1.97 1.97a.75.75 0 0 0 1.03.03z"
                                />
                            </svg>
                            Verified
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500">{{ user.display_name }}</p>
                    <p v-if="user.member_since" class="text-xs text-gray-500 mt-0.5">Member for {{ user.member_since }}</p>
                    <button
                        type="button"
                        @click="toggleEdit"
                        class="mt-3 flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700"
                    >
                        <Plus class="w-4 h-4" />
                        Edit profile
                    </button>
                </div>
            </div>

            <!-- Tabs: About | Gallery -->
            <div class="flex border-b border-gray-200 mb-4">
                <button
                    @click="activeTab = 'about'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :class="activeTab === 'about' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    About
                </button>
                <button
                    @click="activeTab = 'gallery'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :class="activeTab === 'gallery' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Gallery
                </button>
            </div>

            <!-- Tab content: About -->
            <div v-show="activeTab === 'about'" class="space-y-6">
                <!-- Nearby match notifications -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <MapPinned class="w-5 h-5 text-blue-600" />
                        Match nearby
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Get notified when a mutual match is within your chosen distance—great for meeting up or studying together on campus.
                    </p>
                    <div class="flex items-center justify-between gap-4 mb-3">
                        <span class="text-sm font-medium text-gray-700">Notify me when a match is nearby</span>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="nearbyMatchEnabled"
                            @click="onNearbyMatchToggle"
                            :disabled="nearbyMatchSaving"
                            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                            :class="nearbyMatchEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                        >
                            <span
                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition"
                                :class="nearbyMatchEnabled ? 'translate-x-5' : 'translate-x-1'"
                            />
                        </button>
                    </div>
                    <div v-if="nearbyMatchEnabled" class="mt-3 pt-3 border-t border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notification radius</label>
                        <select
                            v-model.number="nearbyMatchRadiusM"
                            @change="saveNearbyMatchSettings"
                            :disabled="nearbyMatchSaving"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm"
                        >
                            <option v-for="opt in radiusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <p v-if="locationError" class="mt-2 text-sm text-amber-600">{{ locationError }}</p>
                    <p v-if="locationUpdating" class="mt-2 text-sm text-gray-500">Updating location…</p>
                    <p class="mt-2 text-xs text-gray-500">You can turn this off anytime. Location is only used to detect when you and a match are within your chosen distance.</p>
                </div>

                <!-- Browser notifications -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <Bell class="w-5 h-5 text-blue-600" />
                        Browser notifications
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Get notified in your browser for new messages, matches, likes, and more—even when the tab is in the background.
                    </p>
                    <div v-if="!browserNotif.isSupported" class="text-sm text-amber-600">
                        Your browser doesn't support notifications.
                    </div>
                    <template v-else>
                        <div class="flex items-center justify-between gap-4 mb-3">
                            <span class="text-sm font-medium text-gray-700">Enable browser notifications</span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="browserNotif.isEnabled"
                                @click="toggleBrowserNotifications"
                                :disabled="browserNotif.permission === 'denied' && !browserNotif.isEnabled"
                                class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                :class="browserNotif.isEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition"
                                    :class="browserNotif.isEnabled ? 'translate-x-5' : 'translate-x-1'"
                                />
                            </button>
                        </div>
                        <p v-if="browserNotif.permission === 'denied'" class="text-sm text-amber-600">
                            Notifications were blocked. Enable them in your browser settings for this site, then turn this on again.
                        </p>
                        <p v-else-if="browserNotif.permission === 'default' && !browserNotif.isEnabled" class="text-sm text-gray-500">
                            Turn on to allow this site to show notifications (you'll be asked for permission).
                        </p>
                    </template>
                </div>

                <!-- Bio -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Bio</h3>
                    <div v-if="!isEditing">
                        <p class="text-gray-700 leading-relaxed">{{ user.bio || 'No bio yet.' }}</p>
                    </div>
                    <div v-else>
                        <textarea
                            v-model="form.bio"
                            placeholder="Tell us about yourself..."
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none resize-none"
                            rows="3"
                            maxlength="500"
                        />
                        <p class="text-xs text-right text-gray-500 mt-1">{{ form.bio?.length || 0 }}/500</p>
                    </div>
                </div>
                <div v-if="isEditing" class="flex gap-3 justify-center">
                    <button @click="toggleEdit" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full font-semibold text-sm">
                        Cancel
                    </button>
                    <button @click="saveChanges" :disabled="form.processing" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-full font-semibold text-sm disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                </div>

                <!-- Personal Information -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <User class="w-5 h-5 text-blue-600" />
                    Personal Information
                </h3>

                <div class="space-y-4">
                    <!-- Display Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Display Name</label>
                        <input
                            v-if="isEditing"
                            v-model="form.display_name"
                            type="text"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                        />
                        <p v-else class="text-gray-900">{{ user.display_name }}</p>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                        <input
                            v-if="isEditing"
                            v-model="form.fullname"
                            type="text"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                        />
                        <p v-else class="text-gray-900">{{ user.fullname }}</p>
                    </div>

                    <!-- Email -->
                    <div class="flex items-center gap-3">
                        <Mail class="w-5 h-5 text-gray-400" />
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <p class="text-gray-600">{{ user.email }}</p>
                        </div>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                        <p v-if="!isEditing" class="text-gray-900">{{ user.gender }}</p>
                        <select
                            v-else
                            v-model="form.gender"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                        >
                            <option v-for="g in genderOptions" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>

                    <!-- Date of Birth -->
                    <div class="flex items-center gap-3">
                        <Calendar class="w-5 h-5 text-gray-400" />
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date of Birth</label>
                            <p class="text-gray-600">{{ new Date(user.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Academic Information -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <GraduationCap class="w-5 h-5 text-blue-600" />
                    Academic Information
                </h3>

                <div class="space-y-4">
                    <!-- Campus -->
                    <div class="flex items-center gap-3">
                        <MapPin class="w-5 h-5 text-gray-400" />
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Campus</label>
                            <p v-if="!isEditing" class="text-gray-900">{{ user.campus }}</p>
                            <select
                                v-else
                                v-model="form.campus"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                            >
                                <option value="Tandag">Tandag</option>
                                <option value="Bislig">Bislig</option>
                                <option value="Tagbina">Tagbina</option>
                                <option value="Lianga">Lianga</option>
                                <option value="Cagwait">Cagwait</option>
                                <option value="San Miguel">San Miguel</option>
                                <option value="Marihatag Offsite">Marihatag Offsite</option>
                                <option value="Cantilan">Cantilan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Academic Program -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Academic Program</label>
                        <input
                            v-if="isEditing"
                            v-model="form.academic_program"
                            type="text"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                        />
                        <p v-else class="text-gray-900">{{ user.academic_program }}</p>
                    </div>

                    <!-- Year Level -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Year Level</label>
                        <input
                            v-if="isEditing"
                            v-model="form.year_level"
                            type="text"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none transition-colors"
                        />
                        <p v-else class="text-gray-900">{{ user.year_level }}</p>
                    </div>

                    <!-- Favorite Courses -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <Book class="w-4 h-4" />
                            Favorite Courses
                        </label>
                        <TagsInput
                            v-if="isEditing"
                            v-model="form.courses"
                            placeholder="Add a course..."
                            autocomplete-url="/api/autocomplete/courses"
                            :max-tags="10"
                            class="mt-1"
                        />
                        <template v-else>
                            <div v-if="courses.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="(course, index) in courses"
                                    :key="index"
                                    class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium"
                                >
                                    {{ course }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">No courses added yet</p>
                        </template>
                    </div>

                    <!-- Research Interests -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <Target class="w-4 h-4" />
                            Research Interests
                        </label>
                        <TagsInput
                            v-if="isEditing"
                            v-model="form.research_interests"
                            placeholder="Add research interest..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="research"
                            :max-tags="10"
                            class="mt-1"
                        />
                        <template v-else>
                            <div v-if="researchInterests.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="(interest, index) in researchInterests"
                                    :key="index"
                                    class="px-3 py-1.5 bg-cyan-100 text-cyan-700 rounded-full text-sm font-medium"
                                >
                                    {{ interest }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">No research interests added yet</p>
                        </template>
                    </div>

                    <!-- Extracurricular Activities -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <Trophy class="w-4 h-4" />
                            Extracurricular Activities
                        </label>
                        <TagsInput
                            v-if="isEditing"
                            v-model="form.extracurricular_activities"
                            placeholder="Add activity..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="extracurricular"
                            :max-tags="10"
                            class="mt-1"
                        />
                        <template v-else>
                            <div v-if="extracurricularActivities.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="(activity, index) in extracurricularActivities"
                                    :key="index"
                                    class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium"
                                >
                                    {{ activity }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">No extracurricular activities added yet</p>
                        </template>
                    </div>

                    <!-- Hobbies & Interests -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <Heart class="w-4 h-4" />
                            Hobbies & Interests
                        </label>
                        <TagsInput
                            v-if="isEditing"
                            v-model="form.interests"
                            placeholder="Add hobby or interest..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="hobby"
                            :max-tags="10"
                            class="mt-1"
                        />
                        <template v-else>
                            <div v-if="interests.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="(hobby, index) in interests"
                                    :key="index"
                                    class="px-3 py-1.5 bg-pink-100 text-pink-700 rounded-full text-sm font-medium"
                                >
                                    {{ hobby }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">No hobbies or interests added yet</p>
                        </template>
                    </div>

                    <!-- Academic Goals -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <GraduationCap class="w-4 h-4" />
                            Academic Goals
                        </label>
                        <TagsInput
                            v-if="isEditing"
                            v-model="form.academic_goals"
                            placeholder="Add academic goal..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="academic_goal"
                            :max-tags="10"
                            class="mt-1"
                        />
                        <template v-else>
                            <div v-if="academicGoals.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="(goal, index) in academicGoals"
                                    :key="index"
                                    class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-medium"
                                >
                                    {{ goal }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">No academic goals added yet</p>
                        </template>
                    </div>
                </div>
                </div>

                <!-- Match preferences -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <Heart class="w-5 h-5 text-blue-600" />
                        Match Preferences
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Relationship Status</label>
                            <p v-if="!isEditing" class="text-gray-900">{{ user.relationship_status || '—' }}</p>
                            <select
                                v-else
                                v-model="form.relationship_status"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none"
                            >
                                <option value="">Select</option>
                                <option v-for="opt in relationshipStatusOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Looking For</label>
                            <p v-if="!isEditing" class="text-gray-900">{{ user.looking_for || '—' }}</p>
                            <select
                                v-else
                                v-model="form.looking_for"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none"
                            >
                                <option value="">Select</option>
                                <option v-for="opt in lookingForOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Interested in (gender)</label>
                            <p v-if="!isEditing" class="text-gray-900">{{ user.preferred_gender ? preferredGenderOptions.find(o => o.value === user.preferred_gender)?.label : 'No preference' }}</p>
                            <select
                                v-else
                                v-model="form.preferred_gender"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none"
                            >
                                <option v-for="opt in preferredGenderOptions" :key="opt.value || 'any'" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Preferred Age Range</label>
                            <p v-if="!isEditing" class="text-gray-900">
                                {{ (user.preferred_age_min != null || user.preferred_age_max != null) ? `${user.preferred_age_min ?? '—'} to ${user.preferred_age_max ?? '—'}` : '—' }}
                            </p>
                            <div v-else class="flex items-center gap-2">
                                <input
                                    v-model.number="form.preferred_age_min"
                                    type="number"
                                    min="18"
                                    max="100"
                                    placeholder="Min"
                                    class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none"
                                />
                                <span class="text-gray-500">to</span>
                                <input
                                    v-model.number="form.preferred_age_max"
                                    type="number"
                                    min="18"
                                    max="100"
                                    placeholder="Max"
                                    class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Preferred Courses (in a match)</label>
                            <template v-if="!isEditing">
                                <div v-if="preferredCourses.length" class="flex flex-wrap gap-2">
                                    <span v-for="(course, i) in preferredCourses" :key="i" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm">{{ course }}</span>
                                </div>
                                <p v-else class="text-sm text-gray-500">None added</p>
                            </template>
                            <TagsInput
                                v-else
                                v-model="form.preferred_courses"
                                placeholder="e.g. Data Structures, Web Development..."
                                autocomplete-url="/api/autocomplete/courses"
                                :max-tags="10"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Preferred Campuses</label>
                            <template v-if="!isEditing">
                                <div v-if="preferredCampuses.length" class="flex flex-wrap gap-2">
                                    <span v-for="(c, i) in preferredCampuses" :key="i" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm">{{ c }}</span>
                                </div>
                                <p v-else class="text-sm text-gray-500">None selected</p>
                            </template>
                            <div v-else class="flex flex-wrap gap-2">
                                <label
                                    v-for="c in campusList"
                                    :key="c"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer text-sm"
                                    :class="form.preferred_campuses.includes(c) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                                >
                                    <input type="checkbox" :value="c" :checked="form.preferred_campuses.includes(c)" @change="togglePreferredCampus(c)" class="rounded" />
                                    {{ c }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ideal Match (qualities)</label>
                            <template v-if="!isEditing">
                                <div v-if="idealMatchQualities.length" class="flex flex-wrap gap-2">
                                    <span v-for="(q, i) in idealMatchQualities" :key="i" class="px-3 py-1.5 bg-cyan-100 text-cyan-700 rounded-full text-sm">{{ q }}</span>
                                </div>
                                <p v-else class="text-sm text-gray-500">None added</p>
                            </template>
                            <TagsInput
                                v-else
                                v-model="form.ideal_match_qualities"
                                placeholder="e.g. funny, ambitious..."
                                :max-tags="12"
                                class="mt-1"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab content: Gallery (Instagram-style grid, Create + Delete) -->
            <div v-show="activeTab === 'gallery'" class="bg-white rounded-2xl shadow-lg p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900">Gallery</h3>
                    <button
                        type="button"
                        :disabled="galleryUploading"
                        @click="openGalleryUpload"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-sm font-semibold hover:opacity-95 disabled:opacity-60 transition-opacity"
                    >
                        <Plus class="w-4 h-4" />
                        {{ galleryUploading ? 'Uploading…' : 'Add photos' }}
                    </button>
                </div>
                <input
                    ref="galleryInputRef"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="onGalleryFileChange"
                />

                <div v-if="galleryLoading" class="flex justify-center py-12">
                    <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
                </div>

                <div v-else class="grid grid-cols-3 gap-0.5 sm:gap-1">
                    <!-- Create cell: add photo -->
                    <button
                        type="button"
                        :disabled="galleryUploading"
                        @click="openGalleryUpload"
                        class="aspect-square bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 text-gray-500 transition-colors"
                    >
                        <Plus class="w-8 h-8" />
                    </button>

                    <!-- Photo cells -->
                    <div
                        v-for="(photo, idx) in galleryPhotos"
                        :key="photo.id"
                        class="relative group aspect-square rounded-lg overflow-hidden bg-gray-200"
                    >
                        <button
                            type="button"
                            class="w-full h-full"
                            @click="openFullscreenGallery(idx)"
                            aria-label="View photo"
                        >
                            <img
                                :src="photo.url"
                                :alt="'Gallery photo'"
                                class="w-full h-full object-cover"
                            />
                        </button>
                        <button
                            type="button"
                            :disabled="galleryDeletingId === photo.id"
                            @click.stop="deleteGalleryPhoto(photo.id)"
                            class="absolute top-1.5 right-1.5 w-8 h-8 rounded-full bg-black/50 hover:bg-red-500 text-white flex items-center justify-center transition-colors disabled:opacity-50"
                            aria-label="Delete photo"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <p v-if="!galleryLoading && galleryPhotos.length === 0" class="text-sm text-gray-500 mt-3">No photos yet. Tap Add photos to upload one or more.</p>

                <!-- Match back & Matches links -->
                <div class="mt-6 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="router.visit('/like-you?tab=match_back')"
                        class="px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-semibold hover:bg-blue-100"
                    >
                        Match back
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/like-you?tab=matches')"
                        class="px-4 py-2 rounded-xl bg-cyan-50 text-cyan-700 text-sm font-semibold hover:bg-cyan-100"
                    >
                        My matches
                    </button>
                </div>
            </div>
        </div>

        <FullscreenImageViewer
            v-model="showFullscreenImage"
            v-model:index="fullscreenIndex"
            :images="fullscreenImages"
        />

        <div
            v-if="showRequirePictureDialog"
            class="fixed inset-0 z-[80] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-200 p-5">
                <h3 class="text-lg font-bold text-gray-900">Upload profile picture required</h3>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    You need to upload a profile picture before you can access Browse and Discover.
                </p>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100"
                        @click="showRequirePictureDialog = false"
                    >
                        Later
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-95"
                        @click="startRequiredPictureUpload"
                    >
                        Upload now
                    </button>
                </div>
            </div>
        </div>

        <BottomNav active-tab="account" />
    </div>
</template>
