<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features: { type: Object, default: () => ({ data: [], links: [] }) },
});

const featureStatusColors = {
    backlog: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-blue-100 text-blue-700',
    in_review: 'bg-purple-100 text-purple-700',
    ready_for_qa: 'bg-yellow-100 text-yellow-700',
    in_qa: 'bg-orange-100 text-orange-700',
    ready_for_release: 'bg-teal-100 text-teal-700',
    released: 'bg-green-100 text-green-700',
    deferred: 'bg-orange-100 text-orange-700',
    rejected: 'bg-red-100 text-red-700',
};

function statusLabel(status) {
    return (status || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Features" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Features</h1>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-5 py-3">Module</th>
                            <th class="px-5 py-3">Assignee</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Requests</th>
                            <th class="px-5 py-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="feature in features.data"
                            :key="feature.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3">
                                <Link :href="`/features/${feature.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ feature.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ feature.module_name || '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ feature.assigned_to_name || '-' }}</td>
                            <td class="px-5 py-3">
                                <span
                                    v-if="featureStatusColors[feature.status]"
                                    :class="featureStatusColors[feature.status]"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ statusLabel(feature.status) }}
                                </span>
                                <StatusBadge v-else :status="feature.status" type="feature" />
                            </td>
                            <td class="px-5 py-3">
                                <PriorityBadge :priority="feature.priority" />
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ feature.request_count ?? 0 }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(feature.deadline) }}</td>
                        </tr>
                        <tr v-if="!features.data?.length">
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">No features found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="features.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-200 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in features.links"
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
