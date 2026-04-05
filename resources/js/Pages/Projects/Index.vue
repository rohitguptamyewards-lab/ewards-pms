<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    projects: { type: Object, default: () => ({ data: [], links: [] }) },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canCreate = computed(() => ['cto', 'ceo', 'manager'].includes(role.value));

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Projects" />

    <div>
        <!-- Page header -->
        <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Projects</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ projects.data?.length ?? 0 }} project{{ (projects.data?.length ?? 0) !== 1 ? 's' : '' }}</p>
            </div>
            <Link
                v-if="canCreate"
                href="/projects/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Project
            </Link>
        </div>

        <!-- Table card -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Owner</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 min-w-[160px]">Progress</th>
                            <th class="px-5 py-3.5 text-right">Tasks</th>
                            <th class="px-5 py-3.5 text-right">Effort</th>
                            <th class="px-5 py-3.5">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="project in projects.data"
                            :key="project.id"
                            class="group hover:bg-[#f5f0ff]/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <Link :href="`/projects/${project.id}`" class="font-semibold text-[#4e1a77] group-hover:underline">
                                    {{ project.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-gray-600">
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
                            <td class="px-5 py-3.5 text-right font-medium text-gray-700">{{ project.total_effort ?? 0 }}h</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(project.created_at) }}</td>
                        </tr>
                        <tr v-if="!projects.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No projects found</p>
                                <Link v-if="canCreate" href="/projects/create" class="mt-2 inline-block text-sm text-[#4e1a77] hover:underline">Create your first project →</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="projects.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in projects.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        link.active ? 'bg-[#4e1a77] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    v-html="link.label"
                    preserve-scroll
                />
            </div>
        </div>
    </div>
</template>
