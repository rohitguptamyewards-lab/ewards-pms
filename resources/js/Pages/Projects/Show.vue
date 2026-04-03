<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatsCard from '@/Components/StatsCard.vue';
import CommentSection from '@/Components/CommentSection.vue';
import DocumentUpload from '@/Components/DocumentUpload.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    project:     { type: Object, required: true },
    stats:       { type: Object, default: () => ({ progress: 0, total_effort: 0, task_counts: { open: 0, in_progress: 0, blocked: 0, done: 0 } }) },
    workLogs:    { type: Array, default: () => [] },
    documents:   { type: Array, default: () => [] },
    comments:    { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
});

const activeTab = ref('overview');
const tabs = [
    { key: 'overview',  label: 'Overview' },
    { key: 'tasks',     label: 'Tasks' },
    { key: 'work-logs', label: 'Work Logs' },
    { key: 'documents', label: 'Documents' },
    { key: 'comments',  label: 'Comments' },
];

// ── Add Task modal ─────────────────────────────────────────────────────────────
const showAddTask  = ref(false);
const taskForm     = ref({ title: '', assigned_to: '', priority: 'p2', deadline: '', description: '' });
const taskErrors   = ref({});
const taskSaving   = ref(false);
const localTasks   = ref([...props.project.tasks || []]);

async function saveTask() {
    taskErrors.value = {};
    if (!taskForm.value.title.trim()) { taskErrors.value.title = 'Title is required.'; return; }
    taskSaving.value = true;
    try {
        const resp = await axios.post(`/api/v1/projects/${props.project.id}/tasks`, taskForm.value);
        localTasks.value.unshift(resp.data);
        showAddTask.value = false;
        taskForm.value = { title: '', assigned_to: '', priority: 'p2', deadline: '', description: '' };
    } catch (e) {
        taskErrors.value = e.response?.data?.errors || { title: 'Failed to create task.' };
    } finally {
        taskSaving.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function ownerName(project) {
    return project.owner_name || project.owner?.name || '—';
}

const taskCounts = computed(() => ({
    open:        localTasks.value.filter(t => t.status === 'open').length,
    in_progress: localTasks.value.filter(t => t.status === 'in_progress').length,
    blocked:     localTasks.value.filter(t => t.status === 'blocked').length,
    done:        localTasks.value.filter(t => t.status === 'done').length,
}));
</script>

<template>
    <Head :title="project.name" />

    <div class="mx-auto max-w-4xl">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <Link href="/projects" class="hover:text-gray-700">Projects</Link>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-900 truncate max-w-xs">{{ project.name }}</span>
        </div>

        <!-- Header card -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ project.name }}</h1>
                        <StatusBadge :status="project.status" type="project" />
                        <PriorityBadge v-if="project.priority" :priority="project.priority" />
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ ownerName(project) }}
                        </span>
                        <span v-if="project.start_date || project.end_date" class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(project.start_date) }} – {{ formatDate(project.end_date || project.deadline) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="mt-4 border-t border-gray-100 pt-4">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-600">Progress</span>
                    <span class="font-semibold text-gray-800">{{ stats.total_effort ?? 0 }}h total effort</span>
                </div>
                <ProgressBar :percentage="stats.progress || 0" />
            </div>
        </div>

        <!-- Stats Row -->
        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatsCard label="Open"        :value="taskCounts.open"        color="gray"  />
            <StatsCard label="In Progress" :value="taskCounts.in_progress" color="blue"  />
            <StatsCard label="Blocked"     :value="taskCounts.blocked"     color="red"   />
            <StatsCard label="Done"        :value="taskCounts.done"        color="green" />
        </div>

        <!-- Tabs -->
        <div class="mt-6 border-b border-gray-200">
            <nav class="-mb-px flex gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="activeTab === tab.key
                        ? 'border-[#5e16bd] text-[#5e16bd] bg-[#f5f0ff]/50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap border-b-2 px-4 pb-3 pt-2 text-sm font-medium transition-colors rounded-t-lg"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Tab: Overview -->
        <div v-if="activeTab === 'overview'" class="mt-5 space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Description</p>
                <p class="mt-2 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ project.description || 'No description provided.' }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Owner</p>
                        <p class="mt-1 font-medium text-gray-900">{{ ownerName(project) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Start Date</p>
                        <p class="mt-1 font-medium text-gray-900">{{ formatDate(project.start_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">End Date</p>
                        <p class="mt-1 font-medium text-gray-900">{{ formatDate(project.end_date || project.deadline) }}</p>
                    </div>
                </div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Members</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-if="project.owner_name"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#f5f0ff] border border-[#ddd0f7] px-3 py-1 text-sm font-medium text-[#5e16bd]"
                    >
                        {{ project.owner_name }}
                        <span class="text-xs text-[#5e16bd]/60">(Owner)</span>
                    </span>
                    <span
                        v-for="member in project.members"
                        :key="member.id"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 px-3 py-1 text-sm font-medium text-gray-700"
                    >
                        {{ member.name }}
                        <span class="text-xs text-gray-400 capitalize">({{ member.role }})</span>
                    </span>
                    <p v-if="!project.owner_name && !project.members?.length" class="text-sm text-gray-400">No members assigned.</p>
                </div>
            </div>
        </div>

        <!-- Tab: Tasks -->
        <div v-if="activeTab === 'tasks'" class="mt-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Tasks ({{ localTasks.length }})</h3>
                <button
                    @click="showAddTask = true"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#5e16bd] px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Task
                </button>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="px-5 py-3.5">Title</th>
                                <th class="px-5 py-3.5">Assignee</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Priority</th>
                                <th class="px-5 py-3.5">Deadline</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="task in localTasks"
                                :key="task.id"
                                class="hover:bg-[#f5f0ff]/20 transition-colors"
                            >
                                <td class="px-5 py-3.5">
                                    <Link :href="`/tasks/${task.id}`" class="font-semibold text-[#5e16bd] hover:underline">
                                        {{ task.title }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3.5 text-gray-600">{{ task.assignee_name || task.assignee?.name || '—' }}</td>
                                <td class="px-5 py-3.5"><StatusBadge :status="task.status" type="task" /></td>
                                <td class="px-5 py-3.5"><PriorityBadge :priority="task.priority" /></td>
                                <td class="px-5 py-3.5 text-gray-400">{{ formatDate(task.deadline) }}</td>
                            </tr>
                            <tr v-if="!localTasks.length">
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">
                                    No tasks yet.
                                    <button @click="showAddTask = true" class="ml-1 text-[#5e16bd] hover:underline">Add the first task →</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Work Logs -->
        <div v-if="activeTab === 'work-logs'" class="mt-5">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Work Logs</h3>
                    <span class="text-sm text-gray-400">
                        Total: {{ workLogs.reduce((s, l) => s + parseFloat(l.hours_spent || 0), 0).toFixed(1) }}h
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-5 py-3.5">User</th>
                                <th class="px-5 py-3.5">Task</th>
                                <th class="px-5 py-3.5 text-right">Hours</th>
                                <th class="px-5 py-3.5">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="log in workLogs" :key="log.id" class="hover:bg-[#f5f0ff]/20 transition-colors">
                                <td class="px-5 py-3.5 text-gray-400 whitespace-nowrap tabular-nums">{{ formatDate(log.log_date) }}</td>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ log.user_name || '—' }}</td>
                                <td class="px-5 py-3.5 text-gray-600">{{ log.task_title || '—' }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                                <td class="px-5 py-3.5 text-gray-500 max-w-[280px] truncate">{{ log.note || '—' }}</td>
                            </tr>
                            <tr v-if="!workLogs.length">
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No work logs recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Documents -->
        <div v-if="activeTab === 'documents'" class="mt-5">
            <DocumentUpload
                documentable-type="project"
                :documentable-id="project.id"
                :documents="documents"
            />
        </div>

        <!-- Tab: Comments -->
        <div v-if="activeTab === 'comments'" class="mt-5">
            <CommentSection
                :comments="comments"
                commentable-type="project"
                :commentable-id="project.id"
            />
        </div>
    </div>

    <!-- Add Task Modal -->
    <Teleport to="body">
        <div v-if="showAddTask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900">Add Task</h3>
                <p class="mt-0.5 text-sm text-gray-500">Create a task for this project</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input
                            v-model="taskForm.title"
                            type="text"
                            placeholder="Task title"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none"
                            :class="{ 'border-red-400': taskErrors.title }"
                        />
                        <p v-if="taskErrors.title" class="mt-1 text-xs text-red-600">{{ taskErrors.title }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Assign To</label>
                            <select
                                v-model="taskForm.assigned_to"
                                class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none"
                            >
                                <option value="">Unassigned</option>
                                <option v-for="m in (project.members || [])" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
                            <select
                                v-model="taskForm.priority"
                                class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none"
                            >
                                <option value="p0">P0 · Critical</option>
                                <option value="p1">P1 · High</option>
                                <option value="p2">P2 · Medium</option>
                                <option value="p3">P3 · Low</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Deadline</label>
                        <input
                            v-model="taskForm.deadline"
                            type="date"
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="taskForm.description"
                            rows="2"
                            placeholder="Optional details..."
                            class="block w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:border-[#5e16bd] focus:bg-white focus:ring-1 focus:ring-[#5e16bd] outline-none"
                        />
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button
                        @click="showAddTask = false"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveTask"
                        :disabled="taskSaving"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] disabled:opacity-50 transition-colors"
                    >
                        <svg v-if="taskSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ taskSaving ? 'Creating...' : 'Create Task' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
