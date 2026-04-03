<script setup>
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    features: { type: Object, default: () => ({ data: [], links: [] }) },
});

// BUG FIX: Removed <Link :href="`/features/${feature.id}`"> — no web route exists for feature detail.
// Title is shown as plain text now.

const statusConfig = {
    backlog:           { classes: 'bg-gray-100 text-gray-600',     label: 'Backlog' },
    proposed:          { classes: 'bg-sky-100 text-sky-700',       label: 'Proposed' },
    in_progress:       { classes: 'bg-[#ece1ff] text-[#5e16bd]',     label: 'In Progress' },
    in_review:         { classes: 'bg-purple-100 text-purple-700', label: 'In Review' },
    ready_for_qa:      { classes: 'bg-yellow-100 text-yellow-700', label: 'Ready for QA' },
    in_qa:             { classes: 'bg-orange-100 text-orange-700', label: 'In QA' },
    ready_for_release: { classes: 'bg-teal-100 text-teal-700',     label: 'Ready for Release' },
    released:          { classes: 'bg-emerald-100 text-emerald-700', label: 'Released' },
    deferred:          { classes: 'bg-amber-100 text-amber-700',   label: 'Deferred' },
    rejected:          { classes: 'bg-red-100 text-red-700',       label: 'Rejected' },
    completed:         { classes: 'bg-emerald-100 text-emerald-700', label: 'Completed' },
};

function getStatusConfig(status) {
    return statusConfig[status] || { classes: 'bg-gray-100 text-gray-600', label: (status || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) };
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Features" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Features</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ features.data?.length ?? 0 }} feature{{ (features.data?.length ?? 0) !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Title</th>
                            <th class="px-5 py-3.5">Module</th>
                            <th class="px-5 py-3.5">Assignee</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Priority</th>
                            <th class="px-5 py-3.5 text-right">Requests</th>
                            <th class="px-5 py-3.5">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="feature in features.data"
                            :key="feature.id"
                            class="hover:bg-[#f5f0ff]/20 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <!-- No web route for feature detail — showing as plain text -->
                                <span class="font-semibold text-gray-900">{{ feature.title }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ feature.module_name || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span v-if="feature.assigned_to_name" class="inline-flex items-center gap-1.5 text-gray-600">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">
                                        {{ feature.assigned_to_name.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ feature.assigned_to_name }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span
                                    :class="getStatusConfig(feature.status).classes"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >
                                    {{ getStatusConfig(feature.status).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <PriorityBadge :priority="feature.priority" />
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-gray-700">{{ feature.request_count ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(feature.deadline) }}</td>
                        </tr>
                        <tr v-if="!features.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No features found.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="features.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in features.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        link.active ? 'bg-[#5e16bd] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200',
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
