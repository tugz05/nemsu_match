<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Filter, ChevronDown, Eye, Edit, Trash2, X } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';

interface User {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    nemsu_id: string;
    profile_picture: string | null;
    gender: string;
    campus: string;
    academic_program: string;
    year_level: string;
    profile_completed: boolean;
    email_verified_at: string | null;
    is_admin: boolean;
    last_seen_at: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination {
    current_page: number;
    data: User[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

interface Filters {
    search: string;
    gender: string;
    status: string;
    sort_by: string;
    sort_dir: string;
}

const props = defineProps<{
    users: Pagination;
    filters: Filters;
}>();

const showFilters = ref(false);
const localFilters = ref<Filters>({ ...props.filters });

function applyFilters() {
    router.get('/superadmin/users', localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
    showFilters.value = false;
}

function clearFilters() {
    localFilters.value = {
        search: '',
        gender: '',
        status: '',
        sort_by: 'created_at',
        sort_dir: 'desc',
    };
    applyFilters();
}

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return 'Never';
    
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return date.toLocaleDateString();
}

function viewUser(user: User) {
    // Navigate to user profile or show detail modal
    router.visit(`/profile/${user.id}`);
}
</script>

<template>
    <Head title="Users Management - Superadmin" />
    
    <SuperadminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Users Management</h1>
                <p class="text-gray-600 mt-1">Manage and configure all users</p>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 mb-6">
                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="localFilters.search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by name, email, or NEMSU ID..."
                            class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors"
                        />
                    </div>

                    <!-- Filter Button -->
                    <button
                        type="button"
                        @click="showFilters = !showFilters"
                        class="flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors"
                    >
                        <Filter class="w-5 h-5" />
                        Filters
                        <ChevronDown 
                            class="w-4 h-4 transition-transform"
                            :class="{ 'rotate-180': showFilters }"
                        />
                    </button>

                    <!-- Apply Search -->
                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
                    >
                        Search
                    </button>
                </div>

                <!-- Expanded Filters -->
                <Transition name="expand">
                    <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Gender Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                            <select
                                v-model="localFilters.gender"
                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500"
                            >
                                <option value="">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="non-binary">Non-Binary</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select
                                v-model="localFilters.status"
                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="verified">Verified</option>
                                <option value="unverified">Unverified</option>
                                <option value="completed">Profile Completed</option>
                                <option value="incomplete">Profile Incomplete</option>
                                <option value="admin">Admins</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sort By</label>
                            <select
                                v-model="localFilters.sort_by"
                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500"
                            >
                                <option value="created_at">Date Joined</option>
                                <option value="last_seen_at">Last Seen</option>
                                <option value="display_name">Name</option>
                            </select>
                        </div>

                        <!-- Sort Direction -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Order</label>
                            <select
                                v-model="localFilters.sort_dir"
                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500"
                            >
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="md:col-span-4 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="clearFilters"
                                class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors"
                            >
                                <X class="w-4 h-4" />
                                Clear Filters
                            </button>
                            <button
                                type="button"
                                @click="applyFilters"
                                class="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
                            >
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Results Count -->
            <div class="mb-4 text-sm text-gray-600">
                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">User</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">NEMSU ID</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Campus</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Status</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Last Seen</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Joined</th>
                                <th class="text-right py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="border-t border-gray-100 hover:bg-gray-50 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                                            <img 
                                                v-if="user.profile_picture" 
                                                :src="`/storage/${user.profile_picture}`" 
                                                class="w-full h-full object-cover"
                                                :alt="user.display_name"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                                {{ user.display_name?.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 text-sm">{{ user.display_name }}</p>
                                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">{{ user.nemsu_id || 'N/A' }}</td>
                                <td class="py-4 px-6 text-sm text-gray-600">{{ user.campus || 'N/A' }}</td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            v-if="user.email_verified_at"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"
                                        >
                                            Verified
                                        </span>
                                        <span
                                            v-if="user.profile_completed"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"
                                        >
                                            Complete
                                        </span>
                                        <span
                                            v-if="user.is_admin"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700"
                                        >
                                            Admin
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    {{ timeAgo(user.last_seen_at) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="viewUser(user)"
                                            class="p-2 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors"
                                            title="View Profile"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    No users found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'px-4 py-2 text-sm font-semibold rounded-lg transition-colors',
                        link.active
                            ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg'
                            : link.url
                                ? 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-200'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                        { 'pointer-events-none': !link.url }
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </SuperadminLayout>
</template>

<style scoped>
.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
