<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { MapPin, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';
import { useCsrfToken } from '@/composables/useCsrfToken';

interface Campus {
    id: number;
    name: string;
    code: string | null;
    base_latitude: number | null;
    base_longitude: number | null;
    created_at: string;
}

const props = defineProps<{
    campuses: Campus[];
}>();

const getCsrfToken = useCsrfToken();

function hasBaseLocation(c: Campus): boolean {
    return c.base_latitude != null && c.base_longitude != null;
}

async function destroy(campus: Campus) {
    if (!confirm(`Delete campus "${campus.name}"? This may affect Find Your Match for users on this campus.`)) return;
    try {
        const res = await fetch(`/superadmin/campuses/${campus.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            router.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Failed to delete campus');
        }
    } catch (e) {
        console.error(e);
        alert('Failed to delete campus');
    }
}
</script>

<template>
    <Head title="Campuses - Superadmin" />
    <SuperadminLayout>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Campuses</h1>
                    <p class="text-gray-600 mt-1">Base locations for Find Your Match (same-campus AI match)</p>
                </div>
                <Link
                    href="/superadmin/campuses/create"
                    class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all"
                >
                    <Plus class="w-5 h-5" />
                    Add Campus
                </Link>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Name</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Code</th>
                            <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700">Base location</th>
                            <th class="text-right py-4 px-6 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="c in campuses"
                            :key="c.id"
                            class="border-t border-gray-100 hover:bg-gray-50 transition-colors"
                        >
                            <td class="py-4 px-6 font-medium text-gray-900">{{ c.name }}</td>
                            <td class="py-4 px-6 text-gray-600">{{ c.code || '—' }}</td>
                            <td class="py-4 px-6 text-sm text-gray-600">
                                <span v-if="hasBaseLocation(c)">
                                    {{ c.base_latitude?.toFixed(5) }}, {{ c.base_longitude?.toFixed(5) }}
                                </span>
                                <span v-else class="text-amber-600">Not set</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <Link
                                    :href="`/superadmin/campuses/${c.id}/edit`"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                >
                                    <Pencil class="w-4 h-4" />
                                    Edit
                                </Link>
                                <button
                                    type="button"
                                    @click="destroy(c)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors ml-2"
                                >
                                    <Trash2 class="w-4 h-4" />
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!campuses.length">
                            <td colspan="4" class="py-12 px-6 text-center text-gray-500">
                                No campuses yet. Add one to enable Find Your Match base locations.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </SuperadminLayout>
</template>
