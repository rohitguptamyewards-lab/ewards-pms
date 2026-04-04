<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';

defineOptions({ layout: AppLayout });

defineProps({
    activeProjects:     { type: Number, default: 0 },
    openTasks:          { type: Number, default: 0 },
    blockedTasks:       { type: Array,  default: () => [] },
    overdueTasks:       { type: Array,  default: () => [] },
    teamWorkload:       { type: Array,  default: () => [] },
    untriagedRequests:  { type: Number, default: 0 },
});

function daysSince(dateStr) {
    if (!dateStr) return 0;
    return Math.floor((Date.now() - new Date(dateStr).getTime()) / (1000 * 60 * 60 * 24));
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Manager Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manager Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Team overview and key metrics</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    v-if="untriagedRequests > 0"
                    href="/requests"
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-orange-700 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Triage Requests ({{ untriagedRequests }})
                </Link>
                <Link href="/reports/work-logs" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Reports
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <StatsCard label="Active Projects"     :value="activeProjects"         color="blue"   />
            <StatsCard label="Open Tasks"          :value="openTasks"              color="indigo" />
            <StatsCard label="Blocked Tasks"       :value="blockedTasks.length"    color="red"    />
            <StatsCard label="Overdue Tasks"       :value="overdueTasks.length"    color="orange" />
            <StatsCard label="Pending Triage"      :value="untriagedRequests"      color="yellow" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Blocked Tasks -->
            <div class="rounded-xl border border-red-100 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 border-b border-red-100 bg-red-50 px-5 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <h2 class="font-semibold text-red-800">Blocked Tasks</h2>
                    <span class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">{{ blockedTasks.length }}</span>
                </div>
                <div v-if="blockedTasks.length" class="divide-y divide-gray-50">
                    <div v-for="task in blockedTasks" :key="task.id" class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <Link :href="`/tasks/${task.id}`" class="font-medium text-[#4e1a77] hover:underline truncate block">
                                    {{ task.title }}
                                </Link>
                                <p class="mt-0.5 text-xs text-gray-500">{{ task.assignee_name || task.assignee?.name || '—' }}</p>
                                <p v-if="task.blocker_reason" class="mt-1 text-xs text-red-600 truncate">{{ task.blocker_reason }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                                {{ daysSince(task.blocked_at || task.updated_at) }}d
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">No blocked tasks.</p>
                </div>
            </div>

            <!-- Overdue Tasks -->
            <div class="rounded-xl border border-orange-100 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 border-b border-orange-100 bg-orange-50 px-5 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100">
                        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h2 class="font-semibold text-orange-800">Overdue Tasks</h2>
                    <span class="ml-auto rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700">{{ overdueTasks.length }}</span>
                </div>
                <div v-if="overdueTasks.length" class="divide-y divide-gray-50">
                    <div v-for="task in overdueTasks" :key="task.id" class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <Link :href="`/tasks/${task.id}`" class="font-medium text-[#4e1a77] hover:underline truncate block">
                                    {{ task.title }}
                                </Link>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ task.project_name || task.project?.name || '—' }}
                                    <span class="mx-1">·</span>
                                    {{ task.assignee_name || task.assignee?.name || '—' }}
                                </p>
                                <p class="mt-0.5 text-xs text-orange-600">Due {{ formatDate(task.deadline) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700">
                                {{ daysSince(task.deadline) }}d late
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">No overdue tasks.</p>
                </div>
            </div>
        </div>

        <!-- Team Workload Today -->
        <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="font-semibold text-gray-900">Team Workload Today</h2>
            </div>
            <div v-if="teamWorkload.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">User</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5 text-right">Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(entry, idx) in teamWorkload" :key="idx" class="hover:bg-[#f5f0ff]/20 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#e8ddf0] text-xs font-bold text-[#4e1a77]">
                                        {{ (entry.user?.name || entry.user_name || '?').charAt(0).toUpperCase() }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ entry.user?.name || entry.user_name || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ entry.project?.name || entry.project_name || '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ entry.task?.title || entry.task_title || '—' }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ entry.hours_spent }}h</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="px-5 py-12 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-gray-400">No work logged today.</p>
            </div>
        </div>
    </div>
</template>
