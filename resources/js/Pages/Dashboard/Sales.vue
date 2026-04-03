<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    myRequests: { type: Array,  default: () => [] },
    stats:      { type: Object, default: () => ({}) },
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
    improvement: { classes: 'bg-[#ece1ff] text-[#5e16bd]',  label: 'Improvement' },
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
            <Link
                href="/requests/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#5e16bd] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#4c12a1] transition-colors"
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
            <StatsCard label="Accepted"        :value="stats.accepted ?? 0"        color="green"   />
            <StatsCard label="Completed"       :value="stats.completed ?? 0"       color="emerald" />
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

        <!-- Requests table -->
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
                            <th class="px-5 py-3.5">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="req in myRequests"
                            :key="req.id"
                            class="group hover:bg-[#f5f0ff]/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <Link :href="`/requests/${req.id}`" class="font-semibold text-[#5e16bd] group-hover:underline">
                                    {{ req.title }}
                                </Link>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ req.merchant_name || '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span :class="getType(req.type).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">{{ getType(req.type).label }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span :class="getUrgency(req.urgency).classes" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">{{ getUrgency(req.urgency).label }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="req.status" type="request" />
                            </td>
                            <td class="px-5 py-3.5 text-gray-400">{{ formatDate(req.created_at) }}</td>
                        </tr>
                        <tr v-if="!myRequests.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No requests submitted yet.</p>
                                <Link href="/requests/create" class="mt-2 inline-block text-sm text-[#5e16bd] hover:underline">Submit your first request →</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
