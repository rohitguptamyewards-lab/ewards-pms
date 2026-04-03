<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const role = computed(() => user.value?.role || '');

const currentUrl = computed(() => page.url);

function isActive(href) {
    return currentUrl.value.startsWith(href);
}

const navItems = computed(() => {
    const items = [
        { label: 'Dashboard', href: '/dashboard', roles: null },
        { label: 'Work Logs', href: '/work-logs', roles: null },
        { label: 'Projects', href: '/projects', roles: ['cto', 'ceo', 'developer', 'tester', 'analyst'] },
        { label: 'Tasks', href: '/tasks', roles: ['developer', 'tester', 'analyst'] },
        { label: 'Requests', href: '/requests', roles: ['cto', 'ceo', 'sales'] },
        { label: 'Features', href: '/features', roles: ['cto', 'ceo'] },
        { label: 'Reports', href: '/reports', roles: ['cto', 'ceo'] },
    ];
    return items.filter(
        (item) => item.roles === null || item.roles.includes(role.value)
    );
});

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-100">
        <!-- Sidebar -->
        <aside class="flex w-[250px] flex-shrink-0 flex-col bg-gray-800">
            <div class="flex h-16 items-center justify-center border-b border-gray-700">
                <span class="text-lg font-bold text-white tracking-wide">eWards PMS</span>
            </div>
            <nav class="mt-4 flex-1 space-y-1 px-3">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        isActive(item.href)
                            ? 'bg-gray-900 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                    ]"
                    class="block rounded-md px-3 py-2.5 text-sm font-medium transition-colors"
                >
                    {{ item.label }}
                </Link>
            </nav>
            <div class="border-t border-gray-700 px-3 py-4">
                <p class="truncate text-xs text-gray-400">Logged in as</p>
                <p class="truncate text-sm font-medium text-white">{{ user?.name }}</p>
                <p class="truncate text-xs text-gray-400 capitalize">{{ role }}</p>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex h-16 flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
                <h1 class="text-lg font-semibold text-gray-800">eWards PMS</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ user?.name }}</span>
                    <button
                        @click="logout"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
                    >
                        Logout
                    </button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
