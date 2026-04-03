<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    report:  { type: Object, default: () => ({ projects: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const localFilters = ref({
    status:   props.filters.status   || '',
    owner_id: props.filters.owner_id || '',
});

function applyFilters() {
    router.get('/reports/projects', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { status: '', owner_id: '' };
    router.get('/reports/projects', {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Project Report" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Project Report</h1>
            <p class="mt-0.5 text-sm text-gray-500">Overview of all projects and their status</p>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[150px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Status</label>
                    <select v-model="localFilters.status" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#5e16bd] focus:ring-1 focus:ring-[#5e16bd] outline-none">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>
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

        <!-- Summary badges -->
        <div v-if="report.projects?.length" class="mb-5 flex flex-wrap gap-3">
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Projects</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ report.projects.length }}</p>
            </div>
            <div class="rounded-xl border border-[#ddd0f7] bg-[#f5f0ff] px-5 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5e16bd]">Active</p>
                <p class="mt-1 text-2xl font-bold text-[#361963]">{{ report.projects.filter(p => p.status === 'active').length }}</p>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-5 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Completed</p>
                <p class="mt-1 text-2xl font-bold text-emerald-900">{{ report.projects.filter(p => p.status === 'completed').length }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Effort</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ report.projects.reduce((s, p) => s + (p.total_effort || 0), 0) }}h</p>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Project</th>
                            <th class="px-5 py-3.5">Owner</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 min-w-[160px]">Progress</th>
                            <th class="px-5 py-3.5 text-right">Tasks</th>
                            <th class="px-5 py-3.5 text-right">Effort</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="project in report.projects"
                            :key="project.id"
                            class="group hover:bg-[#f5f0ff]/20 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <Link :href="`/projects/${project.id}`" class="font-semibold text-[#5e16bd] group-hover:underline">
                                    {{ project.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-gray-700">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">
                                        {{ (project.owner_name || '?').charAt(0).toUpperCase() }}
                                    </span>
                                    {{ project.owner_name || '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="project.status" type="project" />
                            </td>
                            <td class="px-5 py-3.5 w-48">
                                <ProgressBar :percentage="project.progress || 0" />
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-gray-700">{{ project.task_count ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ project.total_effort ?? 0 }}h</td>
                        </tr>
                        <tr v-if="!report.projects?.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No projects found.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
