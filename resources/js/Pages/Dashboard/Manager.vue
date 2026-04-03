<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';

defineOptions({ layout: AppLayout });

defineProps({
    activeProjects: { type: Number, default: 0 },
    openTasks: { type: Number, default: 0 },
    blockedTasks: { type: Array, default: () => [] },
    overdueTasks: { type: Array, default: () => [] },
    teamWorkload: { type: Array, default: () => [] },
    untriagedRequests: { type: Number, default: 0 },
});

function daysSince(dateStr) {
    if (!dateStr) return 0;
    const diff = Date.now() - new Date(dateStr).getTime();
    return Math.floor(diff / (1000 * 60 * 60 * 24));
}
</script>

<template>
    <Head title="Manager Dashboard" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Manager Dashboard</h1>
            <Link
                v-if="untriagedRequests > 0"
                href="/requests"
                class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 transition-colors"
            >
                Triage Requests ({{ untriagedRequests }})
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <StatsCard label="Active Projects" :value="activeProjects" color="blue" />
            <StatsCard label="Open Tasks" :value="openTasks" color="indigo" />
            <StatsCard label="Blocked Tasks" :value="blockedTasks.length" color="red" />
            <StatsCard label="Overdue Tasks" :value="overdueTasks.length" color="orange" />
            <StatsCard label="Untriaged Requests" :value="untriagedRequests" color="yellow" />
        </div>

        <!-- Blocked Tasks -->
        <div v-if="blockedTasks.length" class="mb-6 rounded-lg border border-red-200 bg-white">
            <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                <h2 class="text-base font-semibold text-red-800">Blocked Tasks</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Assignee</th>
                            <th class="px-5 py-3">Blocker Reason</th>
                            <th class="px-5 py-3">Days Blocked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="task in blockedTasks" :key="task.id">
                            <td class="px-5 py-3">
                                <Link :href="`/tasks/${task.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ task.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ task.assignee_name || task.assignee?.name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-600 max-w-[300px] truncate">{{ task.blocker_reason || '-' }}</td>
                            <td class="px-5 py-3 font-semibold text-red-600">{{ daysSince(task.blocked_at || task.updated_at) }}d</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Overdue Tasks -->
        <div v-if="overdueTasks.length" class="mb-6 rounded-lg border border-orange-200 bg-white">
            <div class="border-b border-orange-200 bg-orange-50 px-5 py-4">
                <h2 class="text-base font-semibold text-orange-800">Overdue Tasks</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Project</th>
                            <th class="px-5 py-3">Assignee</th>
                            <th class="px-5 py-3">Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="task in overdueTasks" :key="task.id">
                            <td class="px-5 py-3">
                                <Link :href="`/tasks/${task.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ task.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ task.project_name || task.project?.name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ task.assignee_name || task.assignee?.name || '-' }}</td>
                            <td class="px-5 py-3 font-semibold text-orange-600">{{ daysSince(task.deadline) }}d</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Team Workload Today -->
        <div class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Team Workload Today</h2>
            </div>
            <div class="overflow-x-auto">
                <table v-if="teamWorkload.length" class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Project</th>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Hours Today</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(entry, idx) in teamWorkload" :key="idx">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ entry.user?.name || entry.user_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ entry.project?.name || entry.project_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ entry.task?.title || entry.task_title || '-' }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ entry.hours }}h</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No work logged today.</p>
            </div>
        </div>
    </div>
</template>
