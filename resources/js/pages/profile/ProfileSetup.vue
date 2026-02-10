<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import TagsInput from '@/components/ui/tags-input/TagsInput.vue';
import {
    ChevronLeft,
    ChevronRight,
    User,
    GraduationCap,
    Heart,
    Camera,
    Sparkles,
    CheckCircle,
    SlidersHorizontal,
    Users,
    MapPin,
    CalendarDays,
} from 'lucide-vue-next';

const props = defineProps<{
    user?: {
        name?: string;
        email?: string;
    };
}>();

const currentStep = ref(1);
const totalSteps = 9;

// Profile picture preview
const profilePreview = ref<string | null>(null);

// Form data with array support for tags
const form = useForm({
    display_name: '',
    fullname: props.user?.name || '',
    campus: '',
    academic_program: '',
    year_level: '',
    profile_picture: null as File | null,
    courses: [] as string[],
    research_interests: [] as string[],
    extracurricular_activities: [] as string[],
    academic_goals: [] as string[],
    bio: '',
    date_of_birth: '',
    gender: '',
    interests: [] as string[],
    relationship_status: '',
    looking_for: '',
    preferred_gender: '',
    preferred_age_min: null as number | null,
    preferred_age_max: null as number | null,
    preferred_campuses: [] as string[],
    ideal_match_qualities: [] as string[],
    preferred_courses: [] as string[],
});

const campusList = [
    'Tandag',
    'Bislig',
    'Tagbina',
    'Lianga',
    'Cagwait',
    'San Miguel',
    'Marihatag Offsite',
    'Cantilan',
];

const yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Graduate'];

const genderOptions = ['Male', 'Female', 'Lesbian', 'Gay'];

const relationshipStatusOptions = ['Single', 'In a Relationship', "It's Complicated"];

const lookingForOptions = ['Friendship', 'Relationship', 'Casual Date'];

/** Card options for Looking For (single-select). */
const lookingForCards = [
    { value: 'Friendship', label: 'Friendship', description: 'Connect with new friends and expand your circle.', icon: Users },
    { value: 'Relationship', label: 'Relationship', description: 'Find a meaningful, long-term connection.', icon: Heart },
    { value: 'Casual Date', label: 'Casual Date', description: 'Keep it light—coffee, hangouts, and good vibes.', icon: CalendarDays },
];

/** Gender(s) to see in Discover – "No preference" means no filter. Card options (single-select). */
const preferredGenderOptions = [
    { value: '', label: 'No preference', description: 'Show me everyone in Discover.', icon: Users },
    { value: 'Male', label: 'Male', description: 'Only show male users in Discover.', icon: User },
    { value: 'Female', label: 'Female', description: 'Only show female users in Discover.', icon: User },
    { value: 'Lesbian', label: 'Lesbian', description: 'Only show lesbian users in Discover.', icon: User },
    { value: 'Gay', label: 'Gay', description: 'Only show gay users in Discover.', icon: User },
];

/** Campus options for Preferred Campuses (multi-select) with short descriptions. */
const campusCards = [
    { value: 'Tandag', description: 'NEMSU Tandag Campus' },
    { value: 'Bislig', description: 'NEMSU Bislig Campus' },
    { value: 'Tagbina', description: 'NEMSU Tagbina Campus' },
    { value: 'Lianga', description: 'NEMSU Lianga Campus' },
    { value: 'Cagwait', description: 'NEMSU Cagwait Campus' },
    { value: 'San Miguel', description: 'NEMSU San Miguel Campus' },
    { value: 'Marihatag Offsite', description: 'NEMSU Marihatag Offsite' },
    { value: 'Cantilan', description: 'NEMSU Cantilan Campus' },
];

// Academic program autocomplete
const programSuggestions = ref<string[]>([]);
const showProgramSuggestions = ref(false);

const fetchProgramSuggestions = async (query: string) => {
    if (query.length < 2) {
        programSuggestions.value = [];
        return;
    }

    try {
        const response = await fetch(`/api/autocomplete/academic-programs?q=${encodeURIComponent(query)}`);
        programSuggestions.value = await response.json();
        showProgramSuggestions.value = programSuggestions.value.length > 0;
    } catch (error) {
        console.error('Failed to fetch program suggestions:', error);
    }
};

watch(() => form.academic_program, (newValue) => {
    if (newValue) {
        fetchProgramSuggestions(newValue);
    } else {
        showProgramSuggestions.value = false;
    }
});

const selectProgram = (program: string) => {
    form.academic_program = program;
    showProgramSuggestions.value = false;
};

function togglePreferredCampus(campus: string) {
    const idx = form.preferred_campuses.indexOf(campus);
    if (idx === -1) {
        form.preferred_campuses = [...form.preferred_campuses, campus];
    } else {
        form.preferred_campuses = form.preferred_campuses.filter((c) => c !== campus);
    }
}

const progressPercentage = computed(() => {
    return (currentStep.value / totalSteps) * 100;
});

const stepComplete = computed(() => {
    if (currentStep.value === 1) {
        return !!(form.display_name && form.fullname && form.date_of_birth && form.gender);
    } else if (currentStep.value === 2) {
        return !!(form.campus && form.academic_program && form.year_level);
    } else if (currentStep.value === 3) {
        return true; // Optional fields
    } else if (currentStep.value === 4) {
        return !!(form.bio && form.bio.length >= 10);
    } else if (currentStep.value === 5) {
        return !!form.relationship_status;
    } else if (currentStep.value === 6) {
        return !!form.looking_for;
    } else if (currentStep.value === 7 || currentStep.value === 8) {
        return true; // Preferred gender and campuses are optional
    } else if (currentStep.value === 9) {
        return true; // Age and ideal match optional
    }
    return false;
});

const nextStep = () => {
    if (currentStep.value < totalSteps && stepComplete.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const fileInput = ref<HTMLInputElement | null>(null);

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.profile_picture = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            profilePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const submitProfile = () => {
    form.transform((data) => ({
        ...data,
        preferred_age_min: data.preferred_age_min != null && Number(data.preferred_age_min) >= 18 ? Number(data.preferred_age_min) : null,
        preferred_age_max: data.preferred_age_max != null && Number(data.preferred_age_max) >= 18 ? Number(data.preferred_age_max) : null,
    })).post('/profile/setup', {
        onSuccess: () => {
            console.log('Profile saved successfully!');
            router.visit('/browse');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
        onFinish: () => {
            console.log('Form submission finished');
        },
    });
};

// Format date to MM/DD/YYYY for display
const formatDateForDisplay = (date: string) => {
    if (!date) return '';
    const d = new Date(date);
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const year = d.getFullYear();
    return `${month}/${day}/${year}`;
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-cyan-50 to-sky-100 flex items-center justify-center p-4 relative overflow-hidden">
        <Head title="Complete Your Profile - NEMSU Match" />

        <!-- Animated Hearts Background -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="heart heart-1"></div>
            <div class="heart heart-2"></div>
            <div class="heart heart-3"></div>
            <div class="heart heart-4"></div>
            <div class="heart heart-5"></div>
            <div class="heart heart-6"></div>
            <div class="heart heart-7"></div>
            <div class="heart heart-8"></div>
        </div>

        <div class="w-full max-w-3xl relative z-10">
            <!-- Top Decoration -->
            <div class="text-center mb-6 animate-fade-down">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-lg mb-4">
                    <Sparkles class="w-5 h-5 text-blue-600 animate-pulse" />
                    <span class="text-sm font-semibold text-gray-700">Complete Your Journey</span>
                    <Sparkles class="w-5 h-5 text-cyan-500 animate-pulse" />
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    Create Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">NEMSU Match</span> Profile
                </h1>
                <p class="text-sm text-gray-600">Let's get to know you better!</p>
            </div>

            <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-6 md:p-10 border border-white/50">
                <!-- Error Display -->
                <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-shake">
                    <p class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</p>
                    <ul class="text-sm text-red-600 space-y-1">
                        <li v-for="(error, field) in form.errors" :key="field">• {{ error }}</li>
                    </ul>
                </div>

                <div class="flex flex-col space-y-6">
            <!-- Progress Bar -->
            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs text-gray-600 mb-2">
                    <span class="font-medium">Step {{ currentStep }} of {{ totalSteps }}</span>
                    <div class="flex gap-1.5">
                        <div v-for="step in totalSteps" :key="step" class="flex items-center gap-1">
                            <div
                                class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="step <= currentStep ? 'bg-gradient-to-r from-blue-600 to-cyan-500 scale-110' : 'bg-gray-300'"
                            ></div>
                        </div>
                    </div>
                    <span class="font-semibold text-blue-600">{{ Math.round(progressPercentage) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden shadow-inner">
                    <div
                        class="bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-600 h-full rounded-full transition-all duration-500 ease-out animate-gradient"
                        :style="{ width: progressPercentage + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Step 1: Basic Information -->
            <div v-show="currentStep === 1" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <User class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Basic Information
                    </h2>
                    <p class="text-sm text-gray-600">
                        Let's start with the essentials
                    </p>
                </div>

                <div class="space-y-5">
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="display_name" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Display Name *
                        </label>
                        <Input
                            id="display_name"
                            v-model="form.display_name"
                            placeholder="How should we call you?"
                            class="rounded-xl border-2 focus:border-blue-500 transition-all h-12"
                            :class="{ 'border-red-500': form.errors.display_name }"
                        />
                        <p v-if="form.errors.display_name" class="text-xs text-red-600 flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-red-600"></span>{{ form.errors.display_name }}
                        </p>
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="fullname" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Full Name *
                        </label>
                        <Input
                            id="fullname"
                            v-model="form.fullname"
                            placeholder="Your complete name"
                            class="rounded-xl border-2 focus:border-blue-500 transition-all h-12"
                        />
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="date_of_birth" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Date of Birth * <span class="text-xs text-gray-500 font-normal">(MM/DD/YYYY)</span>
                        </label>
                        <Input
                            id="date_of_birth"
                            v-model="form.date_of_birth"
                            type="date"
                            class="rounded-xl border-2 focus:border-blue-500 transition-all h-12"
                        />
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="gender" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Gender *
                        </label>
                        <select
                            id="gender"
                            v-model="form.gender"
                            class="flex h-12 w-full rounded-xl border-2 border-input bg-white px-4 py-2 text-base shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option value="">Select your gender</option>
                            <option
                                v-for="gender in genderOptions"
                                :key="gender"
                                :value="gender"
                            >
                                {{ gender }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 2: Academic Information -->
            <div v-show="currentStep === 2" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <GraduationCap class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Academic Details
                    </h2>
                    <p class="text-sm text-gray-600">
                        Tell us about your academic journey
                    </p>
                </div>

                <div class="space-y-5">
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="campus" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Campus *
                        </label>
                        <select
                            id="campus"
                            v-model="form.campus"
                            class="flex h-12 w-full rounded-xl border-2 border-input bg-white px-4 py-2 text-base shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option value="">Select your campus</option>
                            <option
                                v-for="campus in campusList"
                                :key="campus"
                                :value="campus"
                            >
                                {{ campus }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01] relative">
                        <label for="academic_program" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Academic Program * <span class="text-xs text-gray-500 font-normal">(Start typing for suggestions)</span>
                        </label>
                        <Input
                            id="academic_program"
                            v-model="form.academic_program"
                            placeholder="e.g., BS Computer Science"
                            class="rounded-xl border-2 focus:border-blue-500 transition-all h-12"
                            @focus="form.academic_program && fetchProgramSuggestions(form.academic_program)"
                            @blur="() => setTimeout(() => showProgramSuggestions = false, 200)"
                        />

                        <!-- Program Autocomplete -->
                        <div
                            v-if="showProgramSuggestions && programSuggestions.length > 0"
                            class="absolute z-50 w-full mt-2 bg-white border-2 border-blue-200 rounded-xl shadow-xl max-h-60 overflow-y-auto animate-fade-in"
                        >
                            <button
                                v-for="(program, index) in programSuggestions"
                                :key="index"
                                type="button"
                                @click="selectProgram(program)"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-blue-50 transition-colors first:rounded-t-xl last:rounded-b-xl flex items-center gap-3 group"
                            >
                                <GraduationCap class="w-4 h-4 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                                <span class="font-medium">{{ program }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="year_level" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Year Level *
                        </label>
                        <select
                            id="year_level"
                            v-model="form.year_level"
                            class="flex h-12 w-full rounded-xl border-2 border-input bg-white px-4 py-2 text-base shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option value="">Select year level</option>
                            <option
                                v-for="year in yearLevels"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Favorite Courses <span class="text-xs text-gray-500 font-normal">(Press Enter or comma to add)</span>
                        </label>
                        <TagsInput
                            v-model="form.courses"
                            placeholder="e.g., Data Structures, Web Development..."
                            autocomplete-url="/api/autocomplete/courses"
                            :max-tags="10"
                        />
                    </div>
                </div>
            </div>

            <!-- Step 3: Interests & Activities -->
            <div v-show="currentStep === 3" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <Heart class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Your Interests
                    </h2>
                    <p class="text-sm text-gray-600">
                        What makes you unique? Share your passions!
                    </p>
                </div>

                <div class="space-y-5">
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Research Interests
                        </label>
                        <TagsInput
                            v-model="form.research_interests"
                            placeholder="e.g., Machine Learning, Climate Change..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="research"
                            :max-tags="10"
                        />
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Extracurricular Activities
                        </label>
                        <TagsInput
                            v-model="form.extracurricular_activities"
                            placeholder="e.g., Student Council, Basketball, Debate..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="extracurricular"
                            :max-tags="10"
                        />
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Hobbies & Interests
                        </label>
                        <TagsInput
                            v-model="form.interests"
                            placeholder="e.g., Reading, Gaming, Travel, Music..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="hobby"
                            :max-tags="15"
                        />
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Academic Goals
                        </label>
                        <TagsInput
                            v-model="form.academic_goals"
                            placeholder="e.g., Graduate with honors, Publish research..."
                            autocomplete-url="/api/autocomplete/interests"
                            category="academic_goal"
                            :max-tags="8"
                        />
                    </div>
                </div>
            </div>

            <!-- Step 4: Profile Photo & Bio -->
            <div v-show="currentStep === 4" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <Camera class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Complete Your Profile
                    </h2>
                    <p class="text-sm text-gray-600">
                        Add a photo and tell us about yourself
                    </p>
                </div>

                <div class="space-y-5">
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Profile Picture
                        </label>

                        <!-- Circular Preview or Upload Area -->
                        <div class="flex flex-col items-center gap-4">
                            <div
                                v-if="profilePreview"
                                class="relative group animate-scale-in"
                            >
                                <div class="w-40 h-40 rounded-full overflow-hidden border-4 border-gradient shadow-xl ring-4 ring-blue-100">
                                    <img :src="profilePreview" alt="Profile Preview" class="w-full h-full object-cover" />
                                </div>
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="absolute bottom-0 right-0 p-3 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-110 text-white"
                                >
                                    <Camera class="w-5 h-5" />
                                </button>
                            </div>

                            <div
                                v-else
                                class="w-full border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-blue-400 hover:bg-blue-50/50 transition-all cursor-pointer group"
                                @click="triggerFileInput"
                            >
                                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <Camera class="w-10 h-10 text-blue-600" />
                                </div>
                                <p class="text-sm font-medium text-gray-700 mb-1">
                                    Click to upload profile picture
                                </p>
                                <p class="text-xs text-gray-500">
                                    JPG, PNG or GIF (Max 5MB)
                                </p>
                            </div>

                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileUpload"
                            />
                        </div>
                    </div>

                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label for="bio" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            Bio * <span class="text-xs text-gray-500 font-normal">(minimum 10 characters)</span>
                        </label>
                        <textarea
                            id="bio"
                            v-model="form.bio"
                            placeholder="Write a short bio about yourself... Tell others what makes you special!"
                            class="flex min-h-[140px] w-full rounded-xl border-2 border-input bg-white px-4 py-3 text-base shadow-sm placeholder:text-muted-foreground focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-all resize-none"
                            :class="{ 'border-red-500': form.errors.bio }"
                            maxlength="500"
                        ></textarea>
                        <div class="flex justify-between items-center">
                            <p v-if="form.errors.bio" class="text-xs text-red-600 flex items-center gap-1">
                                <span class="w-1 h-1 rounded-full bg-red-600"></span>{{ form.errors.bio }}
                            </p>
                            <p class="text-xs ml-auto"
                                :class="form.bio.length >= 10 ? 'text-green-600 font-semibold' : 'text-gray-500'"
                            >
                                {{ form.bio.length }}/500
                                <CheckCircle v-if="form.bio.length >= 10" class="w-3 h-3 inline ml-1" />
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Relationship Status only -->
            <div v-show="currentStep === 5" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <SlidersHorizontal class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Relationship Status
                    </h2>
                    <p class="text-sm text-gray-600">
                        What's your current relationship status?
                    </p>
                </div>
                <div class="space-y-2">
                    <label for="relationship_status" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        Select your status *
                    </label>
                    <select
                        id="relationship_status"
                        v-model="form.relationship_status"
                        class="flex h-12 w-full rounded-xl border-2 border-input bg-white px-4 py-2 text-base shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        :class="{ 'border-red-500': form.errors.relationship_status }"
                    >
                        <option value="">Select your status</option>
                        <option
                            v-for="opt in relationshipStatusOptions"
                            :key="opt"
                            :value="opt"
                        >
                            {{ opt }}
                        </option>
                    </select>
                    <p v-if="form.errors.relationship_status" class="text-xs text-red-600">{{ form.errors.relationship_status }}</p>
                </div>
            </div>

            <!-- Step 6: Looking For only (single-select cards) -->
            <div v-show="currentStep === 6" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <Heart class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        What are you looking for?
                    </h2>
                    <p class="text-sm text-gray-600">
                        Choose one—we'll use this to find better matches
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button
                        v-for="opt in lookingForCards"
                        :key="opt.value"
                        type="button"
                        @click="form.looking_for = opt.value"
                        class="relative flex flex-col items-start p-4 rounded-xl border-2 text-left bg-white transition-all hover:bg-blue-50/50 cursor-pointer"
                        :class="form.looking_for === opt.value ? 'border-blue-500 ring-2 ring-blue-200 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <component
                            :is="opt.icon"
                            class="w-5 h-5 mb-2 text-gray-600"
                            :class="form.looking_for === opt.value ? 'text-blue-600' : ''"
                        />
                        <span class="font-semibold text-gray-900">{{ opt.label }}</span>
                        <span class="text-xs text-gray-500 mt-0.5">{{ opt.description }}</span>
                        <CheckCircle
                            v-if="form.looking_for === opt.value"
                            class="absolute top-3 right-3 w-5 h-5 text-blue-600"
                        />
                    </button>
                </div>
                <p v-if="form.errors.looking_for" class="text-xs text-red-600">{{ form.errors.looking_for }}</p>
            </div>

            <!-- Step 7: Interested in (gender) only (single-select cards) -->
            <div v-show="currentStep === 7" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <Users class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Interested in (gender)
                    </h2>
                    <p class="text-sm text-gray-600">
                        Who do you want to see in Discover? Optional—"No preference" shows everyone
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button
                        v-for="opt in preferredGenderOptions"
                        :key="opt.value || 'any'"
                        type="button"
                        @click="form.preferred_gender = opt.value"
                        class="relative flex flex-col items-start p-4 rounded-xl border-2 text-left bg-white transition-all hover:bg-blue-50/50 cursor-pointer"
                        :class="form.preferred_gender === opt.value ? 'border-blue-500 ring-2 ring-blue-200 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <component
                            :is="opt.icon"
                            class="w-5 h-5 mb-2 text-gray-600"
                            :class="form.preferred_gender === opt.value ? 'text-blue-600' : ''"
                        />
                        <span class="font-semibold text-gray-900">{{ opt.label }}</span>
                        <span class="text-xs text-gray-500 mt-0.5">{{ opt.description }}</span>
                        <CheckCircle
                            v-if="form.preferred_gender === opt.value"
                            class="absolute top-3 right-3 w-5 h-5 text-blue-600"
                        />
                    </button>
                </div>
                <p v-if="form.errors.preferred_gender" class="text-xs text-red-600">{{ form.errors.preferred_gender }}</p>
            </div>

            <!-- Step 8: Preferred Location (Campuses) only (multi-select cards) -->
            <div v-show="currentStep === 8" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <MapPin class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Preferred Location (Campuses)
                    </h2>
                    <p class="text-sm text-gray-600">
                        Select one or more campuses—optional. We'll prioritize matches from these locations
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button
                        v-for="campus in campusCards"
                        :key="campus.value"
                        type="button"
                        @click="togglePreferredCampus(campus.value)"
                        class="relative flex flex-col items-start p-4 rounded-xl border-2 text-left bg-white transition-all hover:bg-blue-50/50 cursor-pointer"
                        :class="form.preferred_campuses.includes(campus.value) ? 'border-blue-500 ring-2 ring-blue-200 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <MapPin
                            class="w-5 h-5 mb-2 text-gray-600"
                            :class="form.preferred_campuses.includes(campus.value) ? 'text-blue-600' : ''"
                        />
                        <span class="font-semibold text-gray-900">{{ campus.value }}</span>
                        <span class="text-xs text-gray-500 mt-0.5">{{ campus.description }}</span>
                        <CheckCircle
                            v-if="form.preferred_campuses.includes(campus.value)"
                            class="absolute top-3 right-3 w-5 h-5 text-blue-600"
                        />
                    </button>
                </div>
            </div>

            <!-- Step 9: Age Range + Ideal Match (optional) -->
            <div v-show="currentStep === 9" class="space-y-6 animate-slide-in">
                <div class="text-center space-y-3">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg animate-bounce-slow"
                    >
                        <Sparkles class="w-10 h-10 text-blue-600" />
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                        Almost there!
                    </h2>
                    <p class="text-sm text-gray-600">
                        Optional: preferred age, courses, and qualities you look for in a match
                    </p>
                </div>
                <div class="space-y-5">
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Preferred Courses (in a match) <span class="text-xs text-gray-500 font-normal">(optional)</span>
                        </label>
                        <TagsInput
                            v-model="form.preferred_courses"
                            placeholder="e.g. BS Civil Engineering, BS Computer Science (press Enter or comma to add)"
                            autocomplete-url="/api/autocomplete/courses"
                            :max-tags="10"
                        />
                    </div>
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Preferred Age Range <span class="text-xs text-gray-500 font-normal">(optional)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <Input
                                v-model.number="form.preferred_age_min"
                                type="number"
                                min="18"
                                max="100"
                                placeholder="Min"
                                class="rounded-xl border-2 focus:border-blue-500 h-12 flex-1"
                            />
                            <span class="text-gray-500">to</span>
                            <Input
                                v-model.number="form.preferred_age_max"
                                type="number"
                                min="18"
                                max="100"
                                placeholder="Max"
                                class="rounded-xl border-2 focus:border-blue-500 h-12 flex-1"
                            />
                        </div>
                    </div>
                    <div class="space-y-2 transform transition-all hover:scale-[1.01]">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Ideal Match <span class="text-xs text-gray-500 font-normal">(e.g. funny, ambitious, adventurous)</span>
                        </label>
                        <TagsInput
                            v-model="form.ideal_match_qualities"
                            placeholder="e.g. funny, ambitious, adventurous (press Enter or comma to add)"
                            :max-tags="12"
                        />
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex gap-3 pt-6">
                <Button
                    v-if="currentStep > 1"
                    @click="prevStep"
                    variant="outline"
                    class="flex-1 rounded-full py-6 text-base font-semibold border-2 hover:bg-gray-50 hover:scale-105 transition-all"
                >
                    <ChevronLeft class="w-5 h-5 mr-1" />
                    Back
                </Button>

                <Button
                    v-if="currentStep < totalSteps"
                    @click="nextStep"
                    :disabled="!stepComplete"
                    class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white rounded-full py-6 text-base font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                >
                    Next Step
                    <ChevronRight class="w-5 h-5 ml-1" />
                </Button>

                <Button
                    v-if="currentStep === totalSteps"
                    @click="submitProfile"
                    :disabled="form.processing || !stepComplete"
                    class="flex-1 bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-600 hover:from-blue-700 hover:via-cyan-600 hover:to-blue-700 text-white rounded-full py-6 text-base font-semibold shadow-xl hover:shadow-2xl hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 animate-gradient"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    <Sparkles v-else class="w-5 h-5 mr-2" />
                    Complete Profile
                </Button>
            </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animated Hearts Background */
.heart {
    position: absolute;
    width: 30px;
    height: 30px;
    opacity: 0.15;
    animation: float-heart 15s infinite ease-in-out;
}

.heart::before,
.heart::after {
    content: "";
    position: absolute;
    width: 30px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6, #06b6d4);
    border-radius: 50px 50px 0 0;
}

.heart::before {
    left: 15px;
    transform: rotate(-45deg);
    transform-origin: 0 100%;
}

.heart::after {
    left: 0;
    transform: rotate(45deg);
    transform-origin: 100% 100%;
}

@keyframes float-heart {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.15;
    }
    90% {
        opacity: 0.15;
    }
    100% {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
    }
}

.heart-1 {
    left: 10%;
    animation-delay: 0s;
    animation-duration: 18s;
}

.heart-2 {
    left: 25%;
    animation-delay: 3s;
    animation-duration: 20s;
    width: 25px;
    height: 25px;
}

.heart-2::before,
.heart-2::after {
    width: 25px;
    height: 40px;
}

.heart-3 {
    left: 45%;
    animation-delay: 6s;
    animation-duration: 16s;
}

.heart-4 {
    left: 60%;
    animation-delay: 2s;
    animation-duration: 22s;
    width: 35px;
    height: 35px;
}

.heart-4::before,
.heart-4::after {
    width: 35px;
    height: 56px;
}

.heart-5 {
    left: 75%;
    animation-delay: 8s;
    animation-duration: 19s;
}

.heart-6 {
    left: 90%;
    animation-delay: 4s;
    animation-duration: 17s;
    width: 28px;
    height: 28px;
}

.heart-6::before,
.heart-6::after {
    width: 28px;
    height: 45px;
}

.heart-7 {
    left: 35%;
    animation-delay: 10s;
    animation-duration: 21s;
}

.heart-8 {
    left: 55%;
    animation-delay: 12s;
    animation-duration: 18s;
    width: 32px;
    height: 32px;
}

.heart-8::before,
.heart-8::after {
    width: 32px;
    height: 51px;
}

/* Animations */
@keyframes fade-down {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-down {
    animation: fade-down 0.6s ease-out;
}

@keyframes slide-in {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-slide-in {
    animation: slide-in 0.4s ease-out;
}

@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 2s infinite ease-in-out;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-10px);
    }
    75% {
        transform: translateX(10px);
    }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

@keyframes gradient {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

.border-gradient {
    border-image: linear-gradient(135deg, #3b82f6, #06b6d4) 1;
}

/* Smooth transitions */
select,
textarea,
input {
    transition: all 0.2s ease;
}

select:focus,
textarea:focus,
input:focus {
    transform: translateY(-1px);
}
</style>
