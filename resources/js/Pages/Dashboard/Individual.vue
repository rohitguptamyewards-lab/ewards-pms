<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

defineProps({
    todaysLogs: { type: Array, default: () => [] },
    myTasks: { type: Array, default: () => [] },
    myProjects: { type: Array, default: () => [] },
    weeklyHours: { type: Number, default: 0 },
});

function isOverdue(deadline) {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <Link
                href="/work-logs/create"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
            >
                Log Work
            </Link>
        </div>

        <!-- Stats -->
        <div class="mb-6">
            <StatsCard label="7-Day Hours" :value="weeklyHours.toFixed(1) + 'h'" color="blue" />
        </div>

        <!-- Grid: Logs + Tasks -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Today's Logs -->
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Today's Logs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="todaysLogs.length" class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Project</th>
                                <th class="px-5 py-3">Task</th>
                                <th class="px-5 py-3">Hours</th>
                                <th class="px-5 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in todaysLogs" :key="log.id">
                                <td class="px-5 py-3 text-gray-700">{{ log.project_name || log.project?.name || '-' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ log.task_title || log.task?.title || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ log.hours_spent || log.hours }}h</td>
                                <td class="px-5 py-3 text-gray-500 truncate max-w-[200px]">{{ log.note || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No logs for today.</p>
                </div>
            </div>

            <!-- My Open Tasks -->
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">My Open Tasks</h2>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="myTasks.length" class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Title</th>
                                <th class="px-5 py-3">Project</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Priority</th>
                                <th class="px-5 py-3">Deadline</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="task in myTasks" :key="task.id">
                                <td class="px-5 py-3">
                                    <Link :href="`/tasks/${task.id}`" class="font-medium text-blue-600 hover:underline">
                                        {{ task.title }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ task.project_name || task.project?.name || '-' }}</td>
                                <td class="px-5 py-3">
                                    <StatusBadge :status="task.status" type="task" />
                                </td>
                                <td class="px-5 py-3">
                                    <PriorityBadge :priority="task.priority" />
                                </td>
                                <td class="px-5 py-3" :class="isOverdue(task.deadline) ? 'font-semibold text-red-600' : 'text-gray-600'">
                                    {{ formatDate(task.deadline) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No open tasks.</p>
                </div>
            </div>
        </div>

        <!-- My Projects -->
        <div class="mt-6 rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">My Projects</h2>
            </div>
            <div v-if="myProjects.length" class="divide-y divide-gray-100">
                <div v-for="project in myProjects" :key="project.id" class="flex items-center gap-4 px-5 py-4">
                    <Link :href="`/projects/${project.id}`" class="min-w-[180px] font-medium text-blue-600 hover:underline">
                        {{ project.name }}
                    </Link>
                    <span class="text-sm text-gray-500">{{ project.my_hours || 0 }}h logged</span>
                    <div class="flex-1">
                        <ProgressBar :percentage="project.progress || 0" />
                    </div>
                </div>
            </div>
            <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No projects assigned.</p>
        </div>
    </div>
</template>
