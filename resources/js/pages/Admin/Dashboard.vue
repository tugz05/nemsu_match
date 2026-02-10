<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Users, MessageSquare, Heart, FileText, ArrowRight, Shield } from 'lucide-vue-next';

interface Stats {
    total_users: number;
    active_users_today: number;
    new_users_this_week: number;
    total_matches: number;
    total_conversations: number;
    total_messages: number;
    total_posts: number;
}

interface RecentUser {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    profile_picture: string | null;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    recentUsers: RecentUser[];
}>();

function formatNumber(num: number): string {
    return num.toLocaleString();
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
        <Head title="Admin Dashboard - NEMSU Match" />
        
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center">
                            <Shield class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Admin Dashboard</h1>
                            <p class="text-sm text-gray-600">NEMSU Match</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            href="/browse"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors"
                        >
                            Back to App
                        </Link>
                        <Link
                            href="/admin/logout"
                            method="post"
                            as="button"
                            class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm font-medium transition-colors"
                        >
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Welcome Message -->
            <div class="mb-8 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-3xl p-8 text-white shadow-xl">
                <h2 class="text-3xl font-bold mb-2">Welcome, Admin! 👋</h2>
                <p class="text-blue-100">Here's an overview of the NEMSU Match platform.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <Users class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_users) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Total Users</p>
                </div>

                <!-- Active Users Today -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <Users class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.active_users_today) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Active Today</p>
                </div>

                <!-- Total Matches -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center">
                            <Heart class="w-6 h-6 text-pink-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_matches) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Total Matches</p>
                </div>

                <!-- Total Messages -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
                            <MessageSquare class="w-6 h-6 text-cyan-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_messages) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Total Messages</p>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Recent Users -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Recent Users</h2>
                    <div class="space-y-3">
                        <div
                            v-for="user in recentUsers"
                            :key="user.id"
                            class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-xl transition-colors"
                        >
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
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ user.display_name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Quick Access</h2>
                    <div class="space-y-3">
                        <Link
                            href="/browse"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-cyan-50 hover:from-blue-100 hover:to-cyan-100 rounded-xl transition-colors group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <Users class="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">Browse Users</p>
                                    <p class="text-xs text-gray-600">View all platform users</p>
                                </div>
                            </div>
                            <ArrowRight class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
                        </Link>

                        <Link
                            href="/chat"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 rounded-xl transition-colors group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <MessageSquare class="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">Messages</p>
                                    <p class="text-xs text-gray-600">View conversations</p>
                                </div>
                            </div>
                            <ArrowRight class="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" />
                        </Link>

                        <Link
                            href="/feed"
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-teal-50 hover:from-green-100 hover:to-teal-100 rounded-xl transition-colors group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <FileText class="w-5 h-5 text-green-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">Social Feed</p>
                                    <p class="text-xs text-gray-600">View posts and activity</p>
                                </div>
                            </div>
                            <ArrowRight class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition-colors" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Info Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <Shield class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="font-bold text-blue-900 mb-2">Admin Access Level</h3>
                        <p class="text-sm text-blue-700 leading-relaxed">
                            You have admin-level access to the NEMSU Match platform. You can view users, monitor activity, and access the main application features. 
                            For advanced settings and user management, contact your superadmin.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
