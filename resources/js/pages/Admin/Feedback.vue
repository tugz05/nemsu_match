<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminSidebar from './adminSidebar/AdminSidebar.vue';
import { MessageCircle } from 'lucide-vue-next';

type FeedbackUser = {
    id: number;
    display_name: string | null;
    fullname: string | null;
    email: string;
};

type FeedbackItem = {
    id: number;
    category: string | null;
    message: string;
    is_read: boolean;
    created_at: string;
    user: FeedbackUser | null;
};

type Pagination<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

const currentRoute = 'admin.feedback';

const page = usePage();
const feedbacks = computed<Pagination<FeedbackItem> | null>(() => (page.props as any).feedbacks ?? null);

function markAsRead(id: number) {
    router.put(`/admin/feedback/${id}/read`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <AdminSidebar :current-route="currentRoute" />

        <div class="flex-1">
            <Head title="Admin • User Feedback" />

            <header class="bg-white border-b border-gray-200">
                <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <MessageCircle class="w-6 h-6 text-blue-600" />
                        <h1 class="text-lg font-bold text-gray-900">User Feedback</h1>
                    </div>
                    <Link
                        href="/admin/dashboard"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </header>

            <main class="max-w-6xl mx-auto p-6 space-y-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Messages submitted by users about their experience with NEMSU Match.
                        </p>
                        <div v-if="feedbacks" class="text-xs text-gray-500">
                            Showing
                            <span class="font-semibold">{{ feedbacks.from }}</span>
                            –
                            <span class="font-semibold">{{ feedbacks.to }}</span>
                            of
                            <span class="font-semibold">{{ feedbacks.total }}</span>
                            feedback
                        </div>
                    </div>

                    <div v-if="!feedbacks || feedbacks.data.length === 0" class="px-6 py-10 text-center text-gray-500">
                        No feedback submitted yet.
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="fb in feedbacks.data"
                            :key="fb.id"
                            class="px-6 py-4 flex gap-4 hover:bg-gray-50"
                        >
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-sm font-semibold text-blue-700">
                                {{ (fb.user?.display_name || fb.user?.fullname || 'U').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-900">
                                                {{ fb.user?.display_name || fb.user?.fullname || 'Unknown user' }}
                                            </span>
                                            <span v-if="fb.category" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                                {{ fb.category }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ fb.user?.email || 'No email available' }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 text-right">
                                        <div>{{ new Date(fb.created_at).toLocaleString() }}</div>
                                        <div class="mt-1">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                                :class="fb.is_read ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'"
                                            >
                                                {{ fb.is_read ? 'Read' : 'New' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-800 whitespace-pre-line">
                                    {{ fb.message }}
                                </p>
                            </div>
                            <div class="flex items-center">
                                <button
                                    v-if="!fb.is_read"
                                    type="button"
                                    class="px-3 py-1.5 rounded-lg border border-blue-200 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                    @click="markAsRead(fb.id)"
                                >
                                    Mark as read
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="feedbacks && feedbacks.links && feedbacks.links.length"
                        class="px-6 py-3 border-t border-gray-100 flex flex-wrap gap-2 justify-end bg-gray-50"
                    >
                        <Link
                            v-for="link in feedbacks.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs border"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200'"
                        />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

