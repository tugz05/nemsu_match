<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, User } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';

interface DisabledUser {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    profile_picture: string | null;
    disabled_reason: string | null;
    disabled_at: string | null;
    pending_appeals_count: number;
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
    users: Pagination<DisabledUser>;
    filters: { search: string };
}>();

const search = ref(props.filters.search || '');

function applySearch() {
    router.get('/superadmin/disabled-users', { search: search.value }, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Disabled Accounts - Superadmin" />
    <SuperadminLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Disabled Accounts</h1>
                <p class="text-gray-600 mt-1">Users blocked from browsing; review their appeal activity.</p>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-6">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="search" @keyup.enter="applySearch" placeholder="Search disabled users..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <button @click="applySearch" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold">Search</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">User</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Disabled At</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Reason</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Pending Appeals</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in users.data" :key="u.id" class="border-t border-gray-100">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-gray-900">{{ u.display_name }}</div>
                                    <div class="text-xs text-gray-500">{{ u.email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ u.disabled_at ? new Date(u.disabled_at).toLocaleString() : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ u.disabled_reason || 'No reason provided' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="u.pending_appeals_count > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'">
                                        {{ u.pending_appeals_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="`/profile/${u.id}`" class="p-2 rounded-lg text-purple-600 hover:bg-purple-50" title="View profile">
                                            <User class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">No disabled users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="users.links?.length" class="mt-6 flex flex-wrap gap-2">
                <Link v-for="link in users.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 rounded-lg text-sm border"
                    :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200'" />
            </div>
        </div>
    </SuperadminLayout>
</template>

