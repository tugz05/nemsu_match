<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Settings as SettingsIcon, Save, Power, UserCheck, MessageSquare, Video, Heart, Zap, Image, Bell } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';
import { useCsrfToken } from '@/composables/useCsrfToken';
import { useBrowserNotifications } from '@/composables/useBrowserNotifications';

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

interface Branding {
    app_logo_url: string | null;
    header_icon_url: string | null;
}

const props = defineProps<{
    settings: GroupedSettings;
    branding: Branding;
}>();

const getCsrfToken = useCsrfToken();
const localSettings = ref<GroupedSettings>(JSON.parse(JSON.stringify(props.settings)));
const brandingUrls = ref<Branding>({ ...props.branding });
const saving = ref(false);
const successMessage = ref('');
const uploadingLogo = ref(false);
const uploadingHeaderIcon = ref(false);

// Browser notification test (superadmin)
const browserNotif = useBrowserNotifications();
const testPusherSending = ref(false);
const testPusherMessage = ref('');

onMounted(() => {
    browserNotif.refreshPermission();
});

async function sendTestBrowserNotification() {
    if (!browserNotif.isSupported) {
        alert('Your browser does not support notifications.');
        return;
    }
    let perm = browserNotif.permission;
    if (perm !== 'granted') {
        perm = await browserNotif.requestPermission();
    }
    if (perm === 'granted') {
        browserNotif.setEnabled(true);
        const n = new Notification('NEMSU Match - Test', {
            body: 'This is a local test. Real notifications arrive via Pusher when the app is in the background.',
            tag: 'test-local',
        });
        n.onclick = () => n.close();
    } else {
        alert('Permission denied. Enable notifications in your browser settings for this site.');
    }
}

async function sendTestViaPusher() {
    testPusherMessage.value = '';
    testPusherSending.value = true;
    try {
        const res = await fetch('/superadmin/settings/test-browser-notification', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok) {
            testPusherMessage.value = data.message || 'Test sent. If browser notifications are enabled and allowed, you should see it (even on this tab for test type).';
        } else {
            testPusherMessage.value = data.message || 'Failed to send test.';
        }
    } catch (e) {
        testPusherMessage.value = 'Request failed.';
    } finally {
        testPusherSending.value = false;
    }
}

// Exclude branding and freemium from generic list (they have dedicated sections)
const settingsWithoutBranding = computed(() => {
    const out: GroupedSettings = {};
    for (const [group, settings] of Object.entries(localSettings.value)) {
        if (group === 'branding' || group === 'freemium') continue;
        out[group] = settings;
    }
    return out;
});

const freemiumSettings = computed(() => localSettings.value.freemium ?? []);
function getFreemiumSetting(key: string): Setting | undefined {
    return freemiumSettings.value.find((s: Setting) => s.key === key);
}
function toggleFreemiumEnabled() {
    const s = getFreemiumSetting('freemium_enabled');
    if (s) {
        s.value = !s.value;
        saveSetting(s);
    }
}

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
        case 'branding': return 'bg-amber-100 text-amber-700';
        case 'freemium': return 'bg-pink-100 text-pink-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}

async function uploadBranding(type: 'logo' | 'header_icon', file: File) {
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (ext !== 'png' && ext !== 'svg') {
        alert('Only PNG and SVG files are allowed.');
        return;
    }
    if (type === 'logo') uploadingLogo.value = true;
    else uploadingHeaderIcon.value = true;
    try {
        const formData = new FormData();
        formData.append('type', type === 'logo' ? 'logo' : 'header_icon');
        formData.append('file', file);
        const res = await fetch('/superadmin/settings/upload-branding', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: formData,
        });
        const data = await res.json();
        if (res.ok) {
            if (type === 'logo') brandingUrls.value.app_logo_url = data.url;
            else brandingUrls.value.header_icon_url = data.url;
            successMessage.value = 'Branding updated successfully';
            setTimeout(() => { successMessage.value = ''; }, 3000);
        } else {
            alert(data.message || 'Upload failed');
        }
    } catch (e) {
        console.error(e);
        alert('Upload failed');
    } finally {
        if (type === 'logo') uploadingLogo.value = false;
        else uploadingHeaderIcon.value = false;
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

            <!-- Freemium: NEMSU Match Plus toggle -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize bg-pink-100 text-pink-700">Freemium</span>
                        <h2 class="text-lg font-bold text-gray-900">NEMSU Match Plus</h2>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">When <strong>ON</strong>, free users get limited daily likes; Plus subscribers get unlimited likes, see who liked you, boost, and extra filters.</p>
                    <div class="flex items-center justify-between gap-4">
                        <span class="font-medium text-gray-900">Enable Freemium / Plus features</span>
                        <button
                            v-if="getFreemiumSetting('freemium_enabled')"
                            type="button"
                            @click="toggleFreemiumEnabled"
                            :class="['relative w-14 h-7 rounded-full transition-colors duration-200', getFreemiumSetting('freemium_enabled')?.value ? 'bg-gradient-to-r from-pink-500 to-rose-500' : 'bg-gray-300']"
                        >
                            <span
                                :class="['absolute top-1 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200', getFreemiumSetting('freemium_enabled')?.value ? 'left-8' : 'left-1']"
                            />
                        </button>
                    </div>
                    <div v-if="getFreemiumSetting('freemium_enabled')?.value" class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Plus price (₱/month)</span>
                            <p class="font-semibold">{{ getFreemiumSetting('plus_monthly_price')?.value ?? 49 }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Free daily likes</span>
                            <p class="font-semibold">{{ getFreemiumSetting('free_daily_likes_limit')?.value ?? 20 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Browser notification test -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <Bell class="w-5 h-5 text-indigo-600" />
                        <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize bg-indigo-100 text-indigo-700">Testing</span>
                        <h2 class="text-lg font-bold text-gray-900">Browser notification (Pusher)</h2>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Test that real-time browser notifications work. Users must allow notifications and enable them in Account → Browser notifications.</p>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <span class="text-sm text-gray-600">Permission:</span>
                        <span :class="['text-sm font-medium px-2 py-1 rounded', browserNotif.permission === 'granted' ? 'bg-green-100 text-green-800' : browserNotif.permission === 'denied' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800']">
                            {{ browserNotif.permission }}
                        </span>
                        <span v-if="browserNotif.isSupported && browserNotif.isEnabled" class="text-sm text-green-600">· Notifications enabled</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :disabled="!browserNotif.isSupported"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="sendTestBrowserNotification"
                        >
                            Request permission & show local test
                        </button>
                        <button
                            type="button"
                            :disabled="testPusherSending"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors disabled:opacity-50"
                            @click="sendTestViaPusher"
                        >
                            {{ testPusherSending ? 'Sending…' : 'Send test via Pusher' }}
                        </button>
                    </div>
                    <p v-if="testPusherMessage" class="mt-3 text-sm text-gray-600">{{ testPusherMessage }}</p>
                </div>
            </div>

            <!-- Branding: App Logo & Header Icon -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize bg-amber-100 text-amber-700">Branding</span>
                        <h2 class="text-lg font-bold text-gray-900">App Logo & Header Icon</h2>
                    </div>
                </div>
                <div class="p-6 grid sm:grid-cols-2 gap-6">
                    <!-- App Logo -->
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 flex flex-col items-center justify-center min-h-[200px] bg-gray-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-3">App Logo</p>
                        <div v-if="brandingUrls.app_logo_url" class="mb-3 max-h-24 flex items-center justify-center">
                            <img :src="brandingUrls.app_logo_url" alt="App logo" class="max-h-24 w-auto object-contain" />
                        </div>
                        <div v-else class="mb-3 w-20 h-20 rounded-xl bg-gray-200 flex items-center justify-center">
                            <Image class="w-10 h-10 text-gray-400" />
                        </div>
                        <p class="text-xs text-gray-500 mb-3">PNG or SVG only</p>
                        <label class="cursor-pointer px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                            <input
                                type="file"
                                accept=".png,.svg,image/png,image/svg+xml"
                                class="hidden"
                                :disabled="uploadingLogo"
                                @change="(e) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) uploadBranding('logo', f); (e.target as HTMLInputElement).value = ''; }"
                            />
                            {{ uploadingLogo ? 'Uploading…' : 'Choose file' }}
                        </label>
                    </div>
                    <!-- Header Icon -->
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 flex flex-col items-center justify-center min-h-[200px] bg-gray-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Header Icon</p>
                        <div v-if="brandingUrls.header_icon_url" class="mb-3 max-h-16 flex items-center justify-center">
                            <img :src="brandingUrls.header_icon_url" alt="Header icon" class="max-h-16 w-auto object-contain" />
                        </div>
                        <div v-else class="mb-3 w-16 h-16 rounded-xl bg-gray-200 flex items-center justify-center">
                            <Image class="w-8 h-8 text-gray-400" />
                        </div>
                        <p class="text-xs text-gray-500 mb-3">PNG or SVG only</p>
                        <label class="cursor-pointer px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                            <input
                                type="file"
                                accept=".png,.svg,image/png,image/svg+xml"
                                class="hidden"
                                :disabled="uploadingHeaderIcon"
                                @change="(e) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) uploadBranding('header_icon', f); (e.target as HTMLInputElement).value = ''; }"
                            />
                            {{ uploadingHeaderIcon ? 'Uploading…' : 'Choose file' }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Settings Groups -->
            <div class="space-y-6">
                <div
                    v-for="(groupSettings, groupName) in settingsWithoutBranding"
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
