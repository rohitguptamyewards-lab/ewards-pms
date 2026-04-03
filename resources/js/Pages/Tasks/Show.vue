<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import CommentSection from '@/Components/CommentSection.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    task: { type: Object, required: true },
    workLogs: { type: Array, default: () => [] },
    comments: { type: Array, default: () => [] },
});

const currentStatus = ref(props.task.status);
const updatingStatus = ref(false);

const transitions = {
    open: ['in_progress'],
    in_progress: ['blocked', 'done'],
    blocked: ['in_progress'],
    done: [],
};

const transitionLabels = {
    in_progress: 'Start Progress',
    blocked: 'Mark Blocked',
    done: 'Mark Done',
};

const transitionColors = {
    in_progress: 'rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors',
    blocked: 'rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors',
    done: 'rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors',
};

const allowedTransitions = () => transitions[currentStatus.value] || [];

async function changeStatus(newStatus) {
    updatingStatus.value = true;
    try {
        await axios.put(`/api/v1/tasks/${props.task.id}/status`, {
            status: newStatus,
        });
        currentStatus.value = newStatus;
    } catch {
        // status unchanged on error
    } finally {
        updatingStatus.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head :title="task.title" />

    <div class="mx-auto max-w-3xl">
        <!-- Header -->
        <div class="mb-6">
            <Link
                :href="`/projects/${task.project?.id || task.project_id}`"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                &larr; Back to Project
            </Link>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ task.title }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <StatusBadge :status="currentStatus" type="task" />
                <PriorityBadge :priority="task.priority" />
            </div>
        </div>

        <!-- Details Card -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Description</h3>
                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ task.description || 'No description provided.' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Assigned To</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ task.assignedTo?.name || task.assigned_to?.name || '-' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Project</h3>
                        <Link
                            v-if="task.project"
                            :href="`/projects/${task.project.id}`"
                            class="mt-1 block text-sm font-medium text-blue-600 hover:underline"
                        >
                            {{ task.project.name }}
                        </Link>
                        <p v-else class="mt-1 text-sm text-gray-900">-</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Deadline</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(task.deadline) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Estimated Hours</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ task.estimated_hours ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Status Transitions -->
            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="mb-3 text-sm font-medium text-gray-500">Update Status</h3>
                <div v-if="allowedTransitions().length" class="flex items-center gap-3">
                    <button
                        v-for="status in allowedTransitions()"
                        :key="status"
                        @click="changeStatus(status)"
                        :disabled="updatingStatus"
                        :class="transitionColors[status]"
                        class="disabled:opacity-50"
                    >
                        {{ updatingStatus ? 'Updating...' : transitionLabels[status] }}
                    </button>
                </div>
                <p v-else class="text-sm text-green-600 font-medium">Completed</p>
            </div>
        </div>

        <!-- Work Logs -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900">Work Logs</h3>

            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">Hours</th>
                                <th class="px-5 py-3">Note</th>
                                <th class="px-5 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in workLogs" :key="log.id">
                                <td class="px-5 py-3 text-gray-700">{{ formatDate(log.log_date) }}</td>
                                <td class="px-5 py-3 text-gray-900 font-medium">{{ log.hours_spent }}h</td>
                                <td class="px-5 py-3 text-gray-700">{{ log.note || '-' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ log.user?.name || '-' }}</td>
                            </tr>
                            <tr v-if="!workLogs.length">
                                <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No work logs recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Comments -->
        <CommentSection
            :comments="comments"
            commentable-type="task"
            :commentable-id="task.id"
        />
    </div>
</template>
