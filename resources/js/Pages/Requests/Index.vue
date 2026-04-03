<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    requests: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canCreate = computed(() => ['sales', 'cto'].includes(role.value));

const localFilters = ref({
    status: props.filters.status || '',
    type: props.filters.type || '',
    urgency: props.filters.urgency || '',
    search: props.filters.search || '',
});

function applyFilters() {
    router.get('/requests', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const urgencyColors = {
    merchant_blocked: 'bg-red-100 text-red-700',
    merchant_unhappy: 'bg-orange-100 text-orange-700',
    nice_to_have: 'bg-gray-100 text-gray-700',
};

const typeColors = {
    bug: 'bg-red-100 text-red-700',
    new_feature: 'bg-purple-100 text-purple-700',
    improvement: 'bg-blue-100 text-blue-700',
};
</script>

<template>
    <Head title="Requests" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Requests</h1>
            <Link
                v-if="canCreate"
                href="/requests/create"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
            >
                New Request
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Status</label>
                <select v-model="localFilters.status" @change="applyFilters" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="received">Received</option>
                    <option value="under_review">Under Review</option>
                    <option value="accepted">Accepted</option>
                    <option value="deferred">Deferred</option>
                    <option value="rejected">Rejected</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Type</label>
                <select v-model="localFilters.type" @change="applyFilters" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="bug">Bug</option>
                    <option value="new_feature">New Feature</option>
                    <option value="improvement">Improvement</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Urgency</label>
                <select v-model="localFilters.urgency" @change="applyFilters" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="merchant_blocked">Merchant Blocked</option>
                    <option value="merchant_unhappy">Merchant Unhappy</option>
                    <option value="nice_to_have">Nice to Have</option>
                </select>
            </div>
            <div class="min-w-[200px] flex-1">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search</label>
                <input
                    v-model="localFilters.search"
                    @input="applyFilters"
                    type="text"
                    placeholder="Search requests..."
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-5 py-3">Merchant</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Urgency</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Demand</th>
                            <th class="px-5 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="req in requests.data"
                            :key="req.id"
                            class="cursor-pointer hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-5 py-3">
                                <Link :href="`/requests/${req.id}`" class="font-medium text-blue-600 hover:underline">
                                    {{ req.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ req.merchant?.name || req.merchant_name || '-' }}</td>
                            <td class="px-5 py-3">
                                <span :class="typeColors[req.type] || 'bg-gray-100 text-gray-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ (req.type || '').replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span :class="urgencyColors[req.urgency] || 'bg-gray-100 text-gray-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ (req.urgency || '').replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <StatusBadge :status="req.status" type="request" />
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ req.demand_count ?? 0 }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(req.created_at) }}</td>
                        </tr>
                        <tr v-if="!requests.data?.length">
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">No requests found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="requests.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-200 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in requests.links"
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
