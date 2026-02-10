<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Settings as SettingsIcon, Save, Power, UserCheck, MessageSquare, Video, Heart, Zap } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';
import { useCsrfToken } from '@/composables/useCsrfToken';

interface Setting {
    id: number;
    key: string;
    value: any;
    raw_value: string;
    type: string;
    description: string;
}

interface GroupedSettings {
    [group: string]: Setting[];
}

const props = defineProps<{
    settings: GroupedSettings;
}>();

const getCsrfToken = useCsrfToken();
const localSettings = ref<GroupedSettings>(JSON.parse(JSON.stringify(props.settings)));
const saving = ref(false);
const successMessage = ref('');

function getIcon(key: string) {
    switch (key) {
        case 'maintenance_mode': return Power;
        case 'pre_registration_mode': return UserCheck;
        case 'allow_registration': return UserCheck;
        case 'enable_chat': return MessageSquare;
        case 'enable_video_call': return Video;
        case 'max_daily_swipes': return Heart;
        case 'max_daily_matches': return Zap;
        default: return SettingsIcon;
    }
}

function getBadgeColor(group: string): string {
    switch (group) {
        case 'general': return 'bg-purple-100 text-purple-700';
        case 'users': return 'bg-blue-100 text-blue-700';
        case 'features': return 'bg-green-100 text-green-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}

async function saveSetting(setting: Setting) {
    saving.value = true;
    try {
        const res = await fetch(`/superadmin/settings/${setting.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                value: setting.value,
            }),
        });

        if (res.ok) {
            successMessage.value = 'Setting saved successfully';
            setTimeout(() => {
                successMessage.value = '';
            }, 3000);
        } else {
            const data = await res.json();
            alert(data.message || 'Failed to save setting');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to save setting');
    } finally {
        saving.value = false;
    }
}

async function saveAllSettings() {
    saving.value = true;
    try {
        const allSettings: any[] = [];
        
        Object.values(localSettings.value).forEach(group => {
            group.forEach(setting => {
                allSettings.push({
                    id: setting.id,
                    value: setting.value,
                });
            });
        });

        const res = await fetch('/superadmin/settings/bulk-update', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                settings: allSettings,
            }),
        });

        if (res.ok) {
            successMessage.value = 'All settings saved successfully';
            setTimeout(() => {
                successMessage.value = '';
                router.reload({ only: ['settings'] });
            }, 2000);
        } else {
            const data = await res.json();
            alert(data.message || 'Failed to save settings');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to save settings');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head title="App Settings - Superadmin" />
    
    <SuperadminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">App Settings</h1>
                    <p class="text-gray-600 mt-1">Configure application settings and features</p>
                </div>
                <button
                    type="button"
                    @click="saveAllSettings"
                    :disabled="saving"
                    class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
                >
                    <Save class="w-5 h-5" />
                    Save All Changes
                </button>
            </div>

            <!-- Success Message -->
            <Transition name="fade">
                <div
                    v-if="successMessage"
                    class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3"
                >
                    <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="font-semibold">{{ successMessage }}</p>
                </div>
            </Transition>

            <!-- Settings Groups -->
            <div class="space-y-6">
                <div
                    v-for="(groupSettings, groupName) in localSettings"
                    :key="groupName"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
                >
                    <!-- Group Header -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                :class="getBadgeColor(groupName)"
                            >
                                {{ groupName }}
                            </span>
                            <h2 class="text-lg font-bold text-gray-900 capitalize">{{ groupName }} Settings</h2>
                        </div>
                    </div>

                    <!-- Settings List -->
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="setting in groupSettings"
                            :key="setting.id"
                            class="p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center justify-between gap-6">
                                <div class="flex items-start gap-4 flex-1">
                                    <!-- Icon -->
                                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0 mt-1">
                                        <component :is="getIcon(setting.key)" class="w-5 h-5 text-blue-600" />
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-gray-900 mb-1">
                                            {{ setting.key.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') }}
                                        </h3>
                                        <p class="text-sm text-gray-600">{{ setting.description }}</p>
                                    </div>
                                </div>

                                <!-- Control -->
                                <div class="flex items-center gap-3">
                                    <!-- Boolean Toggle -->
                                    <div v-if="setting.type === 'boolean'" class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click="setting.value = !setting.value; saveSetting(setting)"
                                            :class="[
                                                'relative w-14 h-7 rounded-full transition-colors duration-200 ease-in-out',
                                                setting.value ? 'bg-gradient-to-r from-blue-600 to-cyan-500' : 'bg-gray-300'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200 ease-in-out',
                                                    setting.value ? 'translate-x-7' : 'translate-x-0'
                                                ]"
                                            />
                                        </button>
                                        <span
                                            class="text-sm font-semibold min-w-[4rem]"
                                            :class="setting.value ? 'text-green-600' : 'text-gray-400'"
                                        >
                                            {{ setting.value ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>

                                    <!-- Integer Input -->
                                    <div v-else-if="setting.type === 'integer'" class="flex items-center gap-3">
                                        <input
                                            v-model.number="setting.value"
                                            @blur="saveSetting(setting)"
                                            type="number"
                                            min="0"
                                            class="w-24 px-4 py-2 border-2 border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-blue-500 transition-colors"
                                        />
                                    </div>

                                    <!-- String Input -->
                                    <div v-else class="flex items-center gap-3">
                                        <input
                                            v-model="setting.value"
                                            @blur="saveSetting(setting)"
                                            type="text"
                                            class="w-64 px-4 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-2xl">
                <div class="flex gap-3">
                    <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-900 mb-1">Settings Information</h4>
                        <p class="text-sm text-blue-700">
                            Boolean settings are saved automatically when toggled. 
                            For numeric and text settings, changes are saved when you click outside the input field or click "Save All Changes".
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
