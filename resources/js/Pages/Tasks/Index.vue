<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tasks:       { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
    projects:    { type: Array,  default: () => [] },
    teamMembers: { type: Array,  default: () => [] },
});

const localFilters = ref({
    project_id:  props.filters.project_id  || '',
    user_id:     props.filters.user_id     || '',
    status:      props.filters.status      || '',
    deadline_to: props.filters.deadline_to || '',
});

const statuses = ['open', 'in_progress', 'blocked', 'done'];

function applyFilters() {
    router.get('/tasks', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { project_id: '', user_id: '', status: '', deadline_to: '' };
    router.get('/tasks', {}, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function isOverdue(dateStr, status) {
    return dateStr && status !== 'done' && new Date(dateStr) < new Date();
}
</script>

<template>
    <Head title="Tasks" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ tasks.length }} task{{ tasks.length !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Project</label>
                    <select v-model="localFilters.project_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Projects</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Assignee</label>
                    <select v-model="localFilters.user_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Users</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Status</label>
                    <select v-model="localFilters.status" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Statuses</option>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Deadline Before</label>
                    <input v-model="localFilters.deadline_to" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors">
                        Apply
                    </button>
                    <button @click="clearFilters" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Title</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Assignee</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Priority</th>
                            <th class="px-5 py-3.5">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="task in tasks" :key="task.id" class="group hover:bg-[#f5f0ff]/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <Link :href="`/tasks/${task.id}`" class="font-semibold text-[#5e16bd] group-hover:underline">
                                    {{ task.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5">
                                <Link v-if="task.project_id" :href="`/projects/${task.project_id}`" class="text-gray-600 hover:text-[#5e16bd] hover:underline">
                                    {{ task.project_name || '—' }}
                                </Link>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span v-if="task.assignee_name" class="inline-flex items-center gap-1.5 text-gray-600">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">
                                        {{ task.assignee_name.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ task.assignee_name }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-5 py-3.5"><StatusBadge :status="task.status" type="task" /></td>
                            <td class="px-5 py-3.5"><PriorityBadge :priority="task.priority" /></td>
                            <td class="px-5 py-3.5" :class="isOverdue(task.deadline, task.status) ? 'font-semibold text-red-600' : 'text-gray-400'">
                                {{ formatDate(task.deadline) }}
                            </td>
                        </tr>
                        <tr v-if="!tasks.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No tasks found</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
