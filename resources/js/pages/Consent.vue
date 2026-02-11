<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { FileCheck, Shield } from 'lucide-vue-next';

const page = usePage();
const appLogoUrl = page.props.branding?.app_logo_url ?? null;

const form = useForm({
    accepted: false,
});

const agree = () => {
    form.post('/consent');
};
</script>

<template>
    <div class="consent-page min-h-screen flex flex-col items-center justify-center p-4 lg:p-8 overflow-hidden relative">
        <Head title="Terms &amp; Conditions - NEMSU Match" />

        <!-- Background -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="consent-orb consent-orb-1" />
            <div class="consent-orb consent-orb-2" />
            <div class="consent-mesh" />
        </div>

        <div class="w-full max-w-lg relative z-10">
            <div class="consent-card">
                <!-- Header -->
                <div class="flex items-center justify-center gap-3 mb-6">
                    <div
                        v-if="appLogoUrl"
                        class="w-12 h-12 rounded-2xl overflow-hidden bg-white/80 shadow-md flex items-center justify-center shrink-0 ring-2 ring-blue-200/50"
                    >
                        <img :src="appLogoUrl" alt="Logo" class="w-9 h-9 object-contain" />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold text-blue-600">NEMSU</span>
                        <span class="text-xl font-bold text-gray-900">Match</span>
                    </div>
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 text-center mb-2">
                    Terms &amp; Conditions
                </h1>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Please read and accept to continue
                </p>

                <!-- Scrollable terms -->
                <div class="terms-box">
                    <div class="terms-content">
                        <h2 class="text-base font-semibold text-gray-900 mt-4 first:mt-0">1. Acceptance</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            By using NEMSU Match, you agree to these Terms and Conditions and our Privacy Policy. This service is exclusively for current NEMSU students with valid @nemsu.edu.ph accounts.
                        </p>

                        <h2 class="text-base font-semibold text-gray-900 mt-4">2. Eligibility</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            You must be at least 18 years old and a currently enrolled student of NEMSU. You are responsible for providing accurate profile information and keeping it updated.
                        </p>

                        <h2 class="text-base font-semibold text-gray-900 mt-4">3. Conduct</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            You agree to treat other users with respect. Harassment, hate speech, impersonation, spam, or any form of abuse is prohibited. We reserve the right to suspend or remove accounts that violate these standards.
                        </p>

                        <h2 class="text-base font-semibold text-gray-900 mt-4">4. Privacy &amp; Data</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Your profile and activity data are used to provide matchmaking and app features. We do not sell your personal data. See our Privacy Policy for full details.
                        </p>

                        <h2 class="text-base font-semibold text-gray-900 mt-4">5. Safety</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Meet in safe, public places when meeting matches in person. Never share passwords or financial information. Report suspicious or inappropriate behavior through the app.
                        </p>

                        <h2 class="text-base font-semibold text-gray-900 mt-4">6. Changes</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            We may update these terms from time to time. Continued use of the app after changes constitutes acceptance. Significant changes may require renewed consent.
                        </p>
                    </div>
                </div>

                <!-- Checkbox + Button -->
                <form @submit.prevent="agree" class="mt-6 space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input
                            v-model="form.accepted"
                            type="checkbox"
                            name="accepted"
                            class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">
                            I have read and agree to the Terms and Conditions and consent to the use of my data as described in the Privacy Policy.
                        </span>
                    </label>
                    <p v-if="form.errors.accepted" class="text-sm text-red-600">
                        {{ form.errors.accepted }}
                    </p>
                    <Button
                        type="submit"
                        :disabled="!form.accepted || form.processing"
                        class="w-full py-5 rounded-2xl font-semibold bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white shadow-lg disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
                    >
                        <FileCheck class="w-5 h-5" />
                        {{ form.processing ? 'Please wait…' : 'I agree — Continue to NEMSU Match' }}
                    </Button>
                </form>

                <p class="mt-4 text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                    <Shield class="w-3.5 h-3.5" />
                    Your data is protected and used only to run the service.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.consent-page {
    background: linear-gradient(160deg, #f8fafc 0%, #f1f5f9 40%, #e0f2fe 100%);
}

.consent-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.45;
}

.consent-orb-1 {
    width: 320px;
    height: 320px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.35) 0%, rgba(6, 182, 212, 0.25) 100%);
    top: -15%;
    right: -10%;
}

.consent-orb-2 {
    width: 240px;
    height: 240px;
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.3) 0%, rgba(59, 130, 246, 0.2) 100%);
    bottom: -10%;
    left: -5%;
}

.consent-mesh {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(59, 130, 246, 0.06) 0%, transparent 50%);
    pointer-events: none;
}

.consent-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    border-radius: 1.5rem;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(59, 130, 246, 0.06);
}

.terms-box {
    max-height: 280px;
    overflow-y: auto;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 1rem;
    background: rgba(248, 250, 252, 0.8);
    padding: 1rem 1.25rem;
}

.terms-box::-webkit-scrollbar {
    width: 6px;
}

.terms-box::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.04);
    border-radius: 3px;
}

.terms-box::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.35);
    border-radius: 3px;
}

.terms-content {
    padding-right: 4px;
}
</style>
