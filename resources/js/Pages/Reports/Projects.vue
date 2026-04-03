<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    report: {
        type: Object,
        default: () => ({ projects: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const localFilters = ref({
    status: props.filters.status || '',
    owner_id: props.filters.owner_id || '',
});

function applyFilters() {
    router.get('/reports/projects', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Project Report" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Project Report</h1>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Status</label>
                <select v-model="localFilters.status" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                </select>
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
                            <th class="px-5 py-3">Project</th>
                            <th class="px-5 py-3">Owner</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 min-w-[150px]">Progress</th>
                            <th class="px-5 py-3">Tasks</th>
                            <th class="px-5 py-3">Effort</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="project in report.projects"
                            :key="project.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3">
                                <Link :href="`/projects/${project.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ project.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ project.owner_name || '-' }}</td>
                            <td class="px-5 py-3">
                                <StatusBadge :status="project.status" type="project" />
                            </td>
                            <td class="px-5 py-3">
                                <ProgressBar :percentage="project.progress || 0" />
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ project.task_count ?? 0 }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ project.total_effort ?? 0 }}h</td>
                        </tr>
                        <tr v-if="!report.projects?.length">
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No projects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
