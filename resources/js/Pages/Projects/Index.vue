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
const canCreate = computed(() => ['cto', 'ceo'].includes(role.value));

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Projects" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <Link
                v-if="canCreate"
                href="/projects/create"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
            >
                New Project
            </Link>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Owner</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 min-w-[150px]">Progress</th>
                            <th class="px-5 py-3">Tasks</th>
                            <th class="px-5 py-3">Effort</th>
                            <th class="px-5 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="project in projects.data"
                            :key="project.id"
                            class="cursor-pointer hover:bg-gray-50 transition-colors"
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
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(project.created_at) }}</td>
                        </tr>
                        <tr v-if="!projects.data?.length">
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">No projects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="projects.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-200 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in projects.links"
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
