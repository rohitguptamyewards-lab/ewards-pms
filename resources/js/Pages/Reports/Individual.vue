<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    report:      { type: Object, default: () => ({ totalHours: 0, projects: [], logs: [] }) },
    filters:     { type: Object, default: () => ({}) },
    teamMembers: { type: Array,  default: () => [] },
    isManager:   { type: Boolean, default: false },
});

const localFilters = ref({
    user_id:   props.filters.user_id   || '',
    date_from: props.filters.date_from || '',
    date_to:   props.filters.date_to   || '',
});

function applyFilters() {
    router.get('/reports/individual', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { user_id: '', date_from: '', date_to: '' };
    router.get('/reports/individual', {}, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Individual Report" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Individual Report</h1>
            <p class="mt-0.5 text-sm text-gray-500">Personal time breakdown</p>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <!-- Manager: pick team member -->
                <div v-if="isManager" class="min-w-[200px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Team Member</label>
                    <select
                        v-model="localFilters.user_id"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    >
                        <option value="">Select member</option>
                        <option v-for="m in teamMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date From</label>
                    <input
                        v-model="localFilters.date_from"
                        type="date"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    />
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Date To</label>
                    <input
                        v-model="localFilters.date_to"
                        type="date"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none"
                    />
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors">
                        Apply
                    </button>
                    <button @click="clearFilters" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary + Project Breakdown row -->
        <div class="mb-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- Total hours card -->
            <div class="rounded-xl border border-[#ddd0f7] bg-gradient-to-br from-[#f5f0ff] to-[#e8ddf0] p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#4e1a77]">Total Hours</p>
                <p class="mt-1 text-4xl font-extrabold text-[#361963]">{{ report.totalHours ?? 0 }}h</p>
                <p v-if="report.logs?.length" class="mt-1 text-sm text-[#4e1a77]">{{ report.logs.length }} entries</p>
            </div>

            <!-- Project Breakdown -->
            <div v-if="report.projects?.length" class="col-span-1 lg:col-span-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-gray-900">Project Breakdown</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="(project, idx) in report.projects" :key="idx" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="h-2 w-2 rounded-full bg-[#4e1a77] shrink-0"></div>
                            <span class="text-sm font-medium text-gray-800 truncate">{{ project.name }}</span>
                        </div>
                        <span class="shrink-0 ml-3 text-sm font-semibold text-gray-900">{{ project.hours }}h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Logs Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Detailed Logs</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Task</th>
                            <th class="px-5 py-3.5 text-right">Hours</th>
                            <th class="px-5 py-3.5">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="(log, idx) in report.logs"
                            :key="idx"
                            class="hover:bg-[#f5f0ff]/20 transition-colors"
                        >
                            <td class="px-5 py-3.5 text-gray-400 whitespace-nowrap tabular-nums">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ log.project_name || '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ log.task_title || '—' }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3.5 text-gray-500 max-w-[280px] truncate">{{ log.note || '—' }}</td>
                        </tr>
                        <tr v-if="!report.logs?.length">
                            <td colspan="5" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No work logs found.</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting the date range</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
