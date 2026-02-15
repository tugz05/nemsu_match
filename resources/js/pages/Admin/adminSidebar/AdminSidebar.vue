<script setup>
import { Link } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Users, 
    MessageSquare, 
    FileWarning, 
    ShieldCheck, 
    Newspaper, 
    LogOut,
    ChevronRight,
    // NEW IMPORTS
    Megaphone,   // Announcements
    LifeBuoy,    // User Feedback
    UserX        // Banned Users
} from 'lucide-vue-next';

// Props to determine active state
defineProps({
    currentRoute: String,
});

// Menu Configuration
const menuItems = [
    // --- MONITORING ---
    { 
        name: 'Dashboard', 
        path: '/admin/dashboard', 
        activeId: 'admin.dashboard',
        icon: LayoutDashboard 
    },
    { 
        name: 'Social Feed', 
        path: '/feed', 
        activeId: 'feed',
        icon: Newspaper 
    },

    // --- USER MANAGEMENT ---
    { 
        name: 'Browse Users', 
        path: '/browse', 
        activeId: 'browse',
        icon: Users 
    },
    { 
        name: 'Verifications', 
        path: '/admin/verifications', 
        activeId: 'admin.verifications',
        icon: ShieldCheck 
    },
    { 
        name: 'Banned Users',   // NEW: Enforcement
        path: '/admin/banned', 
        activeId: 'admin.banned',
        icon: UserX 
    },

    // --- COMMUNICATION & SUPPORT ---
    { 
        name: 'Messages', 
        path: '/chat', 
        activeId: 'chat',
        icon: MessageSquare 
    },
    { 
        name: 'Message Reports', 
        path: '/admin/message-report', 
        activeId: 'admin.message-report',
        icon: FileWarning 
    },
    { 
        name: 'User Feedback',  // NEW: Support
        path: '/admin/feedback', 
        activeId: 'admin.feedback',
        icon: LifeBuoy 
    },
    { 
        name: 'Announcements',  // NEW: Updates
        path: '/admin/announcements', 
        activeId: 'admin.announcements',
        icon: Megaphone 
    },
];
</script>

<template>
    <aside class="w-72 bg-white border-r border-gray-100 flex flex-col shadow-2xl z-20 hidden md:flex relative overflow-hidden group/sidebar">
        
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-blue-50/50 to-transparent pointer-events-none"></div>

        <div class="h-24 flex items-center px-8 z-10">
            <Link href="/admin/dashboard" class="flex items-center gap-3 group">
                <div class="relative">
                    <div class="absolute inset-0 bg-blue-500 rounded-xl blur opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg relative transform group-hover:scale-105 transition-transform duration-300">
                        <ShieldCheck class="w-6 h-6 text-white" />
                    </div>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-600 tracking-tight">
                        NEMSU
                    </h1>
                    <span class="text-xs font-semibold text-gray-400 tracking-widest uppercase">Admin Panel</span>
                </div>
            </Link>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 z-10 overflow-y-auto custom-scrollbar">
            <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">
                Main Menu
            </p>
            
            <template v-for="item in menuItems" :key="item.name">
                <Link 
                    :href="item.path" 
                    class="relative flex items-center gap-3 px-4 py-3.5 text-sm font-medium rounded-xl transition-all duration-300 group overflow-hidden"
                    :class="[
                        currentRoute === item.activeId 
                        ? 'text-white shadow-lg shadow-blue-500/30 translate-x-0' 
                        : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700 hover:translate-x-1'
                    ]"
                >
                    <div 
                        v-if="currentRoute === item.activeId" 
                        class="absolute inset-0 bg-gradient-to-r from-blue-600 to-cyan-500"
                    ></div>

                    <component 
                        :is="item.icon" 
                        class="w-5 h-5 relative z-10 transition-transform duration-300 group-hover:scale-110"
                        :class="currentRoute === item.activeId ? 'text-white' : 'text-gray-400 group-hover:text-blue-600'"
                    />

                    <span class="relative z-10 font-semibold tracking-wide">
                        {{ item.name }}
                    </span>

                    <ChevronRight 
                        v-if="currentRoute === item.activeId" 
                        class="w-4 h-4 ml-auto relative z-10 text-white opacity-80" 
                    />
                </Link>
            </template>
        </nav>

        <div class="p-4 border-t border-gray-100 z-10">
            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-100 to-cyan-100 flex items-center justify-center text-blue-700 font-bold text-xs">
                        AD
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-gray-900 truncate">Administrator</p>
                        <p class="text-[10px] text-gray-500 truncate">admin@nemsu.edu.ph</p>
                    </div>
                </div>
                
                <Link 
                    href="/admin/logout" 
                    method="post" 
                    as="button" 
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-red-600 bg-white border border-red-100 rounded-lg hover:bg-red-50 hover:border-red-200 transition-all duration-200 shadow-sm"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>Sign Out</span>
                </Link>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
}
</style>