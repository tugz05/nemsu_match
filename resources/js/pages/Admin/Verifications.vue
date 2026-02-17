<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    verifications: any;
}>();

// --- NEW FUNCTIONS TO HANDLE CLICKS ---
const approveUser = (id: number) => {
    if (confirm('Are you sure you want to approve this user?')) {
        router.put(`/admin/verifications/${id}/approve`);
    }
};

const rejectUser = (id: number) => {
    if (confirm('Are you sure you want to REJECT and DELETE this user?')) {
        router.delete(`/admin/verifications/${id}/reject`);
    }
};
</script>

<template>
    <Head title="Verifications" />

    <div class="min-h-screen bg-gray-50 p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">User Verifications</h1>
                <Link href="/admin/dashboard" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    &larr; Back to Dashboard
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in verifications?.data" :key="user?.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ user?.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ user?.display_name || 'No Name' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user?.email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    @click="approveUser(user.id)"
                                    class="text-green-600 hover:text-green-900 mr-4 font-bold"
                                    title="Approve User"
                                >
                                    Approve
                                </button>
                                <button
                                    @click="rejectUser(user.id)"
                                    class="text-red-600 hover:text-red-900 font-bold"
                                    title="Delete User"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>

                        <tr v-if="!verifications?.data?.length">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
