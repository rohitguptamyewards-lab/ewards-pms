<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    report: {
        type: Object,
        default: () => ({ logs: [], totalHours: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const localFilters = ref({
    user_id: props.filters.user_id || '',
    project_id: props.filters.project_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function applyFilters() {
    router.get('/reports/work-logs', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Work Log Report" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Work Log Report</h1>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="min-w-[160px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Date From</label>
                <input
                    v-model="localFilters.date_from"
                    type="date"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                />
            </div>
            <div class="min-w-[160px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Date To</label>
                <input
                    v-model="localFilters.date_to"
                    type="date"
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                />
            </div>
            <div>
                <button
                    @click="applyFilters"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    Apply
                </button>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">Total Hours</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ report.totalHours ?? 0 }}h</p>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Project</th>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Hours</th>
                            <th class="px-5 py-3">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="(log, idx) in report.logs"
                            :key="idx"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ log.user_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ log.project_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ log.task_title || '-' }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3 text-gray-600 max-w-[300px] truncate">{{ log.note || '-' }}</td>
                        </tr>
                        <tr v-if="!report.logs?.length">
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No work logs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
