<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    report:      { type: Object, default: () => ({ logs: [], totalHours: 0 }) },
    filters:     { type: Object, default: () => ({}) },
    teamMembers: { type: Array,  default: () => [] },
    projects:    { type: Array,  default: () => [] },
    tasks:       { type: Array,  default: () => [] },
});

const localFilters = ref({
    user_id:    props.filters.user_id    || '',
    project_id: props.filters.project_id || '',
    task_id:    props.filters.task_id    || '',
    date_from:  props.filters.date_from  || '',
    date_to:    props.filters.date_to    || '',
});

const filteredTasks = computed(() => {
    if (!localFilters.value.project_id) return props.tasks;
    return props.tasks.filter(t => String(t.project_id) === String(localFilters.value.project_id));
});

function applyFilters() {
    router.get('/reports/work-logs', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { user_id: '', project_id: '', task_id: '', date_from: '', date_to: '' };
    router.get('/reports/work-logs', {}, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Work Log Report" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Work Log Report</h1>
            <p class="mt-0.5 text-sm text-gray-500">Analyse team time entries</p>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">User</label>
                    <select v-model="localFilters.user_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Users</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Project</label>
                    <select v-model="localFilters.project_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" @change="localFilters.task_id = ''">
                        <option value="">All Projects</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="min-w-[200px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Task</label>
                    <select v-model="localFilters.task_id" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Tasks</option>
                        <option v-for="t in filteredTasks" :key="t.id" :value="t.id">
                            {{ t.title }}{{ t.project_name ? ' — ' + t.project_name : '' }}
                        </option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date From</label>
                    <input v-model="localFilters.date_from" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
                </div>
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date To</label>
                    <input v-model="localFilters.date_to" type="date" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none" />
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

        <!-- Summary Card -->
        <div class="mb-5 rounded-xl border border-[#ddd0f7] bg-gradient-to-br from-[#f5f0ff] to-[#ece1ff] p-6 shadow-sm">
            <div class="flex items-end gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#5e16bd]">Total Hours</p>
                    <p class="mt-1 text-4xl font-extrabold text-[#361963]">{{ report.totalHours ?? 0 }}h</p>
                </div>
                <p v-if="report.logs?.length" class="mb-1.5 text-sm text-[#5e16bd]">
                    across {{ report.logs.length }} entries
                </p>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">User</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5 text-right">Hours</th>
                            <th class="px-5 py-3.5">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(log, idx) in report.logs" :key="idx" class="hover:bg-[#f5f0ff]/20 transition-colors">
                            <td class="px-5 py-3.5 text-gray-400 whitespace-nowrap tabular-nums">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 font-medium text-gray-900">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#ece1ff] text-xs font-bold text-[#5e16bd]">
                                        {{ (log.user_name || '?').charAt(0).toUpperCase() }}
                                    </span>
                                    {{ log.user_name || '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">{{ log.project_name || '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ log.task_title || '—' }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3.5 text-gray-500 max-w-[280px] truncate">{{ log.note || '—' }}</td>
                        </tr>
                        <tr v-if="!report.logs?.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No work logs found.</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
