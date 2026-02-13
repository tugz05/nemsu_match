<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, User, Check, X } from 'lucide-vue-next';
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

interface AppealRow {
    id: number;
    message: string;
    status: string;
    review_notes: string | null;
    created_at: string;
    reviewed_at: string | null;
    user: BasicUser | null;
    report: { id: number; reported_user_id: number; reason: string; status: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination<T> {
    data: T[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
}

const props = defineProps<{
    appeals: Pagination<AppealRow>;
    filters: { status: string; search: string };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'pending');
const loading = ref(false);
const reviewNotes = ref('');

function applyFilters() {
    router.get('/superadmin/appeals', {
        search: search.value,
        status: status.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                decision,
                review_notes: reviewNotes.value.trim() || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'Failed to review appeal.');
            return;
        }
        reviewNotes.value = '';
        applyFilters();
    } catch (e) {
        console.error(e);
        alert('Failed to review appeal.');
    } finally {
        loading.value = false;
    }
}

const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];
</script>

<template>
    <Head title="Appeals - Superadmin" />
    <SuperadminLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Appeals</h1>
                <p class="text-gray-600 mt-1">View and accept or reject appeals from disabled accounts. Approving an appeal re-enables the user's account.</p>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-wrap gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Search by name or email..."
                            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <select
                        v-model="status"
                        class="px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[140px]"
                    >
                        <option value="">All statuses</option>
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <button @click="applyFilters" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold">
                        Apply
                    </button>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Review notes (optional) — applied when you click Approve or Reject</label>
                    <input
                        v-model="reviewNotes"
                        type="text"
                        placeholder="e.g. Reason for decision..."
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 text-sm"
                    />
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">User</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Appeal message</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Status</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Submitted</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="a in appeals.data" :key="a.id" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-gray-900">{{ a.user?.display_name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ a.user?.email ?? '—' }}</div>
                                    <div v-if="a.user?.is_disabled" class="text-xs text-amber-600 mt-0.5">Account disabled</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 max-w-xs">
                                    <span class="line-clamp-2">{{ a.message }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="{
                                            'bg-amber-100 text-amber-700': a.status === 'pending',
                                            'bg-green-100 text-green-700': a.status === 'approved',
                                            'bg-red-100 text-red-700': a.status === 'rejected',
                                        }"
                                    >
                                        {{ a.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ a.created_at ? new Date(a.created_at).toLocaleString() : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end items-center gap-2">
                                        <Link
                                            v-if="a.user"
                                            :href="`/profile/${a.user.id}`"
                                            class="p-2 rounded-lg text-purple-600 hover:bg-purple-50"
                                            title="View profile"
                                        >
                                            <User class="w-4 h-4" />
                                        </Link>
                                        <template v-if="a.status === 'pending'">
                                            <button
                                                :disabled="loading"
                                                @click="reviewAppeal(a.id, 'approved')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-green-100 text-green-700 hover:bg-green-200 disabled:opacity-50"
                                                title="Approve appeal and re-enable account"
                                            >
                                                <Check class="w-4 h-4" />
                                                Approve
                                            </button>
                                            <button
                                                :disabled="loading"
                                                @click="reviewAppeal(a.id, 'rejected')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-red-100 text-red-700 hover:bg-red-200 disabled:opacity-50"
                                                title="Reject appeal"
                                            >
                                                <X class="w-4 h-4" />
                                                Reject
                                            </button>
                                        </template>
                                        <span v-else-if="a.review_notes" class="text-xs text-gray-500 max-w-[120px] truncate" :title="a.review_notes">
                                            {{ a.review_notes }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="appeals.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">No appeals found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="appeals.links?.length" class="mt-6 flex flex-wrap gap-2">
                <Link
                    v-for="link in appeals.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1.5 rounded-lg text-sm border"
                    :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200'"
                />
            </div>
        </div>
    </SuperadminLayout>
</template>
