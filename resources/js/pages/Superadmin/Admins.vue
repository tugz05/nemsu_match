<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { UserCog, Plus, Search, Trash2, Edit, CheckCircle, XCircle } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';
import { useCsrfToken } from '@/composables/useCsrfToken';

interface User {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    profile_picture: string | null;
    created_at: string;
    last_seen_at: string | null;
    is_online: boolean;
}

interface AdminRole {
    id: number;
    user_id: number;
    user: User | null;
    role: 'superadmin' | 'admin' | 'editor';
    permissions: any;
    is_active: boolean;
    assigned_at: string;
    assigned_by: { id: number; display_name: string; fullname: string } | null;
}

const props = defineProps<{
    adminRoles: AdminRole[];
}>();

const getCsrfToken = useCsrfToken();
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const selectedRole = ref<AdminRole | null>(null);
const userSearchQuery = ref('');
const userSearchResults = ref<User[]>([]);
const searchingUsers = ref(false);
const selectedUser = ref<User | null>(null);
const newRole = ref<'superadmin' | 'admin' | 'editor'>('editor');
const editRole = ref<'superadmin' | 'admin' | 'editor'>('editor');
const editIsActive = ref(true);
const submitting = ref(false);

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function searchUsers() {
    if (!userSearchQuery.value.trim()) {
        userSearchResults.value = [];
        return;
    }

    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(async () => {
        searchingUsers.value = true;
        try {
            const res = await fetch(`/superadmin/admins/search-users?q=${encodeURIComponent(userSearchQuery.value)}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                userSearchResults.value = data.users || [];
            }
        } catch (e) {
            console.error(e);
        } finally {
            searchingUsers.value = false;
        }
    }, 300);
}

function selectUser(user: User) {
    selectedUser.value = user;
    userSearchQuery.value = '';
    userSearchResults.value = [];
}

function openAddDialog() {
    showAddDialog.value = true;
    selectedUser.value = null;
    newRole.value = 'editor';
}

function closeAddDialog() {
    showAddDialog.value = false;
    selectedUser.value = null;
    userSearchQuery.value = '';
    userSearchResults.value = [];
}

function openEditDialog(adminRole: AdminRole) {
    selectedRole.value = adminRole;
    editRole.value = adminRole.role;
    editIsActive.value = adminRole.is_active;
    showEditDialog.value = true;
}

function closeEditDialog() {
    showEditDialog.value = false;
    selectedRole.value = null;
}

async function assignRole() {
    if (!selectedUser.value || submitting.value) return;

    submitting.value = true;
    try {
        const res = await fetch('/superadmin/admins', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                user_id: selectedUser.value.id,
                role: newRole.value,
            }),
        });

        if (res.ok) {
            router.reload();
            closeAddDialog();
        } else {
            const data = await res.json();
            alert(data.message || 'Failed to assign role');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to assign role');
    } finally {
        submitting.value = false;
    }
}

async function updateRole() {
    if (!selectedRole.value || submitting.value) return;

    submitting.value = true;
    try {
        const res = await fetch(`/superadmin/admins/${selectedRole.value.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                role: editRole.value,
                is_active: editIsActive.value,
            }),
        });

        if (res.ok) {
            router.reload();
            closeEditDialog();
        } else {
            const data = await res.json();
            alert(data.message || 'Failed to update role');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to update role');
    } finally {
        submitting.value = false;
    }
}

async function removeRole(adminRole: AdminRole) {
    if (!confirm(`Are you sure you want to remove ${adminRole.role} role from ${adminRole.user?.display_name}?`)) {
        return;
    }

    try {
        const res = await fetch(`/superadmin/admins/${adminRole.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        });

        if (res.ok) {
            router.reload();
        } else {
            const data = await res.json();
            alert(data.message || 'Failed to remove role');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to remove role');
    }
}

function getRoleBadgeClass(role: string): string {
    switch (role) {
        case 'superadmin': return 'bg-purple-100 text-purple-700';
        case 'admin': return 'bg-blue-100 text-blue-700';
        case 'editor': return 'bg-green-100 text-green-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}
</script>

<template>
    <Head title="Manage Admins - Superadmin" />
    
    <SuperadminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Admins & Editors</h1>
                    <p class="text-gray-600 mt-1">Manage admin and editor roles</p>
                </div>
                <button
                    type="button"
                    @click="openAddDialog"
                    class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all"
                >
                    <Plus class="w-5 h-5" />
                    Assign Role
                </button>
            </div>

            <!-- Admin Roles List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">User</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Role</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Status</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Assigned By</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Assigned</th>
                            <th class="text-right py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="adminRole in adminRoles"
                            :key="adminRole.id"
                            class="border-t border-gray-100 hover:bg-gray-50 transition-colors"
                        >
                            <td class="py-4 px-6">
                                <div v-if="adminRole.user" class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                                        <img 
                                            v-if="adminRole.user.profile_picture" 
                                            :src="`/storage/${adminRole.user.profile_picture}`" 
                                            class="w-full h-full object-cover"
                                            :alt="adminRole.user.display_name"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                            {{ adminRole.user.display_name?.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm">{{ adminRole.user.display_name }}</p>
                                        <p class="text-xs text-gray-500">{{ adminRole.user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span 
                                    class="px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="getRoleBadgeClass(adminRole.role)"
                                >
                                    {{ adminRole.role.toUpperCase() }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span
                                    v-if="adminRole.is_active"
                                    class="flex items-center gap-1 text-green-600 text-sm"
                                >
                                    <CheckCircle class="w-4 h-4" />
                                    Active
                                </span>
                                <span
                                    v-else
                                    class="flex items-center gap-1 text-gray-400 text-sm"
                                >
                                    <XCircle class="w-4 h-4" />
                                    Inactive
                                </span>
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-600">
                                {{ adminRole.assigned_by?.display_name || 'System' }}
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-600">
                                {{ new Date(adminRole.assigned_at).toLocaleDateString() }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEditDialog(adminRole)"
                                        class="p-2 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors"
                                        title="Edit"
                                    >
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeRole(adminRole)"
                                        class="p-2 hover:bg-red-50 text-red-600 rounded-lg transition-colors"
                                        title="Remove"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="adminRoles.length === 0">
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                No admin roles assigned yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Role Dialog -->
        <div
            v-if="showAddDialog"
            class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4"
            @click.self="closeAddDialog"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Assign Admin Role</h3>
                
                <!-- User Search -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Search User</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="userSearchQuery"
                            @input="searchUsers"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors"
                        />
                    </div>
                    
                    <!-- Search Results -->
                    <div v-if="userSearchResults.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-xl">
                        <button
                            v-for="user in userSearchResults"
                            :key="user.id"
                            type="button"
                            @click="selectUser(user)"
                            class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 transition-colors text-left"
                        >
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                                <img 
                                    v-if="user.profile_picture" 
                                    :src="`/storage/${user.profile_picture}`" 
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                    {{ user.display_name?.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ user.display_name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                            </div>
                        </button>
                    </div>
                    
                    <!-- Selected User -->
                    <div v-if="selectedUser" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 overflow-hidden">
                            <img 
                                v-if="selectedUser.profile_picture" 
                                :src="`/storage/${selectedUser.profile_picture}`" 
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-blue-600 font-bold text-sm">
                                {{ selectedUser.display_name?.charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm">{{ selectedUser.display_name }}</p>
                            <p class="text-xs text-gray-600">{{ selectedUser.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Role</label>
                    <select
                        v-model="newRole"
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors"
                    >
                        <option value="editor">Editor - Limited permissions</option>
                        <option value="admin">Admin - Full management access</option>
                        <option value="superadmin">Superadmin - Full system access</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="closeAddDialog"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-full transition-colors disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="assignRole"
                        :disabled="!selectedUser || submitting"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="submitting">Assigning...</span>
                        <span v-else>Assign Role</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Role Dialog -->
        <div
            v-if="showEditDialog && selectedRole"
            class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4"
            @click.self="closeEditDialog"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Admin Role</h3>
                
                <div class="mb-4 p-3 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-600">User</p>
                    <p class="font-semibold text-gray-900">{{ selectedRole.user?.display_name }}</p>
                </div>

                <!-- Role Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Role</label>
                    <select
                        v-model="editRole"
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-blue-500 transition-colors"
                    >
                        <option value="editor">Editor - Limited permissions</option>
                        <option value="admin">Admin - Full management access</option>
                        <option value="superadmin">Superadmin - Full system access</option>
                    </select>
                </div>

                <!-- Status Toggle -->
                <div class="mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            v-model="editIsActive"
                            type="checkbox"
                            class="w-5 h-5 text-blue-600 rounded"
                        />
                        <span class="text-sm font-semibold text-gray-900">Active</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="closeEditDialog"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-full transition-colors disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="updateRole"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="submitting">Updating...</span>
                        <span v-else>Update Role</span>
                    </button>
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>

<style scoped>
@keyframes scale-in {
    from {
        transform: scale(0.95);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}
</style>
