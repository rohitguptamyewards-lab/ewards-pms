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
    task:        { type: Object, required: true },
    workLogs:    { type: Array, default: () => [] },
    comments:    { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
});

const currentStatus   = ref(props.task.status);
const currentAssignee = ref(props.task.assignee_name || '—');
const updatingStatus  = ref(false);
const statusError     = ref('');

// Reassign
const showReassign    = ref(false);
const newAssigneeId   = ref(props.task.assigned_to || '');
const reassigning     = ref(false);
const reassignError   = ref('');

const transitions = {
    open:        ['in_progress'],
    in_progress: ['blocked', 'done'],
    blocked:     ['in_progress'],
    done:        [],
};

const transitionConfig = {
    in_progress: { label: 'Start Progress', classes: 'bg-[#4e1a77] hover:bg-[#3d1560] text-white' },
    blocked:     { label: 'Mark Blocked',   classes: 'bg-red-600 hover:bg-red-700 text-white' },
    done:        { label: 'Mark Done',      classes: 'bg-emerald-600 hover:bg-emerald-700 text-white' },
};

const allowedTransitions = () => transitions[currentStatus.value] || [];

async function changeStatus(newStatus) {
    updatingStatus.value = true;
    statusError.value = '';
    try {
        await axios.put(`/api/v1/tasks/${props.task.id}/status`, { status: newStatus });
        currentStatus.value = newStatus;
    } catch (e) {
        statusError.value = e.response?.data?.errors?.status?.[0] || e.response?.data?.message || 'Failed to update status.';
    } finally {
        updatingStatus.value = false;
    }
}

async function saveReassign() {
    reassigning.value = true;
    reassignError.value = '';
    try {
        await axios.put(`/api/v1/tasks/${props.task.id}`, { assigned_to: newAssigneeId.value || null });
        const member = props.teamMembers.find(m => m.id === Number(newAssigneeId.value));
        currentAssignee.value = member?.name || '—';
        showReassign.value = false;
    } catch (e) {
        reassignError.value = 'Failed to reassign task.';
    } finally {
        reassigning.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function isOverdue(deadline) {
    return deadline && new Date(deadline) < new Date() && currentStatus.value !== 'done';
}
</script>

<template>
    <Head :title="task.title" />

    <div class="mx-auto max-w-3xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/tasks" class="hover:text-gray-700">Tasks</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <Link
                v-if="task.project_id"
                :href="`/projects/${task.project_id}`"
                class="hover:text-gray-700"
            >{{ task.project_name || 'Project' }}</Link>
            <svg v-if="task.project_id" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900 truncate max-w-xs">{{ task.title }}</span>
        </div>

        <!-- Header card -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ task.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :status="currentStatus" type="task" />
                        <PriorityBadge :priority="task.priority" />
                        <span
                            v-if="isOverdue(task.deadline)"
                            class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            Overdue
                        </span>
                    </div>
                </div>
            </div>

            <!-- Meta grid -->
            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assigned To</p>
                    <div class="mt-1 flex items-center gap-1.5">
                        <p class="text-sm font-medium text-gray-800">{{ currentAssignee }}</p>
                        <button
                            v-if="teamMembers.length"
                            @click="showReassign = !showReassign"
                            class="rounded p-0.5 text-gray-400 hover:text-[#4e1a77] hover:bg-[#f5f0ff] transition-colors"
                            title="Change assignee"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </button>
                    </div>
                    <!-- Inline reassign form -->
                    <div v-if="showReassign" class="mt-2 flex items-center gap-2">
                        <select
                            v-model="newAssigneeId"
                            class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                        >
                            <option value="">Unassigned</option>
                            <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                        <button
                            @click="saveReassign"
                            :disabled="reassigning"
                            class="rounded-md bg-[#4e1a77] px-2.5 py-1 text-xs font-medium text-white hover:bg-[#3d1560] disabled:opacity-50 transition-colors"
                        >{{ reassigning ? '...' : 'Save' }}</button>
                        <button @click="showReassign = false" class="rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            ✕
                        </button>
                    </div>
                    <p v-if="reassignError" class="mt-1 text-xs text-red-600">{{ reassignError }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Project</p>
                    <Link
                        v-if="task.project_id"
                        :href="`/projects/${task.project_id}`"
                        class="mt-1 block text-sm font-medium text-[#4e1a77] hover:underline"
                    >{{ task.project_name || '—' }}</Link>
                    <p v-else class="mt-1 text-sm text-gray-500">—</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Deadline</p>
                    <p class="mt-1 text-sm font-medium" :class="isOverdue(task.deadline) ? 'text-red-600' : 'text-gray-800'">
                        {{ formatDate(task.deadline) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Est. Hours</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ task.estimated_hours ? task.estimated_hours + 'h' : '—' }}</p>
                </div>
            </div>

            <!-- Description -->
            <div v-if="task.description" class="mt-4 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Description</p>
                <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ task.description }}</p>
            </div>

            <!-- Status transitions -->
            <div class="mt-5 border-t border-gray-100 pt-5">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Update Status</p>
                <div v-if="allowedTransitions().length" class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="status in allowedTransitions()"
                        :key="status"
                        @click="changeStatus(status)"
                        :disabled="updatingStatus"
                        :class="transitionConfig[status].classes"
                        class="rounded-lg px-4 py-2 text-sm font-medium shadow-sm disabled:opacity-50 transition-colors"
                    >
                        <span v-if="updatingStatus" class="flex items-center gap-2">
                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Updating...
                        </span>
                        <span v-else>{{ transitionConfig[status].label }}</span>
                    </button>
                    <p v-if="statusError" class="text-sm text-red-600">{{ statusError }}</p>
                </div>
                <div v-else class="flex items-center gap-2 text-sm text-emerald-600 font-medium">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Task Completed
                </div>
            </div>
        </div>

        <!-- Work Logs -->
        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Work Logs</h2>
                <span class="text-sm text-gray-400">
                    {{ workLogs.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1) }}h total
                </span>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Hours</th>
                            <th class="px-5 py-3">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="log in workLogs" :key="log.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ log.user_name || '—' }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ log.note || '—' }}</td>
                        </tr>
                        <tr v-if="!workLogs.length">
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No work logs recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Comments -->
        <div class="mt-6">
            <CommentSection :comments="comments" commentable-type="task" :commentable-id="task.id" />
        </div>
    </div>
</template>
