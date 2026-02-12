<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Users, 
    UserCog, 
    ShieldAlert,
    UserX,
    Settings, 
    Menu, 
    X, 
    LogOut,
    ChevronLeft 
} from 'lucide-vue-next';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user as any);
const showMobileMenu = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/superadmin', icon: LayoutDashboard },
    { name: 'Users', href: '/superadmin/users', icon: Users },
    { name: 'Reported Users', href: '/superadmin/reported-users', icon: ShieldAlert },
    { name: 'Disabled Accounts', href: '/superadmin/disabled-users', icon: UserX },
    { name: 'Admins & Editors', href: '/superadmin/admins', icon: UserCog },
    { name: 'Settings', href: '/superadmin/settings', icon: Settings },
];

const isActive = (href: string): boolean => {
    const currentPath = window.location.pathname;
    if (href === '/superadmin') {
        return currentPath === href;
    }
    return currentPath.startsWith(href);
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar (Desktop) -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-white border-r border-gray-200">
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">
                    NEMSU Match
                </h1>
                <span class="ml-2 text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full font-semibold">
                    SUPERADMIN
                </span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-700 hover:bg-gray-100'
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5" />
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-sm">
                            {{ currentUser?.display_name?.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ currentUser?.display_name }}</p>
                        <p class="text-xs text-gray-500 truncate">Superadmin</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        href="/browse"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        Back to App
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex items-center justify-center gap-2 px-3 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm font-medium transition-colors"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile Header -->
        <div class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 z-40">
            <h1 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">
                NEMSU Match
            </h1>
            <button
                type="button"
                @click="showMobileMenu = !showMobileMenu"
                class="p-2 hover:bg-gray-100 rounded-lg"
            >
                <Menu v-if="!showMobileMenu" class="w-6 h-6 text-gray-700" />
                <X v-else class="w-6 h-6 text-gray-700" />
            </button>
        </div>

        <!-- Mobile Menu -->
        <Transition name="slide">
            <div
                v-if="showMobileMenu"
                class="lg:hidden fixed inset-0 z-30 bg-white pt-16"
            >
                <nav class="px-3 py-4 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        @click="showMobileMenu = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-blue-50 text-blue-600'
                                : 'text-gray-700 hover:bg-gray-100'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </nav>
                <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
                    <Link
                        href="/browse"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors mb-2"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        Back to App
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm font-medium transition-colors"
                    >
                        <LogOut class="w-4 h-4" />
                        Logout
                    </Link>
                </div>
            </div>
        </Transition>

        <!-- Main Content -->
        <main class="flex-1 lg:mt-0 mt-16">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
}
</style>
