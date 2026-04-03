<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    untriagedRequests:       { type: Array, default: () => [] },
    merchantBlockedRequests: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total: 0, untriaged: 0, accepted: 0, merchantBlocked: 0 }),
    },
});

function daysSince(dateStr) {
    if (!dateStr) return 0;
    return Math.floor((Date.now() - new Date(dateStr).getTime()) / 86400000);
}

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
    bug:         { classes: 'bg-red-100 text-red-700',        label: 'Bug' },
    new_feature: { classes: 'bg-purple-100 text-purple-700',  label: 'New Feature' },
    improvement: { classes: 'bg-[#ece1ff] text-[#5e16bd]',   label: 'Improvement' },
};

function getUrgency(u) { return urgencyConfig[u] || { classes: 'bg-gray-100 text-gray-600', label: (u||'').replace(/_/g,' ') }; }
function getType(t)    { return typeConfig[t]    || { classes: 'bg-gray-100 text-gray-600', label: (t||'').replace(/_/g,' ') }; }
</script>

<template>
    <Head title="MC Team Dashboard" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">MC Team Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">Merchant requests & triage queue</p>
            </div>
            <div class="flex gap-3">
                <Link
                    href="/requests/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Request
                </Link>
                <Link
                    v-if="stats.untriaged > 0"
                    href="/requests?status=received"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    Triage Queue ({{ stats.untriaged }})
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <StatsCard label="Total Requests"    :value="stats.total"           color="gray"   />
            <StatsCard label="Needs Triage"      :value="stats.untriaged"       color="yellow" />
            <StatsCard label="Accepted"          :value="stats.accepted"        color="green"  />
            <StatsCard label="Merchant Blocked"  :value="stats.merchantBlocked" color="red"    />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Untriaged Queue -->
            <div class="rounded-xl border border-yellow-100 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 border-b border-yellow-100 bg-yellow-50 px-5 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-yellow-100">
                        <svg class="h-4 w-4 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h2 class="font-semibold text-yellow-800">Needs Triage</h2>
                    <span class="ml-auto rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700">{{ untriagedRequests.length }}</span>
                </div>
                <div v-if="untriagedRequests.length" class="divide-y divide-gray-50">
                    <div v-for="req in untriagedRequests" :key="req.id" class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <Link :href="`/requests/${req.id}`" class="font-medium text-[#5e16bd] hover:underline truncate block">
                                    {{ req.title }}
                                </Link>
                                <p class="mt-0.5 text-xs text-gray-500">{{ req.merchant_name || '—' }} · {{ req.requester_name || '—' }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <span :class="getType(req.type).classes" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">{{ getType(req.type).label }}</span>
                                    <span :class="getUrgency(req.urgency).classes" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">{{ getUrgency(req.urgency).label }}</span>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{ daysSince(req.created_at) }}d ago</span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">All requests have been triaged.</p>
                </div>
            </div>

            <!-- Merchant Blocked -->
            <div class="rounded-xl border border-red-100 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 border-b border-red-100 bg-red-50 px-5 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <h2 class="font-semibold text-red-800">Merchant Blocked</h2>
                    <span class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">{{ merchantBlockedRequests.length }}</span>
                </div>
                <div v-if="merchantBlockedRequests.length" class="divide-y divide-gray-50">
                    <div v-for="req in merchantBlockedRequests" :key="req.id" class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <Link :href="`/requests/${req.id}`" class="font-medium text-[#5e16bd] hover:underline truncate block">
                                    {{ req.title }}
                                </Link>
                                <p class="mt-0.5 text-xs text-gray-500">{{ req.merchant_name || '—' }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <StatusBadge :status="req.status" type="request" />
                                    <span class="text-xs text-gray-400">{{ formatDate(req.created_at) }}</span>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                                {{ daysSince(req.created_at) }}d
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-400">No merchant-blocked requests.</p>
                </div>
            </div>
        </div>
    </div>
</template>
