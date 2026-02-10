<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Shield, Lock, Mail, Eye, EyeOff } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    status?: string;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4 relative overflow-hidden">
        <Head title="Admin Login - NEMSU Match" />

        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-20 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-md relative z-10">
            <!-- Security Badge -->
            <div class="flex justify-center mb-8">
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-full flex items-center justify-center shadow-2xl">
                        <Shield class="w-10 h-10 text-white" stroke-width="2.5" />
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/20">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Admin Portal
                    </h1>
                    <p class="text-sm text-gray-600">
                        NEMSU Match Administrative Access
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="form.errors && Object.keys(form.errors).length > 0"
                    class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl"
                >
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-red-200 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p
                                v-for="(error, key) in form.errors"
                                :key="key"
                                class="text-sm text-red-700 font-medium"
                            >
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Success Status -->
                <div
                    v-if="status"
                    class="mb-6 p-4 bg-green-50 border border-green-200 rounded-2xl"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 bg-green-200 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-sm text-green-700 font-medium">{{ status }}</p>
                    </div>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Mail class="w-5 h-5 text-gray-400" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="admin@nemsu.edu.ph"
                                class="w-full pl-12 pr-4 py-3.5 bg-white border-2 border-gray-300 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-900 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Lock class="w-5 h-5 text-gray-400" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="w-full pl-12 pr-12 py-3.5 bg-white border-2 border-gray-300 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                :class="{ 'border-red-500': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="togglePasswordVisibility"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <Eye v-if="!showPassword" class="w-5 h-5" />
                                <EyeOff v-else class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        />
                        <label for="remember" class="ml-2 text-sm text-gray-700 font-medium">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-bold py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 text-base disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign In</span>
                    </Button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-4 bg-white text-gray-500 font-semibold">AUTHORIZED PERSONNEL ONLY</span>
                    </div>
                </div>

                    <!-- Security Notice -->
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-200 rounded-2xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <Lock class="w-4 h-4 text-blue-600" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-blue-900 mb-1">Secure Access</h3>
                                <p class="text-xs text-blue-700 leading-relaxed">
                                    This portal is restricted to authorized administrators only.
                                    Use your admin credentials to access the system.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Role Types -->
                    <div class="pt-4 space-y-2">
                        <p class="text-xs font-semibold text-gray-500 text-center mb-3">Access Levels</p>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-3 text-center">
                                <div class="w-8 h-8 bg-purple-200 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <Shield class="w-4 h-4 text-purple-700" />
                                </div>
                                <p class="text-xs font-bold text-purple-900">Superadmin</p>
                                <p class="text-[10px] text-purple-600 mt-1">Full Access</p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-3 text-center">
                                <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <Shield class="w-4 h-4 text-blue-700" />
                                </div>
                                <p class="text-xs font-bold text-blue-900">Admin</p>
                                <p class="text-[10px] text-blue-600 mt-1">Management</p>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-3 text-center">
                                <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <Shield class="w-4 h-4 text-green-700" />
                                </div>
                                <p class="text-xs font-bold text-green-900">Editor</p>
                                <p class="text-[10px] text-green-600 mt-1">Content</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <a href="/" class="hover:text-blue-600 transition-colors font-medium">
                                ← Back to Main Site
                            </a>
                            <span class="font-semibold">NEMSU Match Admin</span>
                        </div>
                    </div>
                </div>

            <!-- Bottom Note -->
            <p class="text-center text-xs text-white/60 mt-6">
                All administrative activities are logged and monitored for security purposes.
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% {
        opacity: 0.3;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
