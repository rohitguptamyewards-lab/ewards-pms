<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatsCard from '@/Components/StatsCard.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    project: { type: Object, required: true },
    stats: { type: Object, default: () => ({ progress: 0, total_effort: 0, task_counts: { open: 0, in_progress: 0, blocked: 0, done: 0 } }) },
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head :title="project.name" />

    <div class="mx-auto max-w-4xl">
        <!-- Header -->
        <div class="mb-6">
            <Link href="/projects" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Projects</Link>
            <div class="mt-2 flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ project.name }}</h1>
                <StatusBadge :status="project.status" type="project" />
            </div>
            <p v-if="project.deadline" class="mt-1 text-sm text-gray-500">
                Deadline: {{ formatDate(project.deadline) }}
            </p>
        </div>

        <!-- Stats Row -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatsCard label="Open" :value="stats.task_counts?.open ?? 0" color="gray" />
            <StatsCard label="In Progress" :value="stats.task_counts?.in_progress ?? 0" color="blue" />
            <StatsCard label="Blocked" :value="stats.task_counts?.blocked ?? 0" color="red" />
            <StatsCard label="Done" :value="stats.task_counts?.done ?? 0" color="green" />
        </div>

        <!-- Progress -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="mb-2 text-sm font-medium text-gray-500">Overall Progress</h3>
            <ProgressBar :percentage="stats.progress || 0" />
        </div>

        <!-- Description -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-sm font-medium text-gray-500">Description</h3>
            <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ project.description || 'No description provided.' }}</p>
        </div>

        <!-- Members -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-3 text-sm font-medium text-gray-500">Members</h3>
            <div class="flex flex-wrap gap-2">
                <!-- Owner -->
                <span
                    v-if="project.owner"
                    class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-3 py-1 text-sm font-medium text-blue-700"
                >
                    {{ project.owner.name }}
                    <span class="text-xs text-blue-500">(Owner)</span>
                </span>
                <!-- Team Members -->
                <span
                    v-for="member in project.members"
                    :key="member.id"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 px-3 py-1 text-sm font-medium text-gray-700"
                >
                    {{ member.name }}
                    <span class="text-xs text-gray-400">({{ member.role }})</span>
                </span>
                <p v-if="!project.owner && !project.members?.length" class="text-sm text-gray-400">No members assigned.</p>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="flex items-center justify-between px-5 py-4">
                <h3 class="text-sm font-medium text-gray-500">Tasks</h3>
                <span class="text-sm text-gray-400">Total Effort: {{ stats.total_effort ?? 0 }}h</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-5 py-3">Assignee</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="task in project.tasks"
                            :key="task.id"
                            class="cursor-pointer hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3">
                                <Link :href="`/tasks/${task.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ task.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ task.assignee?.name || task.assignee_name || '-' }}</td>
                            <td class="px-5 py-3">
                                <StatusBadge :status="task.status" type="task" />
                            </td>
                            <td class="px-5 py-3">
                                <PriorityBadge :priority="task.priority" />
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(task.deadline) }}</td>
                        </tr>
                        <tr v-if="!project.tasks?.length">
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No tasks found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
