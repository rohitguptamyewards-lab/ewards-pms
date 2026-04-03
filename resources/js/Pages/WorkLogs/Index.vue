<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    workLogs: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const localFilters = ref({
    project_id: props.filters.project_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function applyFilters() {
    router.get('/work-logs', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Work Logs" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Work Logs</h1>
            <Link
                href="/work-logs/create"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
            >
                Log Work
            </Link>
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

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Project</th>
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Hours</th>
                            <th class="px-5 py-3">Note</th>
                            <th class="px-5 py-3">Blocker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="log in workLogs.data"
                            :key="log.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3 text-gray-700">{{ formatDate(log.log_date) }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ log.project_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ log.task_title || '-' }}</td>
                            <td class="px-5 py-3 text-gray-900 font-medium">{{ log.hours_spent }}h</td>
                            <td class="px-5 py-3 text-gray-700">{{ log.note || '-' }}</td>
                            <td class="px-5 py-3">
                                <span v-if="log.blocker" class="text-sm text-red-600">{{ log.blocker }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                        </tr>
                        <tr v-if="!workLogs.data?.length">
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No work logs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="workLogs.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-200 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in workLogs.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    v-html="link.label"
                    preserve-scroll
                />
            </div>
        </div>
    </div>
</template>
