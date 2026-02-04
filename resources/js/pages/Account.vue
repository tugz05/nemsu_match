<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { User, Mail, MapPin, GraduationCap, Calendar, Heart, Book, Target, Trophy, Camera, Edit2, LogOut, ChevronRight, ChevronLeft, Settings, Plus } from 'lucide-vue-next';
import TagsInput from '@/components/ui/tags-input/TagsInput.vue';
import { profilePictureSrc } from '@/composables/useProfilePictureSrc';
import { BottomNav } from '@/components/feed';

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
        following_count?: number;
        followers_count?: number;
        posts_count?: number;
        member_since?: string | null;
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

const isEditing = ref(false);
const profilePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const activeTab = ref<'about' | 'posts' | 'achievements'>('about');
const showSettingsMenu = ref(false);

// Form for editing (include array fields for interests, courses, etc.)
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
    }
    isEditing.value = !isEditing.value;
};

// Save changes
const saveChanges = () => {
    form.post('/api/account/update', {
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

const navigateToHome = () => router.visit('/home');
const navigateToDiscover = () => router.visit('/dashboard');
const goBack = () => router.visit('/home');

function onDocumentClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('.settings-menu-container')) showSettingsMenu.value = false;
}
onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
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
                    <h2 class="text-xl font-bold text-gray-900">{{ user.fullname }}</h2>
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

            <!-- Stats row: Following | Followers | Posts -->
            <div class="grid grid-cols-3 gap-4 py-4 border-y border-gray-200 mb-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ user.following_count ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Following</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ user.followers_count ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Followers</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900">{{ user.posts_count ?? 0 }}</p>
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
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Non-binary">Non-binary</option>
                            <option value="Prefer not to say">Prefer not to say</option>
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
            </div>

            <!-- Tab content: Posts -->
            <div v-show="activeTab === 'posts'" class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <p class="text-gray-500 mb-4">Your posts appear on the home feed.</p>
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
                        <span class="text-xs text-gray-500">Make your first connection</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-cyan-100 flex items-center justify-center mb-2">
                            <GraduationCap class="w-8 h-8 text-cyan-600" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Scholar</span>
                        <span class="text-xs text-gray-500">Complete your profile</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                            <Trophy class="w-8 h-8 text-blue-600" />
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Active</span>
                        <span class="text-xs text-gray-500">Post 5 times</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center justify-center aspect-square">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-2 opacity-60">
                            <Target class="w-8 h-8 text-gray-400" />
                        </div>
                        <span class="text-sm font-semibold text-gray-500">Coming soon</span>
                        <span class="text-xs text-gray-400">More badges</span>
                    </div>
                </div>
            </div>
        </div>

        <BottomNav active-tab="account" />
    </div>
</template>
