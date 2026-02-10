<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Users, MessageSquare, Heart, Activity, TrendingUp, UserCheck } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';

interface Stats {
    total_users: number;
    active_users_today: number;
    new_users_this_week: number;
    verified_users: number;
    total_matches: number;
    total_conversations: number;
    total_messages: number;
    total_swipes: number;
    total_posts: number;
}

interface RecentUser {
    id: number;
    display_name: string;
    fullname: string;
    email: string;
    profile_picture: string | null;
    created_at: string;
    last_seen_at: string | null;
}

interface UserGrowth {
    date: string;
    count: number;
}

interface GenderDistribution {
    male: number;
    female: number;
    other: number;
}

const props = defineProps<{
    stats: Stats;
    recentUsers: RecentUser[];
    userGrowth: UserGrowth[];
    genderDistribution: GenderDistribution;
}>();

function formatNumber(num: number): string {
    return num.toLocaleString();
}

function timeAgo(dateStr: string): string {
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
</script>

<template>
    <Head title="Superadmin Dashboard - NEMSU Match" />
    
    <SuperadminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600 mt-1">Overview of NEMSU Match platform</p>
            </div>

            <!-- Stats Cards -->
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
                    <p class="text-xs text-green-600 mt-2">+{{ formatNumber(stats.new_users_this_week) }} this week</p>
                </div>

                <!-- Active Users Today -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <Activity class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.active_users_today) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Active Today</p>
                    <p class="text-xs text-gray-500 mt-2">{{ ((stats.active_users_today / stats.total_users) * 100).toFixed(1) }}% of total</p>
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
                    <p class="text-xs text-gray-500 mt-2">{{ formatNumber(stats.total_swipes) }} swipes</p>
                </div>

                <!-- Conversations -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
                            <MessageSquare class="w-6 h-6 text-cyan-600" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.total_conversations) }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Conversations</p>
                    <p class="text-xs text-gray-500 mt-2">{{ formatNumber(stats.total_messages) }} messages</p>
                </div>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Growth Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">User Growth (Last 30 Days)</h2>
                    <div class="h-64 flex items-end gap-1">
                        <div
                            v-for="day in userGrowth"
                            :key="day.date"
                            class="flex-1 bg-blue-200 rounded-t hover:bg-blue-300 transition-colors cursor-pointer relative group"
                            :style="{ height: `${(day.count / Math.max(...userGrowth.map(d => d.count)) * 100) || 5}%` }"
                            :title="`${day.date}: ${day.count} users`"
                        >
                            <span class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                {{ new Date(day.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}: {{ day.count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Gender Distribution -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Gender Distribution</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Male</span>
                                <span class="font-semibold">{{ formatNumber(genderDistribution.male) }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-blue-600 rounded-full"
                                    :style="{ width: `${(genderDistribution.male / stats.total_users * 100)}%` }"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Female</span>
                                <span class="font-semibold">{{ formatNumber(genderDistribution.female) }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-pink-600 rounded-full"
                                    :style="{ width: `${(genderDistribution.female / stats.total_users * 100)}%` }"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Other</span>
                                <span class="font-semibold">{{ formatNumber(genderDistribution.other) }}</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-purple-600 rounded-full"
                                    :style="{ width: `${(genderDistribution.other / stats.total_users * 100)}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="mt-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Recent Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">User</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Email</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Joined</th>
                                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr 
                                v-for="user in recentUsers" 
                                :key="user.id"
                                class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                            >
                                <td class="py-3 px-4">
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
                                            <p class="text-xs text-gray-500">{{ user.fullname }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">{{ user.email }}</td>
                                <td class="py-3 px-4 text-sm text-gray-600">{{ timeAgo(user.created_at) }}</td>
                                <td class="py-3 px-4 text-sm text-gray-600">
                                    {{ user.last_seen_at ? timeAgo(user.last_seen_at) : 'Never' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>
