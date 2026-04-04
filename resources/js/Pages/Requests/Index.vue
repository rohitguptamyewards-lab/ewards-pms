<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    requests: { type: Object, default: () => ({ data: [], links: [] }) },
    filters:  { type: Object, default: () => ({}) },
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
// BUG FIX: was only ['sales', 'cto'] — added ceo, manager, mc_team
const canCreate = computed(() => ['cto', 'ceo', 'manager', 'mc_team', 'sales'].includes(role.value));

const localFilters = ref({
    status:  props.filters.status  || '',
    type:    props.filters.type    || '',
    urgency: props.filters.urgency || '',
    search:  props.filters.search  || '',
});

function applyFilters() {
    router.get('/requests', {
        ...Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v)),
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    localFilters.value = { status: '', type: '', urgency: '', search: '' };
    router.get('/requests', {}, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const urgencyConfig = {
    merchant_blocked: { classes: 'bg-red-100 text-red-700',    label: 'Merchant Blocked' },
    merchant_unhappy: { classes: 'bg-orange-100 text-orange-700', label: 'Merchant Unhappy' },
    nice_to_have:     { classes: 'bg-gray-100 text-gray-600',   label: 'Nice to Have' },
};

const typeConfig = {
    bug:         { classes: 'bg-red-100 text-red-700',    label: 'Bug' },
    new_feature: { classes: 'bg-purple-100 text-purple-700', label: 'New Feature' },
    improvement: { classes: 'bg-[#e8ddf0] text-[#4e1a77]',  label: 'Improvement' },
};

function getUrgency(u) { return urgencyConfig[u] || { classes: 'bg-gray-100 text-gray-600', label: (u || '').replace(/_/g, ' ') }; }
function getType(t)    { return typeConfig[t]    || { classes: 'bg-gray-100 text-gray-600', label: (t || '').replace(/_/g, ' ') }; }
</script>

<template>
    <Head title="Requests" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Requests</h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ requests.data?.length ?? 0 }} request{{ (requests.data?.length ?? 0) !== 1 ? 's' : '' }}</p>
            </div>
            <Link
                v-if="canCreate"
                href="/requests/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Request
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[150px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Status</label>
                    <select v-model="localFilters.status" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none">
                        <option value="">All Statuses</option>
                        <option value="received">Received</option>
                        <option value="under_review">Under Review</option>
                        <option value="clarification_needed">Clarification Needed</option>
                        <option value="linked">Linked</option>
                        <option value="deferred">Deferred</option>
                        <option value="rejected">Rejected</option>
                        <option value="fulfilled">Fulfilled</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Type</label>
                    <select v-model="localFilters.type" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none">
                        <option value="">All Types</option>
                        <option value="bug">Bug</option>
                        <option value="new_feature">New Feature</option>
                        <option value="improvement">Improvement</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Urgency</label>
                    <select v-model="localFilters.urgency" class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-[#4e1a77] focus:ring-1 focus:ring-[#4e1a77] outline-none">
                        <option value="">All Urgency</option>
                        <option value="merchant_blocked">Merchant Blocked</option>
                        <option value="merchant_unhappy">Merchant Unhappy</option>
                        <option value="nice_to_have">Nice to Have</option>
                    </select>
                </div>
                <div class="min-w-[200px] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">Search</label>
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Search requests..."
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

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5">Title</th>
                            <th class="px-5 py-3.5">Merchant</th>
                            <th class="px-5 py-3.5">Type</th>
                            <th class="px-5 py-3.5">Urgency</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Demand</th>
                            <th class="px-5 py-3.5">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="req in requests.data"
                            :key="req.id"
                            class="group hover:bg-[#f5f0ff]/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <Link :href="`/requests/${req.id}`" class="font-semibold text-[#4e1a77] group-hover:underline">
                                    {{ req.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ req.merchant?.name || req.merchant_name || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span :class="getType(req.type).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ getType(req.type).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span :class="getUrgency(req.urgency).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ getUrgency(req.urgency).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="req.status" type="request" />
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-gray-700">{{ req.demand_count ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(req.created_at) }}</td>
                        </tr>
                        <tr v-if="!requests.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No requests found.</p>
                                <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="requests.links?.length > 3" class="flex items-center justify-center gap-1 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <Link
                    v-for="link in requests.links"
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
