<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reports: {
        type: Array,
        default: () => [] 
    }
});

// State variables
const showModal = ref(false);
const selectedReport = ref(null);
const localStatus = ref(''); // Dito natin ilalagay ang pipiliing status

// Open Modal
const viewReport = (report) => {
    selectedReport.value = report;
    localStatus.value = report.status; // Kopyahin ang current status para sa dropdown
    showModal.value = true;
};

// Close Modal
const closeModal = () => {
    showModal.value = false;
    selectedReport.value = null;
};

// Function: Update Status
const updateStatus = () => {
    if (!selectedReport.value) return;

    router.put(`/admin/message-report/${selectedReport.value.id}`, {
        status: localStatus.value
    }, {
        onSuccess: () => {
            // Pag successful, close modal at mag-notify (optional)
            closeModal(); 
            // alert('Status updated!'); // Uncomment kung gusto mo ng alert
        },
        onError: () => {
            alert('Failed to update status.');
        }
    });
};

// Function: Delete Report
const deleteReport = (id) => {
    if (confirm('Are you sure you want to delete this report? This cannot be undone.')) {
        router.delete(`/admin/message-report/${id}`, {
            onSuccess: () => closeModal(),
            onError: () => alert('An error occurred while deleting.')
        });
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 relative">
        <Head title="Message Reports" />

        <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link href="/admin/dashboard" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Message Reports</h1>
                            <p class="text-sm text-gray-500">Review and manage user reports</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Reported User</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ report.type }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    <div class="font-medium text-gray-900">{{ report.user }}</div>
                                    <div class="text-xs text-gray-500">From: {{ report.reporter }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ report.date }}</td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-yellow-100 text-yellow-700': report.status === 'Pending',
                                        'bg-green-100 text-green-700': report.status === 'Resolved',
                                        'bg-gray-100 text-gray-700': report.status === 'Dismissed'
                                    }" class="px-2 py-1 text-xs font-bold rounded-full">
                                        {{ report.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="viewReport(report)" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1 rounded text-sm font-medium transition">
                                        View
                                    </button>
                                    <button @click="deleteReport(report.id)" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1 rounded text-sm font-medium transition">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                             <tr v-if="reports.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">No reports found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg overflow-hidden">
                
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Manage Report</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="p-6 space-y-4" v-if="selectedReport">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block text-gray-500 font-bold text-xs uppercase">Reporter</span>
                            <span class="text-gray-900">{{ selectedReport.reporter }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 font-bold text-xs uppercase">Reported User</span>
                            <span class="text-gray-900">{{ selectedReport.user }}</span>
                        </div>
                    </div>

                    <hr>

                    <div>
                        <span class="block text-gray-500 font-bold text-xs uppercase">Violation</span>
                        <span class="text-red-600 font-medium">{{ selectedReport.type }}</span>
                        <div class="mt-2 bg-gray-50 p-3 rounded text-gray-700 text-sm border">
                            {{ selectedReport.reason }}
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <label class="block text-xs font-bold text-blue-800 uppercase mb-2">Update Status</label>
                        <div class="flex gap-2">
                            <select 
                                v-model="localStatus" 
                                class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="Pending">Pending</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                            
                            <button 
                                @click="updateStatus"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm"
                            >
                                Save Update
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                    <button @click="deleteReport(selectedReport.id)" class="text-red-600 hover:text-red-800 text-sm font-medium">
                        Delete Report
                    </button>
                    <button @click="closeModal" class="px-4 py-2 bg-white border border-gray-300 rounded text-gray-700 text-sm hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>