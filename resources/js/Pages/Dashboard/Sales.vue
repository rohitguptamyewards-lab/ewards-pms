<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    myRequests:          { type: Array,  default: () => [] },
    stats:               { type: Object, default: () => ({}) },
    merchantHistory:     { type: Object, default: null },
    newProductFeatures:  { type: Array,  default: () => [] },
});

const totalSubmitted = computed(() => Object.values(props.stats).reduce((a, b) => a + b, 0));
const pending        = computed(() => (props.stats.received ?? 0) + (props.stats.under_review ?? 0));

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const urgencyConfig = {
    merchant_blocked: { classes: 'bg-red-100 text-red-700',       label: 'Merchant Blocked' },
    merchant_unhappy: { classes: 'bg-orange-100 text-orange-700', label: 'Merchant Unhappy' },
    nice_to_have:     { classes: 'bg-gray-100 text-gray-600',     label: 'Nice to Have' },
};

const typeConfig = {
    bug:         { classes: 'bg-red-100 text-red-700',       label: 'Bug' },
    new_feature: { classes: 'bg-purple-100 text-purple-700', label: 'New Feature' },
    improvement: { classes: 'bg-[#e8ddf0] text-[#4e1a77]',  label: 'Improvement' },
};

function getUrgency(u) { return urgencyConfig[u] || { classes: 'bg-gray-100 text-gray-600', label: (u||'').replace(/_/g,' ') }; }
function getType(t)    { return typeConfig[t]    || { classes: 'bg-gray-100 text-gray-600', label: (t||'').replace(/_/g,' ') }; }
</script>

<template>
    <Head title="My Requests" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Requests</h1>
                <p class="mt-0.5 text-sm text-gray-500">Track the merchant requests you have submitted</p>
            </div>
            <!-- Item 74: Mobile-first New Request CTA -->
            <Link
                href="/requests/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#4e1a77] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3d1560] transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Request
            </Link>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
            <StatsCard label="Total Submitted" :value="totalSubmitted"             color="blue"    />
            <StatsCard label="Pending"         :value="pending"                    color="yellow"  />
            <StatsCard label="Linked"          :value="stats.linked ?? 0"          color="green"   />
            <StatsCard label="Fulfilled"       :value="stats.fulfilled ?? 0"       color="emerald" />
            <StatsCard label="Rejected"        :value="stats.rejected ?? 0"        color="red"     />
        </div>

        <!-- Pending alert -->
        <div v-if="pending > 0" class="mb-5 flex items-center gap-3 rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4">
            <svg class="h-5 w-5 shrink-0 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <p class="text-sm text-yellow-800">
                <span class="font-semibold">{{ pending }} request{{ pending !== 1 ? 's' : '' }}</span> are pending review by the team.
            </p>
        </div>

        <!-- Item 52: New Product Features Feed -->
        <div v-if="newProductFeatures.length" class="mb-6 overflow-hidden rounded-xl border border-green-200 bg-green-50 shadow-sm">
            <div class="flex items-center gap-2 border-b border-green-100 px-5 py-3">
                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <h3 class="text-sm font-semibold text-green-800">New Product Features (Last 30 Days)</h3>
                <span class="ml-auto rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">{{ newProductFeatures.length }} new</span>
            </div>
            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feat in newProductFeatures"
                    :key="feat.id"
                    class="rounded-lg border border-green-100 bg-white p-3 shadow-sm"
                >
                    <p class="text-sm font-semibold text-gray-800 line-clamp-1">{{ feat.title }}</p>
                    <p v-if="feat.module_name" class="mt-0.5 text-xs text-gray-400">{{ feat.module_name }}</p>
                    <p v-if="feat.description" class="mt-1 text-xs text-gray-500 line-clamp-2">{{ feat.description }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Released</span>
                        <span class="text-xs text-gray-400">{{ formatDate(feat.released_at) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Requests table -->
            <div class="lg:col-span-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="px-5 py-3.5">Title</th>
                                <th class="px-5 py-3.5">Merchant</th>
                                <th class="px-5 py-3.5 hidden sm:table-cell">Type</th>
                                <th class="px-5 py-3.5 hidden sm:table-cell">Urgency</th>
                                <!-- Item 53: Translated status label column -->
                                <th class="px-5 py-3.5">Status</th>
                                <!-- Item 50: ETA column -->
                                <th class="px-5 py-3.5 hidden md:table-cell">ETA</th>
                                <th class="px-5 py-3.5 hidden lg:table-cell">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="req in myRequests"
                                :key="req.id"
                                class="group hover:bg-[#f5f0ff]/30 transition-colors"
                            >
                                <td class="px-5 py-3.5">
                                    <Link :href="`/requests/${req.id}`" class="font-semibold text-[#4e1a77] group-hover:underline">
                                        {{ req.title }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3.5 text-gray-600">{{ req.merchant_name || '—' }}</td>
                                <td class="px-5 py-3.5 hidden sm:table-cell">
                                    <span :class="getType(req.type).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">{{ getType(req.type).label }}</span>
                                </td>
                                <td class="px-5 py-3.5 hidden sm:table-cell">
                                    <span :class="getUrgency(req.urgency).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">{{ getUrgency(req.urgency).label }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <!-- Item 53: Show translated status label -->
                                    <span class="text-xs text-gray-700">{{ req.status_label || req.status }}</span>
                                </td>
                                <!-- Item 50: Sprint ETA -->
                                <td class="px-5 py-3.5 hidden md:table-cell text-xs text-gray-500">
                                    {{ req.sprint_eta ? formatDate(req.sprint_eta) : '—' }}
                                </td>
                                <td class="px-5 py-3.5 hidden lg:table-cell text-gray-400">{{ formatDate(req.created_at) }}</td>
                            </tr>
                            <tr v-if="!myRequests.length">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <p class="text-sm font-medium text-gray-400">No requests submitted yet.</p>
                                    <Link href="/requests/create" class="mt-2 inline-block text-sm text-[#4e1a77] hover:underline">Submit your first request →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Item 51: Merchant Lookup / History -->
            <div v-if="merchantHistory" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Merchant History</h3>
                    <p class="mt-0.5 text-sm text-gray-500">{{ merchantHistory.merchant.name }}</p>
                    <span v-if="merchantHistory.merchant.tier" class="mt-1 inline-block rounded-full bg-[#e8ddf0] px-2 py-0.5 text-xs font-semibold text-[#4e1a77] capitalize">
                        {{ merchantHistory.merchant.tier?.replace(/_/g, ' ') }}
                    </span>
                </div>
                <div class="px-5 py-4">
                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 p-3 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ merchantHistory.total_requests }}</p>
                            <p class="text-xs text-gray-400">Total Requests</p>
                        </div>
                        <div class="rounded-lg bg-green-50 p-3 text-center">
                            <p class="text-2xl font-bold text-green-700">{{ merchantHistory.fulfilled }}</p>
                            <p class="text-xs text-gray-400">Completed</p>
                        </div>
                    </div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Recent Requests</p>
                    <div class="space-y-2">
                        <div v-for="req in merchantHistory.recent_requests" :key="req.id" class="flex items-center justify-between gap-2">
                            <Link :href="`/requests/${req.id}`" class="truncate text-xs font-medium text-[#4e1a77] hover:underline">{{ req.title }}</Link>
                            <StatusBadge :status="req.status" type="request" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
