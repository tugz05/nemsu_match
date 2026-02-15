<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminSidebar from './adminSidebar/AdminSidebar.vue';
import { UserX } from 'lucide-vue-next';

type BannedUser = {
    id: number;
    display_name: string | null;
    fullname: string | null;
    email: string;
    profile_picture: string | null;
    banned_at: string | null;
    ban_reason: string | null;
};

type Pagination<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
};

const currentRoute = 'admin.banned';

const page = usePage();
const users = computed<Pagination<BannedUser> | null>(() => (page.props as any).users ?? null);

function unbanUser(userId: number) {
    if (!confirm('Unban this user?')) return;
    router.delete(`/admin/users/${userId}/unban`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <AdminSidebar :current-route="currentRoute" />

        <div class="flex-1">
            <Head title="Admin • Banned Users" />

            <header class="bg-white border-b border-gray-200">
                <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <UserX class="w-6 h-6 text-red-600" />
                        <h1 class="text-lg font-bold text-gray-900">Banned Users</h1>
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
                        <div>
                            <p class="text-sm text-gray-600">
                                Users who are currently banned from using NEMSU Match.
                            </p>
                        </div>
                        <div v-if="users" class="text-xs text-gray-500">
                            Showing
                            <span class="font-semibold">{{ users.from }}</span>
                            –
                            <span class="font-semibold">{{ users.to }}</span>
                            of
                            <span class="font-semibold">{{ users.total }}</span>
                            users
                        </div>
                    </div>

                    <div v-if="!users || users.data.length === 0" class="px-6 py-10 text-center text-gray-500">
                        No banned users found.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-6 py-3 text-left">User</th>
                                    <th class="px-6 py-3 text-left">Email</th>
                                    <th class="px-6 py-3 text-left">Reason</th>
                                    <th class="px-6 py-3 text-left">Banned At</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center text-xs font-semibold text-blue-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="user.profile_picture"
                                                    :src="user.profile_picture"
                                                    alt=""
                                                    class="w-full h-full object-cover"
                                                />
                                                <span v-else>
                                                    {{ (user.display_name || user.fullname || 'U').charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">
                                                    {{ user.display_name || user.fullname || 'Unknown user' }}
                                                </div>
                                                <div class="text-xs text-gray-500">ID #{{ user.id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        {{ user.ban_reason || 'No reason provided' }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        {{ user.banned_at ? new Date(user.banned_at).toLocaleString() : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-green-200 text-xs font-semibold text-green-700 hover:bg-green-50"
                                            @click="unbanUser(user.id)"
                                        >
                                            Unban
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users && users.links && users.links.length"
                        class="px-6 py-3 border-t border-gray-100 flex flex-wrap gap-2 justify-end bg-gray-50"
                    >
                        <Link
                            v-for="link in users.links"
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

