<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page  = usePage();
const user  = computed(() => page.props.auth?.user);
const role  = computed(() => user.value?.role || '');
const url   = computed(() => page.url);

function isActive(href) {
    if (!href) return false;
    if (href === '/') return url.value === '/';
    return url.value.startsWith(href);
}

const MANAGER  = ['cto', 'ceo', 'manager'];
const MC_UP    = [...MANAGER, 'mc_team'];
const REPORTS  = MC_UP; // mc_team can view reports too
const ALL      = [...MC_UP, 'developer', 'tester', 'analyst', 'sales'];

const navItems = computed(() => {
    const items = [
        {
            label: 'Dashboard', href: '/', roles: null,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
        },
        {
            label: 'Work Logs', href: '/work-logs', roles: null,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
        },
        {
            label: 'Projects', href: '/projects', roles: ALL,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />`,
        },
        {
            label: 'Tasks', href: '/tasks', roles: [...MC_UP, 'developer', 'tester', 'analyst'],
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />`,
        },
        {
            label: 'Requests', href: '/requests', roles: [...MC_UP, 'sales'],
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />`,
        },
        {
            label: 'Features', href: '/features', roles: MC_UP,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />`,
        },
        {
            label: 'Team', href: '/team-members', roles: ALL,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
        },
        {
            label: 'My Report', href: '/reports/individual', roles: ['developer', 'tester', 'analyst'],
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />`,
        },
        { label: 'REPORTS', href: null, roles: REPORTS, header: true },
        {
            label: 'Work Log Report', href: '/reports/work-logs', roles: REPORTS, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`,
        },
        {
            label: 'Project Report', href: '/reports/projects', roles: REPORTS, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`,
        },
        {
            label: 'Individual Report', href: '/reports/individual', roles: REPORTS, indent: true,
            icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />`,
        },
    ];
    return items.filter(item => item.roles === null || item.roles.includes(role.value));
});

const avatarInitial = computed(() => (user.value?.name || '?').charAt(0).toUpperCase());
const roleLabel = computed(() => (role.value || '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50">
        <!-- Sidebar -->
        <aside class="flex w-64 flex-shrink-0 flex-col bg-[#1e0a45]">
            <!-- Logo -->
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#5e16bd]">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <span class="text-base font-bold text-white tracking-tight">eWards PMS</span>
            </div>

            <!-- Nav -->
            <nav class="mt-3 flex-1 overflow-y-auto px-3 pb-3">
                <template v-for="item in navItems" :key="item.href || item.label">
                    <!-- Section header -->
                    <p v-if="item.header" class="mb-1 mt-5 px-3 text-[10px] font-bold uppercase tracking-widest text-white/30">
                        {{ item.label }}
                    </p>
                    <!-- Nav link -->
                    <Link
                        v-else
                        :href="item.href"
                        :class="[
                            isActive(item.href)
                                ? 'bg-[#5e16bd] text-white shadow-sm'
                                : 'text-white/60 hover:bg-[#2d1569] hover:text-white',
                            item.indent ? 'pl-7 text-xs py-2' : 'text-sm py-2.5',
                        ]"
                        class="flex items-center gap-3 rounded-lg px-3 font-medium transition-all duration-150"
                    >
                        <svg
                            v-if="item.icon"
                            class="h-4 w-4 flex-shrink-0"
                            :class="item.indent ? 'h-3.5 w-3.5' : ''"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                            v-html="item.icon"
                        />
                        {{ item.label }}
                    </Link>
                </template>
            </nav>

            <!-- User footer -->
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#5e16bd] text-sm font-bold text-white">
                        {{ avatarInitial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ roleLabel }}</p>
                    </div>
                    <button @click="logout" class="shrink-0 rounded-md p-1.5 text-gray-400 hover:bg-white/10 hover:text-white transition-colors" title="Sign out">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="font-semibold text-gray-800">eWards PMS</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#ece1ff] text-sm font-bold text-[#5e16bd]">
                        {{ avatarInitial }}
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium text-gray-800">{{ user?.name }}</p>
                        <p class="text-xs text-gray-400">{{ roleLabel }}</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

