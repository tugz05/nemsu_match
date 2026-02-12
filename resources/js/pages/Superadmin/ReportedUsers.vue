<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Eye, User, Ban, X } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';

interface BasicUser {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    profile_picture: string | null;
    is_disabled?: boolean;
    disabled_reason?: string | null;
    disabled_at?: string | null;
}

interface ReportRow {
    id: number;
    reason: string;
    description: string | null;
    status: string;
    created_at: string;
    reporter: BasicUser | null;
    reported_user: BasicUser | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    from: number;
    to: number;
    total: number;
}

interface Filters {
    search: string;
    status: string;
}

const props = defineProps<{
    reports: Pagination<ReportRow>;
    filters: Filters;
}>();

const localSearch = ref(props.filters.search || '');
const localStatus = ref(props.filters.status || 'pending');
const loading = ref(false);
const detailsOpen = ref(false);
const detailsLoading = ref(false);
const selectedReport = ref<any>(null);
const disableReason = ref('');
const appealReviewNotes = ref('');

function applyFilters() {
    router.get('/superadmin/reported-users', {
        search: localSearch.value,
        status: localStatus.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

async function openDetails(reportId: number) {
    detailsOpen.value = true;
    detailsLoading.value = true;
    selectedReport.value = null;
    disableReason.value = '';
    appealReviewNotes.value = '';
    try {
        const res = await fetch(`/superadmin/reported-users/${reportId}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (res.ok) selectedReport.value = data.report;
    } catch (e) {
        console.error(e);
    } finally {
        detailsLoading.value = false;
    }
}

async function reviewAppeal(appealId: number, decision: 'approved' | 'rejected') {
    if (loading.value) return;
    loading.value = true;
    try {
        const res = await fetch(`/superadmin/appeals/${appealId}/review`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                decision,
                review_notes: appealReviewNotes.value.trim() || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'Failed to review appeal.');
            return;
        }
        if (selectedReport.value?.id) {
            await openDetails(selectedReport.value.id);
        }
        applyFilters();
    } catch (e) {
        console.error(e);
        alert('Failed to review appeal.');
    } finally {
        loading.value = false;
    }
}

async function disableAccount(reportId: number) {
    if (loading.value) return;
    loading.value = true;
    try {
        const res = await fetch(`/superadmin/reported-users/${reportId}/disable-account`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ disabled_reason: disableReason.value.trim() || null }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'Failed to disable account.');
            return;
        }
        detailsOpen.value = false;
        applyFilters();
    } catch (e) {
        console.error(e);
        alert('Failed to disable account.');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Reported Users - Superadmin" />
    <SuperadminLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Reported Users</h1>
                <p class="text-gray-600 mt-1">Review reports and take action on violating accounts.</p>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="localSearch" @keyup.enter="applyFilters" placeholder="Search reported user..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <select v-model="localStatus" class="px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="dismissed">Dismissed</option>
                    </select>
                    <button @click="applyFilters" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold">Apply</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Reported User</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Reporter</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Reason</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Status</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Date</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in reports.data" :key="report.id" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-gray-900">{{ report.reported_user?.display_name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ report.reported_user?.email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-gray-900">{{ report.reporter?.display_name || 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ report.reporter?.email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 capitalize">{{ report.reason }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="report.status === 'pending' ? 'bg-amber-100 text-amber-700' : (report.status === 'reviewed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')">
                                        {{ report.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ new Date(report.created_at).toLocaleString() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openDetails(report.id)" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50" title="View report details"><Eye class="w-4 h-4" /></button>
                                        <Link v-if="report.reported_user" :href="`/profile/${report.reported_user.id}`" class="p-2 rounded-lg text-purple-600 hover:bg-purple-50" title="View profile"><User class="w-4 h-4" /></Link>
                                        <button v-if="report.status === 'pending'" @click="openDetails(report.id)" class="p-2 rounded-lg text-red-600 hover:bg-red-50" title="Disable account"><Ban class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="reports.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-500">No reports found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="reports.links?.length" class="mt-6 flex flex-wrap gap-2">
                <Link v-for="link in reports.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 rounded-lg text-sm border"
                    :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200'" />
            </div>
        </div>

        <div v-if="detailsOpen" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4" @click.self="detailsOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">Report Details</h2>
                    <button @click="detailsOpen = false" class="p-1 rounded-lg hover:bg-gray-100"><X class="w-4 h-4 text-gray-600" /></button>
                </div>
                <div v-if="detailsLoading" class="text-sm text-gray-500">Loading...</div>
                <div v-else-if="selectedReport" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Reported user</p>
                            <p class="font-semibold text-gray-900">{{ selectedReport.reported_user?.display_name }} ({{ selectedReport.reported_user?.email }})</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Reporter</p>
                            <p class="font-semibold text-gray-900">{{ selectedReport.reporter?.display_name }} ({{ selectedReport.reporter?.email }})</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Reason</p>
                            <p class="font-semibold text-gray-900 capitalize">{{ selectedReport.reason }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Status</p>
                            <p class="font-semibold text-gray-900 capitalize">{{ selectedReport.status }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Description</p>
                        <p class="text-sm text-gray-800">{{ selectedReport.description || 'No description provided.' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Appeals</p>
                        <div v-if="selectedReport.appeals?.length" class="space-y-2 max-h-36 overflow-auto pr-1">
                            <div v-for="appeal in selectedReport.appeals" :key="appeal.id" class="text-xs p-2 rounded-lg bg-gray-50 border border-gray-200">
                                <div class="font-semibold text-gray-700 capitalize mb-1">{{ appeal.status }}</div>
                                <div class="text-gray-700">{{ appeal.message }}</div>
                                <div v-if="appeal.review_notes" class="mt-1 text-gray-600">
                                    <span class="font-semibold">Review notes:</span> {{ appeal.review_notes }}
                                </div>
                                <div v-if="appeal.status === 'pending'" class="mt-2 flex flex-wrap gap-2">
                                    <button
                                        @click="reviewAppeal(appeal.id, 'approved')"
                                        :disabled="loading"
                                        class="px-2 py-1 rounded-lg bg-green-600 text-white text-[11px] font-semibold disabled:opacity-50"
                                    >
                                        Approve Appeal
                                    </button>
                                    <button
                                        @click="reviewAppeal(appeal.id, 'rejected')"
                                        :disabled="loading"
                                        class="px-2 py-1 rounded-lg bg-red-600 text-white text-[11px] font-semibold disabled:opacity-50"
                                    >
                                        Reject Appeal
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">No appeals yet.</p>
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <label class="block text-xs text-gray-500 mb-1">Appeal review notes (optional)</label>
                        <textarea
                            v-model="appealReviewNotes"
                            rows="2"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Visible in superadmin review context"
                        />
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <label class="block text-xs text-gray-500 mb-1">Disable reason</label>
                        <textarea v-model="disableReason" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Reason shown to the user..." />
                        <div class="mt-3 flex justify-end gap-2">
                            <button @click="detailsOpen = false" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold">Close</button>
                            <button @click="disableAccount(selectedReport.id)" :disabled="loading || selectedReport.reported_user?.is_disabled" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold disabled:opacity-50">
                                {{ selectedReport.reported_user?.is_disabled ? 'Already disabled' : (loading ? 'Disabling...' : 'Disable account') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>

